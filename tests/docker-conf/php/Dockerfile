ARG php_version=8.1

FROM 3liz/liz-php-cli:${php_version}

ARG php_version
ARG DEBIAN_FRONTEND=noninteractive
ENV PHP_VERSION=${php_version}

# Install drivers to access to SQL server
# see https://docs.microsoft.com/en-us/sql/connect/odbc/linux-mac/installing-the-microsoft-odbc-driver-for-sql-server?view=sql-server-ver15#debian18
# see https://github.com/microsoft/msphpsql/releases
# see https://docs.microsoft.com/en-us/sql/connect/php/installation-tutorial-linux-mac?view=sql-server-ver15#installing-on-debian
RUN  curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add -; \
     curl https://packages.microsoft.com/config/debian/11/prod.list > /etc/apt/sources.list.d/mssql-release.list; \
     apt-get update; \
     ACCEPT_EULA=Y apt-get -y install msodbcsql18 libgssapi-krb5-2; \
     cd /tmp/; \
     wget -O debsqlsrv.tar https://github.com/microsoft/msphpsql/releases/download/v5.10.0/Debian11-${PHP_VERSION}.tar; \
     tar xf debsqlsrv.tar; \
     phpextdir=$(php -i | grep "PHP Extension =" | cut -d "=" -f 2 | sed 's/[> ]//g'); \
     cp  Debian11-${PHP_VERSION}/*.so /usr/lib/php/$phpextdir/; \
     shrinkphpversion=$(echo $PHP_VERSION | sed 's/\.//g'); \
     printf "; priority=20\nextension=php_sqlsrv_${shrinkphpversion}_nts.so\n" > /etc/php/${PHP_VERSION}/mods-available/sqlsrv.ini; \
     printf "; priority=30\nextension=php_pdo_sqlsrv_${shrinkphpversion}_nts.so\n" > /etc/php/${PHP_VERSION}/mods-available/pdo_sqlsrv.ini; \
     phpenmod  -v ${PHP_VERSION} sqlsrv pdo_sqlsrv; \
     rm -rf /tmp/msodbcsql.deb /tmp/debsqlsrv.tar /tmp/Debian11-${PHP_VERSION};

COPY appctl.sh /bin/
RUN chmod 755  /bin/appctl.sh

CMD ["tail", "-f", "/var/log/php_cli_errors.log"]
