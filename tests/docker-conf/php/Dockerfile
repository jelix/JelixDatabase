FROM debian:buster-slim

ARG composer_version=1.9.3

ENV TEST_USER_ID=1000 \
    TEST_GROUP_ID=1000 \
    PHP_VERSION=7.3

RUN set -eux; \
    echo 'alias ll="ls -al"' > /etc/profile.d/start; \
	apt-get update; \
    apt-get -y install apt-transport-https lsb-release ca-certificates curl wget git unzip; \
    wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg; \
    sh -c 'echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list'; \
    apt-get update;

RUN apt-get -y install \
    php${PHP_VERSION}-cli \
    php${PHP_VERSION}-curl \
    php${PHP_VERSION}-intl \
    php${PHP_VERSION}-json \
    php${PHP_VERSION}-mbstring \
    php${PHP_VERSION}-pgsql \
    php${PHP_VERSION}-sqlite3 \
    php${PHP_VERSION}-mysql \
    php${PHP_VERSION}-xml \
    php${PHP_VERSION}-zip \
    ; apt-get clean

## Install Composer
RUN wget -O /bin/composer https://getcomposer.org/download/$composer_version/composer.phar \
    && chmod +x /bin/composer && /bin/composer self-update

RUN set -eux; \
    sed -i "/^display_errors =/c\display_errors = On" /etc/php/${PHP_VERSION}/cli/php.ini; \
    addgroup --gid $TEST_GROUP_ID grouptest; \
    useradd --uid $TEST_USER_ID --gid $TEST_GROUP_ID usertest;

RUN echo "error_log = /var/log/php_errors.log" >> /etc/php/${PHP_VERSION}/cli/php.ini \
    && touch /var/log/php_errors.log

COPY entrypoint.sh /bin/
COPY appctl.sh /bin/
RUN chmod 755 /bin/entrypoint.sh /bin/appctl.sh

WORKDIR /src/tests/
ENTRYPOINT ["/bin/entrypoint.sh"]
CMD ["tail", "-f", "/var/log/php_errors.log"]