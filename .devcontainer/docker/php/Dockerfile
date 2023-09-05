FROM php:8.1-fpm

ARG USER_UID=1000
ARG USER_GID=${USER_UID}

RUN apt-get update
RUN apt-get install -y libc-client-dev libfreetype6-dev libjpeg62-turbo-dev libkrb5-dev libonig-dev libpng-dev libpq-dev libxslt-dev libzip-dev sqlite3 zip
RUN pecl install redis-5.3.4
RUN docker-php-ext-enable redis
RUN pecl install xdebug-3.1.1
RUN docker-php-ext-enable xdebug
RUN docker-php-ext-install bcmath
RUN apt-get install -y libjpeg-dev && docker-php-ext-configure gd --with-jpeg && docker-php-ext-install gd
#RUN docker-php-ext-install gd
RUN docker-php-ext-install intl
RUN docker-php-ext-install pdo_mysql
RUN docker-php-ext-install xml
RUN docker-php-ext-install zip

#
# user
RUN groupmod -o -g ${USER_GID} www-data \
  && usermod -o -u ${USER_UID} -g www-data www-data
