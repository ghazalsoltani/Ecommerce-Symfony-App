FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libicu-dev \
    && docker-php-ext-install pdo pdo_mysql zip gd intl opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy composer files first (for better caching)
COPY composer.json composer.lock ./

# Set environment to prod BEFORE installing dependencies
ENV APP_ENV=prod

# Install PHP dependencies (no dev, optimized)
RUN composer install --no-dev --no-scripts --optimize-autoloader --no-interaction

# Copy the rest of the project
COPY . .

# Run post-install scripts manually
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative

# Clear and warm up cache
RUN php bin/console cache:clear --env=prod --no-debug || true
RUN php bin/console cache:warmup --env=prod --no-debug || true

# Create JWT directory and generate keys
RUN mkdir -p config/jwt \
    && openssl genpkey -algorithm RSA -out config/jwt/private.pem -pkeyopt rsa_keygen_bits:4096 \
    && openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem \
    && chmod 644 config/jwt/private.pem config/jwt/public.pem

# Create upload directory
RUN mkdir -p public/uploads && chmod 755 public/uploads

# Set proper permissions
RUN chown -R www-data:www-data /app/var /app/public/uploads

# Apache configuration
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /app/public\n\
    <Directory /app/public>\n\
        AllowOverride All\n\
        Require all granted\n\
        FallbackResource /index.php\n\
    </Directory>\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]