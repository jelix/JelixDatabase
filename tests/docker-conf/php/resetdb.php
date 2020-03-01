<?php
/*
echo "Delete all tables from the postgresql database\n";
$tryAgain = true;

while($tryAgain) {
    $cnx = @pg_connect("host='pgsql' port='5432' dbname='jelixtests' user='jelix' password='jelixpass' ");
    if (!$cnx) {
        echo "  postgresql is not ready yet\n";
        sleep(1);
        continue;
    }
    $tryAgain = false;
    $cnx->query('drop table if exists products');
    $cnx->query('drop table if exists product_test');
    $cnx->query('drop table if exists labels_test');
    pg_close($cnx);
}

echo "  tables deleted\n";
*/

echo "Delete all tables from the mysql database\n";
$tryAgain = true;

while ($tryAgain) {
    $cnx = @new mysqli("mysql", "jelix", 'jelixpass', 'jelixtests');
    if ($cnx->connect_errno) {
        throw new Exception('Error during the connection on mysql '.$cnx->connect_errno);
    }
    /*if (!$cnx) {
        echo "  postgresql is not ready yet\n";
        sleep(1);
        continue;
    }*/
    $tryAgain = false;
    $cnx->query('drop table if exists products');
    $cnx->query('drop table if exists product_test');
    $cnx->query('drop table if exists labels_test');

    $cnx->query("CREATE TABLE IF NOT EXISTS `product_test` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`name` VARCHAR( 150 ) NOT NULL ,
`price` FLOAT NOT NULL,
`create_date` datetime default NULL,
`promo` BOOL NOT NULL default 0,
`dummy` set('created','started','stopped') DEFAULT NULL
) ENGINE = InnoDB");

    $cnx->query("CREATE TABLE IF NOT EXISTS `labels_test` (
`key` INT NOT NULL ,
`keyalias` VARCHAR( 10 ) NULL,
`lang` VARCHAR( 5 ) NOT NULL ,
`label` VARCHAR( 50 ) NOT NULL ,
PRIMARY KEY ( `key` , `lang` ),
UNIQUE (`keyalias`)
) ENGINE=InnoDb");

    $cnx->query("CREATE TABLE IF NOT EXISTS `products` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`name` VARCHAR( 150 ) NOT NULL ,
`price` FLOAT   default '0',
`promo` BOOL NOT NULL,
`publish_date` DATE NOT NULL
) ENGINE = InnoDb");

    $cnx->close();
}

echo "  tables deleted\n";
