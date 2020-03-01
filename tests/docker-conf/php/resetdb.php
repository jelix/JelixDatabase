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
    pg_query($cnx, 'drop table if exists jlx_user');
    pg_close($cnx);
}

echo "  tables deleted\n";
*/

echo "Delete all tables from the mysql database\n";
$tryAgain = true;

while($tryAgain) {
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
    $cnx->query('drop table if exists jlx_user');
    $cnx->close();
}

echo "  tables deleted\n";

