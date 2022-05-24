FROM php:8.1-fpm
ARG user=www-data
ARG uid=1000
WORKDIR /var/www
RUN apt-get update && apt-get install -y \
    curl \
    zip \
    unzip \
    wget \
    openssl \
    git \
    libmemcached-dev \
    libc-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
	libmcrypt-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    && docker-php-ext-install iconv mbstring pdo mysqli pdo_mysql exif zip pcntl bcmath \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd \
    && apt-get -y --no-install-recommends install php-gd php8.0-gd php-mysql php-xml php-zip php8.0-mbstring php8.0-gd php8.0-mysql php-redis php8.0-sqlite php8.0-pdo_mysql php8.0-soap \
    && apt-get clean; rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/* \
    && pecl install redis && docker-php-ext-enable redis
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN mkdir -p /home/$user/.composer && chown -R $user:$user /home/$user
USER $user
EXPOSE 9000
CMD ["php-fpm"]
