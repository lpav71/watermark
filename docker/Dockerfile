FROM php:8.1-apache

# Установка Xdebug
RUN pecl install xdebug-3.1.6 && docker-php-ext-enable xdebug

# Обновляем список пакетов и устанавливаем Midnight Commander и необходимые зависимости для GD
RUN apt-get update && \
    apt-get install -y mc libjpeg-dev libpng-dev libfreetype6-dev && \
    apt-get clean

# Установка расширения GD с поддержкой JPEG, PNG и FreeType
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install gd

# Настройка Xdebug
RUN echo "xdebug.mode=debug" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.log=/var/log/xdebug.log" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.discover_client_host=1" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.log_level=7" >> /usr/local/etc/php/conf.d/xdebug.ini