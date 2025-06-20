FROM php:8.3-apache

# Installer les dépendances système
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    libicu-dev \
    nodejs \
    npm

# Installer les extensions PHP nécessaires
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Activer le module rewrite d'Apache
RUN a2enmod rewrite

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier tous les fichiers de l'application
COPY . .

# Copier le fichier .env.example vers .env
RUN cp .env.example .env

# Installer les dépendances
RUN composer install --no-dev --optimize-autoloader

# Installer et compiler les assets
RUN npm install && npm run build

# Créer le dossier database s'il n'existe pas
RUN mkdir -p database

# Créer le fichier SQLite s'il n'existe pas
RUN touch database/database.sqlite

# Configurer les permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache \
    && chmod -R 755 /var/www/html/database \
    && chmod 666 /var/www/html/database/database.sqlite

# Générer la clé d'application
RUN php artisan key:generate

# Exécuter les migrations
RUN php artisan migrate --force

# Publier les assets de Filament
RUN php artisan filament:assets \
    && php artisan vendor:publish --tag=filament-assets --force \
    && php artisan vendor:publish --tag=filament-config --force \
    && php artisan vendor:publish --tag=filament-translations --force

# Publier les assets
RUN php artisan storage:link

# Vider le cache
RUN php artisan config:clear \
    && php artisan view:clear \
    && php artisan route:clear

# Optimiser l'application
RUN php artisan optimize

# Configurer Apache
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# Exposer le port 80
EXPOSE 80

# Commande pour démarrer Apache
CMD ["apache2-foreground"] 