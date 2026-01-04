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
    default-mysql-client \
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
RUN mkdir -p var/cache/prod var/log config/jwt public/uploads && \
    chmod -R 777 var && \
    chown -R www-data:www-data var

# Create a minimal .env file for the build process
RUN echo "APP_ENV=prod" > .env && \
    echo "APP_DEBUG=0" >> .env && \
    echo "APP_SECRET=build-time-secret" >> .env && \
    echo "DATABASE_URL=mysql://user:pass@localhost:3306/db" >> .env && \
    echo "CORS_ALLOW_ORIGIN='^https?://.*'" >> .env

# Install dependencies
RUN SYMFONY_DOTENV_VARS=0 composer install \
    --no-dev \
    --no-scripts \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

RUN composer dump-autoload --no-dev --classmap-authoritative --optimize

# Generate JWT keys
RUN openssl genpkey -algorithm RSA -out config/jwt/private.pem -pkeyopt rsa_keygen_bits:4096 && \
    openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem && \
    chmod 644 config/jwt/private.pem config/jwt/public.pem

# Set permissions
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

# PHP configuration
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini

# Create startup script
RUN echo '#!/bin/bash\n\
set -e\n\
\n\
PORT=${PORT:-8080}\n\
echo "=== Starting Ghazalea Backend ==="\n\
echo "Port: $PORT"\n\
echo "DATABASE_URL: ${DATABASE_URL:-NOT SET}"\n\
\n\
# Replace PORT in nginx config\n\
sed -i "s/PORT_PLACEHOLDER/$PORT/g" /etc/nginx/sites-available/default\n\
\n\
# Create .env.local\n\
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
echo "=== .env.local created ==="\n\
cat /app/.env.local\n\
echo "========================"\n\
\n\
# Test MySQL connection\n\
echo "Testing MySQL connection..."\n\
if mysql -h mysql.railway.internal -u root -pKmLMNPbyiVkgCCvghrCUJcJzULZKLqko -e "SELECT 1" 2>/dev/null; then\n\
    echo "MySQL connection OK!"\n\
else\n\
    echo "MySQL connection FAILED - but continuing anyway"\n\
fi\n\
\n\
# Fix permissions\n\
rm -rf /app/var/cache/*\n\
mkdir -p /app/var/cache/prod /app/var/log\n\
chmod -R 777 /app/var\n\
chown -R www-data:www-data /app/var\n\
\n\
# Skip cache warmup if DB not available (will warmup on first request)\n\
echo "Skipping cache warmup - will warmup on first request"\n\
\n\
# Final permissions\n\
chmod -R 777 /app/var\n\
\n\
echo "Starting services..."\n\
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf' > /start.sh && \
    chmod +x /start.sh

EXPOSE 8080

CMD ["/start.sh"]