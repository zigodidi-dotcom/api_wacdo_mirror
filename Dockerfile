FROM php:8.4-cli

WORKDIR /app

# Extensions nécessaires pour Symfony/Doctrine + MySQL
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libzip-dev \
    && docker-php-ext-install pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Dépendances PHPs
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader --no-scripts

# Code
COPY . .

# Rendre l'entrypoint exécutable
RUN chmod +x bin/entrypoint.sh

# Port Railway
ENV PORT=8080
EXPOSE 8080

CMD ["sh", "-lc", "bin/entrypoint.sh"]
