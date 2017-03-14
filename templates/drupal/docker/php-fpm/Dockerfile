FROM php:7-fpm
MAINTAINER Travis Tomka <ttomka@isovera.com>

# Install PHP extensions.
RUN apt-get update && apt-get install -y libpng12-dev libjpeg-dev libpq-dev libmcrypt-dev \
  && rm -rf /var/lib/apt/lists/* \
  && docker-php-ext-configure gd --with-png-dir=/usr --with-jpeg-dir=/usr \
  && docker-php-ext-install gd mbstring opcache pdo pdo_mysql pdo_pgsql zip mcrypt

# Run cleanup tasks on environment.
RUN apt-get clean; rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*
