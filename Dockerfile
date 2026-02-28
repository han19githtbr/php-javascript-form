FROM php:8.2-apache

# Instala dependências necessárias
RUN apt-get update && apt-get install -y \
        unzip \
        libpq-dev \
        && docker-php-ext-install pdo pdo_pgsql \
        && rm -rf /var/lib/apt/lists/*

# Instala o Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copia os arquivos do projeto
WORKDIR /var/www/html
COPY . .

# Ajusta permissões para o Apache
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Instala as dependências PHP
RUN composer install --no-dev --optimize-autoloader

# Configuração do Apache
COPY apache.conf /etc/apache2/sites-available/000-default.conf

# Ativa módulos necessários
RUN a2enmod rewrite

EXPOSE 80

CMD ["apache2-foreground"]