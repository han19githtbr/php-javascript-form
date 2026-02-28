# ─────────────────────────────────────────────────────────────────
# Dockerfile
#
# Monta um container com PHP 8.2 + Apache para rodar no Render.
# ─────────────────────────────────────────────────────────────────

FROM php:8.2-apache

# Instala libpq-dev (necessária para compilar pdo_pgsql)
# docker-php-ext-install já habilita as extensões automaticamente
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# Instala o Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copia os arquivos do projeto
WORKDIR /var/www/html
COPY . .

# Instala as dependências PHP (PHPMailer, phpdotenv)
RUN composer install --no-dev --optimize-autoloader

# Copia a configuração do Apache
COPY apache.conf /etc/apache2/sites-available/000-default.conf

# Ativa o mod_rewrite
RUN a2enmod rewrite

EXPOSE 80

CMD ["apache2-foreground"]