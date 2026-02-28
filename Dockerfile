# ─────────────────────────────────────────────────────────────────
# Dockerfile
#
# Monta um container com PHP 8.2 + Apache para rodar no Render.
#
# Instrução por instrução:
#   FROM    → parte da imagem oficial do PHP com Apache já embutido
#   RUN     → instala extensões PHP necessárias para o projeto:
#               pdo_pgsql  = conectar ao PostgreSQL (substitui SQLite em prod)
#               pdo_sqlite = manter compatibilidade com dev local
#               mbstring   = strings multibyte (necessário para PHPMailer)
#               openssl    = conexões TLS/SSL (necessário para SMTP Gmail)
#   COPY    → copia todos os arquivos do projeto para o container
#   RUN     → instala as dependências do composer (PHPMailer, phpdotenv)
#   COPY    → copia a configuração customizada do Apache
#   RUN     → ativa o módulo mod_rewrite do Apache (boas práticas)
#   EXPOSE  → informa ao Render que o container escuta na porta 80
#   CMD     → inicia o Apache em foreground (obrigatório em containers)
# ─────────────────────────────────────────────────────────────────

FROM php:8.2-apache

# Instala as extensões PHP necessárias
RUN apt-get update && apt-get install -y \
        libpq-dev \
        libssl-dev \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        pdo_sqlite \
        mbstring \
    && docker-php-ext-enable \
        pdo_pgsql \
        pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

# Instala o Composer (gerenciador de dependências PHP)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copia os arquivos do projeto para o diretório padrão do Apache
WORKDIR /var/www/html
COPY . .

# Instala as dependências PHP (PHPMailer, phpdotenv)
# --no-dev        → não instala dependências de desenvolvimento
# --optimize-autoloader → deixa o autoload mais rápido em produção
RUN composer install --no-dev --optimize-autoloader

# Copia a configuração customizada do Apache
COPY apache.conf /etc/apache2/sites-available/000-default.conf

# Ativa o mod_rewrite (permite URLs limpas e redirecionamentos)
RUN a2enmod rewrite

# Porta que o Apache vai escutar
EXPOSE 80

# Inicia o Apache em foreground — obrigatório em Docker
CMD ["apache2-foreground"]
