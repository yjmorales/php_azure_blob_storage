FROM php:7.4-apache

### Install OS tools
RUN apt-get update \
    && apt-get install --yes curl \
    libmcrypt-dev \
    libreadline-dev \
    libzip-dev \
    nano \
    git \
    vim \
    libwebp-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libxpm-dev \
    libfreetype6-dev \
    python3-distutils \
    python3-apt \
    python3-pip \
    beanstalkd \
\
### Install PHP extras
    && pecl install mcrypt \
    && apt-get update && apt-get install -y zlib1g-dev libmcrypt-dev \
    && docker-php-ext-install -j$(nproc) zip mysqli pdo pdo_mysql gd \
    && docker-php-ext-enable mcrypt \
\
### Changing Ownership of document root directory to apache.
    && chown -R www-data:www-data /var/www/html

### Coping vhost template within apache sites.
COPY dockerization/web/000-default.conf /etc/apache2/sites-enabled/000-default.conf

### Helper command to enter into the docker container termina: > docker exec -it web /bin/bash
### Exposing port 80 to the exterior.
EXPOSE 80