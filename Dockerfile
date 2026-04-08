FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    && docker-php-ext-install mbstring exif pcntl bcmath gd zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite
RUN a2enmod rewrite headers

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html/

# Create backups directory and set permissions
RUN mkdir -p /var/www/html/backups \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/backups \
    && touch /var/www/html/bot_activity.log \
    && chmod 666 /var/www/html/bot_activity.log

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install PHP dependencies (if any)
RUN composer install --no-interaction --optimize-autoloader --no-dev || true

# ✅ PHP SYNTAX CHECK - Isse pata chalega error kya hai
RUN echo "🔍 Checking PHP syntax..." \
    && php -l /var/www/html/index.php \
    && php -l /var/www/html/inc/config.php \
    && php -l /var/www/html/inc/telegram_api.php \
    && php -l /var/www/html/inc/channel_mapping.php \
    && php -l /var/www/html/inc/forward_header.php \
    && php -l /var/www/html/inc/typing_indicators.php \
    && php -l /var/www/html/inc/csv_manager.php \
    && php -l /var/www/html/inc/search_engine.php \
    && php -l /var/www/html/inc/movie_delivery.php \
    && php -l /var/www/html/inc/request_system.php \
    && php -l /var/www/html/inc/user_management.php \
    && php -l /var/www/html/inc/category_browse.php \
    && php -l /var/www/html/inc/backup_system.php \
    && php -l /var/www/html/inc/channel_info.php \
    && php -l /var/www/html/inc/admin_panel.php \
    && php -l /var/www/html/inc/command_handlers.php \
    && php -l /var/www/html/inc/webhook_handler.php \
    && echo "✅ All PHP files syntax OK!"

# Expose port
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
