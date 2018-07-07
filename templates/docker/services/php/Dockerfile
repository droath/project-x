FROM php:{PHP_VERSION}-fpm-jessie
MAINTAINER Travis Tomka <underoathosx@gmail.com>

# Install PHP extensions.
RUN apt-get update && apt-get install -y {PACKAGE_INSTALL} \
  && rm -rf /var/lib/apt/lists/* \
  {PHP_PECL}
  && docker-php-ext-enable {PHP_EXT_ENABLE} \
  && docker-php-ext-configure {PHP_EXT_CONFIG} \
  && docker-php-ext-install {PHP_EXT_INSTALL}

# Run PHP comamnds.
{PHP_COMMANDS}

# Run cleanup tasks on environment.
RUN apt-get clean; rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*
