FROM php:8.2-apache

# Installer les dépendances système
RUN apt-get update && apt-get install -y \
    git unzip zip libzip-dev libonig-dev libpng-dev \
    libjpeg-dev libfreetype6-dev libxml2-dev curl \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql mbstring zip exif pcntl bcmath gd

# Activer mod_rewrite pour Laravel
RUN a2enmod rewrite

# Installer Composer globalement
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Répertoire de travail
WORKDIR /var/www/html

# Copier uniquement les fichiers composer
COPY composer.json composer.lock ./

# Installer les dépendances PHP
RUN composer install --no-dev --no-scripts --optimize-autoloader

# Copier tout le projet
COPY . .

# Régénérer l'autoload et package discover
RUN composer dump-autoload \
    && php artisan package:discover --ansi || true

# Permissions Laravel
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80
CMD ["apache2-foreground"]