# fpm ではなく apache を使うイメージに変更
FROM php:8.2-apache

# XdebugとMySQL連携に必要なパッケージをインストール
RUN apt-get update && apt-get install -y \
    libzip-dev \
    && docker-php-ext-install pdo pdo_mysql \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug

# Apacheの設定
RUN a2enmod rewrite

# DocumentRootを /var/www/html/www に変更する設定を反映
COPY apache-config.conf /etc/apache2/sites-available/000-default.conf

RUN mkdir -p /tmp/php_sessions && \
    chown -R www-data:www-data /tmp/php_sessions