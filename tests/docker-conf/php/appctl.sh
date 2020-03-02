#!/bin/bash
ROOTDIR="/src/tests"
APP_USER=usertest
APP_GROUP=grouptest

COMMAND="$1"

if [ "$COMMAND" == "" ]; then
    echo "Error: command is missing"
    exit 1;
fi

function composerInstall() {

    if [ -f $ROOTDIR/composer.lock ]; then
        rm -f $ROOTDIR/composer.lock
    fi
    composer install --prefer-dist --no-progress --no-ansi --no-interaction --working-dir=$ROOTDIR/
    chown -R $APP_USER:$APP_GROUP $ROOTDIR/vendor $ROOTDIR/composer.lock
}

function composerUpdate() {
    composer update --prefer-dist --no-progress --no-ansi --no-interaction --working-dir=$ROOTDIR/
    chown -R $APP_USER:$APP_GROUP $ROOTDIR/vendor $ROOTDIR/composer.lock
}

function launchUnitTests() {
    su $APP_USER -c "cd $ROOTDIR/tests/units/ && ../../vendor/bin/phpunit"
}

function reset() {
    php $ROOTDIR/tests/docker-conf/php/resetdb.php
    chown $APP_USER:$APP_GROUP $ROOTDIR/tests/units/tests.sqlite3
}


case $COMMAND in
    reset)
        reset
        ;;
    composer-install)
        composerInstall;;
    composer-update)
        composerUpdate;;
    unit-tests)
        launchUnitTests;;
    *)
        echo "appctl.sh: wrong command"
        exit 2
        ;;
esac

