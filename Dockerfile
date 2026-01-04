FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libicu-dev \
    libonig-dev \
    nginx \
    supervisor \
    && docker-php-ext-install pdo pdo_mysql zip gd intl opcache mbstring \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Set environment variables
ENV APP_ENV=prod
ENV APP_DEBUG=0
ENV COMPOSER_ALLOW_SUPERUSER=1

# Copy entire project
COPY . .

# Create necessary directories
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

# Cache warmup (ignore errors)
RUN php bin/console cache:clear --env=prod --no-debug --no-warmup 2>/dev/null || true && \
    php bin/console cache:warmup --env=prod --no-debug 2>/dev/null || true

# Nginx configuration
RUN echo 'server {\n\
    listen 80;\n\
    server_name _;\n\
    root /app/public;\n\
    \n\
    location / {\n\
        try_files $uri /index.php$is_args$args;\n\
    }\n\
    \n\
    location ~ ^/index\\.php(/|$) {\n\
        fastcgi_pass 127.0.0.1:9000;\n\
        fastcgi_split_path_info ^(.+\\.php)(/.*)$;\n\
        include fastcgi_params;\n\
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;\n\
        fastcgi_param DOCUMENT_ROOT $realpath_root;\n\
        fastcgi_param APP_ENV prod;\n\
        fastcgi_param APP_DEBUG 0;\n\
        internal;\n\
    }\n\
    \n\
    location ~ \\.php$ {\n\
        return 404;\n\
    }\n\
    \n\
    error_log /dev/stderr;\n\
    access_log /dev/stdout;\n\
}' > /etc/nginx/sites-available/default

# Supervisor configuration to run both nginx and php-fpm
RUN echo '[supervisord]\n\
nodaemon=true\n\
\n\
[program:php-fpm]\n\
command=/usr/local/sbin/php-fpm\n\
autostart=true\n\
autorestart=true\n\
\n\
[program:nginx]\n\
command=/usr/sbin/nginx -g "daemon off;"\n\
autostart=true\n\
autorestart=true' > /etc/supervisor/conf.d/supervisord.conf

# PHP production configuration
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.max_accelerated_files=20000" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini

# Remove default nginx config that might conflict
RUN rm -f /etc/nginx/sites-enabled/default && \
    ln -s /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]