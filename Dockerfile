FROM heroku/php

RUN apt-get update  && apt-get upgrade && apt-get install --yes --no-install-recommends \
    libssl-dev
RUN pecl upgrade-all && pecl channel-update pecl.php.net && pecl install xdebug && pecl install mongodb && pecl install mongo && docker-php-ext-enable mongodb

RUN echo "zend_extension=$(find /app/.heroku/php/ -name xdebug.so)" > /app/.heroku/php/etc/php/php.ini \
    && echo "xdebug.remote_enable=on" >> /app/.heroku/php/etc/php/php.ini \
    && echo "xdebug.remote_autostart=off" >> /app/.heroku/php/etc/php/php.ini \
    #&& echo "xdebug.remote_connect_back = 1" >> /app/.heroku/php/etc/php/php.ini \
    && echo "extension=mongo.so" >> /app/.heroku/php/etc/php/php.ini \
    && echo "extension=mongodb.so" >> /app/.heroku/php/etc/php/php.ini
    
COPY composer.lock /app/user/
COPY composer.json /app/user/
RUN composer install && composer update