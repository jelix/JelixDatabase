FROM debian:buster-slim

ARG composer_version=1.9.3

ENV TEST_USER_ID=1000 \
    TEST_GROUP_ID=1000 \
    PHP_VERSION=7.3

RUN set -eux; \
    echo 'alias ll="ls -al"' > /etc/profile.d/start; \
	apt-get update; \
    apt-get -y install apt-transport-https lsb-release ca-certificates curl wget git unzip gnupg2; \
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

# Install drivers to access to SQL server
# see https://docs.microsoft.com/en-us/sql/connect/odbc/linux-mac/installing-the-microsoft-odbc-driver-for-sql-server?view=sql-server-ver15#debian17
# see https://github.com/microsoft/msphpsql/releases
RUN  curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add -; \
     curl https://packages.microsoft.com/config/debian/10/prod.list > /etc/apt/sources.list.d/mssql-release.list; \
     apt-get update; \
     ACCEPT_EULA=Y apt-get -y install msodbcsql17 libgssapi-krb5-2; \
     cd /tmp/; \
     wget -O debsqlsrv.tar https://github.com/microsoft/msphpsql/releases/download/v5.8.0/Debian10-${PHP_VERSION}.tar; \
     tar xf debsqlsrv.tar; \
     cp  Debian10-${PHP_VERSION}/*_nts.so /usr/lib/php/20180731/; \
     printf "; priority=20\nextension=php_sqlsrv_73_nts.so\n" > /etc/php/${PHP_VERSION}/mods-available/sqlsrv.ini; \
     printf "; priority=30\nextension=php_pdo_sqlsrv_73_nts.so\n" > /etc/php/${PHP_VERSION}/mods-available/pdo_sqlsrv.ini; \
     phpenmod sqlsrv; \
     phpenmod pdo_sqlsrv; \
     rm -rf /tmp/msodbcsql.deb /tmp/debsqlsrv.tar /tmp/Debian10-${PHP_VERSION};

COPY entrypoint.sh /bin/
COPY appctl.sh /bin/
RUN chmod 755 /bin/entrypoint.sh /bin/appctl.sh

WORKDIR /src/tests/
ENTRYPOINT ["/bin/entrypoint.sh"]
CMD ["tail", "-f", "/var/log/php_errors.log"]