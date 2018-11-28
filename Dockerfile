FROM composer AS build

# install dependencies
COPY composer.json composer.json
COPY composer.lock composer.lock
RUN composer global require hirak/prestissimo --no-plugins --no-scripts
RUN composer install --prefer-dist --no-scripts --no-dev --optimize-autoloader


FROM php:7.2-cli-alpine

# install mysql ext
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copy vendor
COPY --from=build /app/vendor ./usr/src/app/vendor

# Copy codebase
COPY . ./usr/src/app
WORKDIR /usr/src/app

CMD php bin/console count-domains $batch
