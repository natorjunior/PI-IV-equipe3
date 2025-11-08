# Dockerfile para customizar a imagem PHP com Apache
FROM php:8.2-apache

# Instala extensões PHP comuns (exemplo: mysqli, pdo, zip, gd)
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Habilita o módulo de reescrita do Apache (útil para URLs amigáveis)
RUN a2enmod rewrite

# Copia os arquivos da API para o container (opcional, se não usar volume)
# COPY . /var/www/html

# Define permissões (opcional)
# RUN chown -R www-data:www-data /var/www/html

# Expõe a porta padrão do Apache
EXPOSE 80
