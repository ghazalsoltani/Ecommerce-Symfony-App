FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libpng-dev libicu-dev libonig-dev nginx supervisor \
    && docker-php-ext-install pdo pdo_mysql zip gd intl opcache mbstring \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

ENV APP_ENV=prod
ENV APP_DEBUG=0
ENV COMPOSER_ALLOW_SUPERUSER=1

# Copy project files
COPY . .

# Remove local .env and create production .env with Railway MySQL
RUN rm -f .env .env.local .env.*.local && \
    echo 'APP_ENV=prod' > .env && \
    echo 'APP_DEBUG=0' >> .env && \
    echo 'APP_SECRET=h4Sh_R@nd0m_S3cr3t_K3y_Ghazalea_2026' >> .env && \
    echo 'DATABASE_URL="mysql://root:KmLMNPbyiVkgCCvghrCUJcJzULZKLqko@mysql.railway.internal:3306/railway?serverVersion=8.0&charset=utf8mb4"' >> .env && \
    echo 'CORS_ALLOW_ORIGIN="^https?://.*"' >> .env && \
    echo 'JWT_SECRET_KEY=/app/config/jwt/private.pem' >> .env && \
    echo 'JWT_PUBLIC_KEY=/app/config/jwt/public.pem' >> .env && \
    echo 'JWT_PASSPHRASE=ghazalea-jwt-passphrase-2026' >> .env && \
    echo 'MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0' >> .env && \
    echo 'MAILER_DSN=null://null' >> .env && \
    echo 'STRIPE_SECRET_KEY=sk_test_51Qm07kRwhbE0S47KOfwQTyRPeo8CLS2lAyoqEHwP1ykoLZwzAFgPj2zDSE7oowWQgJubfQJyF1V6IgZJXEUu6FcZ00kbDHIhto' >> .env && \
    echo 'STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret' >> .env && \
    echo 'DOMAIN=https://ghazalea-backend-production.up.railway.app' >> .env && \
    echo 'FRONTEND_DOMAIN=https://ghazalea.com' >> .env

# Create directories
RUN mkdir -p var/cache/prod var/log config/jwt public/uploads && \
    chmod -R 777 var

# Install dependencies (no dev, no scripts)
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader
RUN composer dump-autoload --no-dev --classmap-authoritative --optimize

# Generate JWT keys
RUN openssl genpkey -algorithm RSA -out config/jwt/private.pem -pkeyopt rsa_keygen_bits:4096 && \
    openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem && \
    chmod 644 config/jwt/private.pem config/jwt/public.pem

# Set permissions
RUN chmod -R 777 var && chown -R www-data:www-data var public/uploads config/jwt

# Configure PHP-FPM
RUN echo "clear_env = no" >> /usr/local/etc/php-fpm.d/www.conf

# Nginx configuration with proper MIME types for images
RUN echo 'server {\n\
    listen 8080;\n\
    server_name _;\n\
    root /app/public;\n\
    index index.php;\n\
    \n\
    # Serve images with correct MIME type\n\
    location /uploads/ {\n\
        alias /app/public/uploads/;\n\
        default_type image/jpeg;\n\
        add_header Cache-Control "public, max-age=31536000";\n\
        try_files $uri =404;\n\
    }\n\
    \n\
    location / { \n\
        try_files $uri /index.php$is_args$args; \n\
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
        internal;\n\
    }\n\
    \n\
    location ~ \\.php$ { return 404; }\n\
    \n\
    error_log /dev/stderr;\n\
    access_log /dev/stdout;\n\
}' > /etc/nginx/sites-available/default && \
    rm -f /etc/nginx/sites-enabled/default && \
    ln -s /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default

# Supervisor configuration
RUN echo '[supervisord]\n\
nodaemon=true\n\
user=root\n\
[program:php-fpm]\n\
command=/usr/local/sbin/php-fpm\n\
autostart=true\n\
autorestart=true\n\
stdout_logfile=/dev/stdout\n\
stdout_logfile_maxbytes=0\n\
stderr_logfile=/dev/stderr\n\
stderr_logfile_maxbytes=0\n\
[program:nginx]\n\
command=/usr/sbin/nginx -g "daemon off;"\n\
autostart=true\n\
autorestart=true\n\
stdout_logfile=/dev/stdout\n\
stdout_logfile_maxbytes=0\n\
stderr_logfile=/dev/stderr\n\
stderr_logfile_maxbytes=0' > /etc/supervisor/conf.d/supervisord.conf

# Startup script
RUN echo '#!/bin/bash\n\
echo "=== Ghazalea Backend Starting ==="\n\
\n\
rm -rf /app/var/cache/*\n\
mkdir -p /app/var/cache/prod /app/var/log\n\
chmod -R 777 /app/var\n\
\n\
echo "Starting services..."\n\
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf' > /start.sh && chmod +x /start.sh

EXPOSE 8080
CMD ["/start.sh"]