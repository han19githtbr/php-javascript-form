FROM php:8.2-apache

# Instala dependências necessárias
RUN apt-get update && apt-get install -y \
        unzip \
        libpq-dev \
        libssl-dev \
        && docker-php-ext-install pdo pdo_pgsql \
        && docker-php-ext-install pdo_mysql \
        && rm -rf /var/lib/apt/lists/*

# Instala o Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copia os arquivos do projeto
WORKDIR /var/www/html
COPY . .

# Ajusta permissões
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage

# Instala as dependências PHP
RUN composer install --no-dev --optimize-autoloader

# Configuração do Apache
COPY apache.conf /etc/apache2/sites-available/000-default.conf

# Ativa módulos necessários
RUN a2enmod rewrite \
    && a2enmod ssl \
    && a2enmod headers

# Cria diretório para logs
RUN mkdir -p /var/log/apache2 \
    && chown -R www-data:www-data /var/log/apache2

EXPOSE 80
EXPOSE 443

CMD ["apache2-foreground"]