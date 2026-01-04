FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libicu-dev \
    libonig-dev \
    && docker-php-ext-install pdo pdo_mysql zip gd intl opcache mbstring \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Set environment variables FIRST (very important!)
ENV APP_ENV=prod
ENV APP_DEBUG=0
ENV COMPOSER_ALLOW_SUPERUSER=1

# Copy entire project
COPY . .

# Create necessary directories before composer install
RUN mkdir -p var/cache var/log config/jwt public/uploads

# Create a minimal .env file for the build process
RUN echo "APP_ENV=prod" > .env && \
    echo "APP_DEBUG=0" >> .env && \
    echo "APP_SECRET=build-time-secret-change-in-production" >> .env && \
    echo "DATABASE_URL=mysql://user:pass@localhost:3306/db" >> .env

# Install dependencies WITHOUT scripts and WITHOUT dev packages
RUN SYMFONY_DOTENV_VARS=0 composer install \
    --no-dev \
    --no-scripts \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

# Generate optimized autoloader
RUN composer dump-autoload --no-dev --classmap-authoritative --optimize

# Generate JWT keys
RUN openssl genpkey -algorithm RSA -out config/jwt/private.pem -pkeyopt rsa_keygen_bits:4096 && \
    openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem && \
    chmod 644 config/jwt/private.pem config/jwt/public.pem

# Set permissions
RUN chmod -R 777 var && \
    chmod -R 755 public/uploads && \
    chown -R www-data:www-data var public/uploads config/jwt

# Try to warm up cache (ignore errors during build)
RUN php bin/console cache:clear --env=prod --no-debug --no-warmup 2>/dev/null || true && \
    php bin/console cache:warmup --env=prod --no-debug 2>/dev/null || true

# Apache configuration
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /app/public\n\
    <Directory /app/public>\n\
        AllowOverride All\n\
        Require all granted\n\
        FallbackResource /index.php\n\
    </Directory>\n\
    SetEnv APP_ENV prod\n\
    SetEnv APP_DEBUG 0\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# PHP production configuration
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.max_accelerated_files=20000" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "realpath_cache_size=4096K" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "realpath_cache_ttl=600" >> /usr/local/etc/php/conf.d/opcache.ini

EXPOSE 80

CMD ["apache2-foreground"]