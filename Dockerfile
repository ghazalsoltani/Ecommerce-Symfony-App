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

# Create necessary directories with proper permissions
RUN mkdir -p var/cache/prod var/log config/jwt public/uploads && \
    chmod -R 777 var && \
    chown -R www-data:www-data var

# Create a minimal .env file for the build process
RUN echo "APP_ENV=prod" > .env && \
    echo "APP_DEBUG=0" >> .env && \
    echo "APP_SECRET=build-time-secret-change-in-production" >> .env && \
    echo "DATABASE_URL=mysql://user:pass@localhost:3306/db" >> .env && \
    echo "CORS_ALLOW_ORIGIN='^https?://.*'" >> .env

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

# Set final permissions
RUN chmod -R 777 var && \
    chmod -R 755 public/uploads && \
    chown -R www-data:www-data var public/uploads config/jwt

# Configure PHP-FPM
RUN echo "clear_env = no" >> /usr/local/etc/php-fpm.d/www.conf && \
    echo "catch_workers_output = yes" >> /usr/local/etc/php-fpm.d/www.conf

# Nginx configuration
RUN echo 'server {\n\
    listen PORT_PLACEHOLDER;\n\
    server_name _;\n\
    root /app/public;\n\
    index index.php;\n\
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
        fastcgi_buffer_size 128k;\n\
        fastcgi_buffers 4 256k;\n\
        fastcgi_busy_buffers_size 256k;\n\
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

RUN rm -f /etc/nginx/sites-enabled/default && \
    ln -s /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default

# Supervisor configuration
RUN echo '[supervisord]\n\
nodaemon=true\n\
user=root\n\
\n\
[program:php-fpm]\n\
command=/usr/local/sbin/php-fpm\n\
autostart=true\n\
autorestart=true\n\
stdout_logfile=/dev/stdout\n\
stdout_logfile_maxbytes=0\n\
stderr_logfile=/dev/stderr\n\
stderr_logfile_maxbytes=0\n\
\n\
[program:nginx]\n\
command=/usr/sbin/nginx -g "daemon off;"\n\
autostart=true\n\
autorestart=true\n\
stdout_logfile=/dev/stdout\n\
stdout_logfile_maxbytes=0\n\
stderr_logfile=/dev/stderr\n\
stderr_logfile_maxbytes=0' > /etc/supervisor/conf.d/supervisord.conf

# PHP production configuration
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.max_accelerated_files=20000" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini

# Create startup script
RUN echo '#!/bin/bash\n\
set -e\n\
\n\
PORT=${PORT:-8080}\n\
echo "Starting application on port $PORT"\n\
\n\
# Replace PORT in nginx config\n\
sed -i "s/PORT_PLACEHOLDER/$PORT/g" /etc/nginx/sites-available/default\n\
\n\
# Create .env.local with Railway variables\n\
cat > /app/.env.local << EOF\n\
APP_ENV=${APP_ENV:-prod}\n\
APP_DEBUG=${APP_DEBUG:-0}\n\
APP_SECRET=${APP_SECRET:-default-secret}\n\
DATABASE_URL=${DATABASE_URL}\n\
CORS_ALLOW_ORIGIN=${CORS_ALLOW_ORIGIN:-^https?://.*}\n\
JWT_SECRET_KEY=${JWT_SECRET_KEY:-/app/config/jwt/private.pem}\n\
JWT_PUBLIC_KEY=${JWT_PUBLIC_KEY:-/app/config/jwt/public.pem}\n\
JWT_PASSPHRASE=${JWT_PASSPHRASE:-}\n\
STRIPE_SECRET_KEY=${STRIPE_SECRET_KEY:-}\n\
STRIPE_WEBHOOK_SECRET=${STRIPE_WEBHOOK_SECRET:-}\n\
EOF\n\
\n\
# Fix permissions for var directory\n\
rm -rf /app/var/cache/*\n\
mkdir -p /app/var/cache/prod /app/var/log\n\
chmod -R 777 /app/var\n\
chown -R www-data:www-data /app/var\n\
\n\
# Clear and warmup cache\n\
cd /app && php bin/console cache:clear --env=prod --no-debug 2>&1 || true\n\
cd /app && php bin/console cache:warmup --env=prod --no-debug 2>&1 || true\n\
\n\
# Final permission fix after cache warmup\n\
chmod -R 777 /app/var\n\
chown -R www-data:www-data /app/var\n\
\n\
echo "Cache warmed up, starting services..."\n\
\n\
# Start supervisor\n\
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf' > /start.sh && \
    chmod +x /start.sh

EXPOSE 8080

CMD ["/start.sh"]