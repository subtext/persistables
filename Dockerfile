FROM php:8.4-cli

ARG TARGETARCH
RUN cp /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini
RUN curl -sSLf -o /usr/local/bin/install-php-extensions \
https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions \
&& chmod +x /usr/local/bin/install-php-extensions \
&& install-php-extensions @composer intl mbstring pdo_mysql xdebug zip

