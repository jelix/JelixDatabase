<?php
/**
 * @author      Florian Lonqueu-Brochard
 * @contributor Laurent Jouanneau
 * @copyright   2012 Florian Lonqueu-Brochard, 2012-2020 Laurent Jouanneau
 * @link        https://jelix.org
 * @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
 */

require_once(__DIR__.'/queriesTestAbstract.php');

use \Jelix\Database\Connection;
use \Jelix\Database\Connector\Mysqli\ResultSet;

class mysqlQueriesTest extends queriesTestAbstract
{
    protected $connectionInstanceName =  '\\Jelix\\Database\\Connector\\Mysqli\\Connection';

    protected function getConnection()
    {
        if (self::$connection === null) {
            $parameters = new \Jelix\Database\AccessParameters(array(
                'driver'=>'mysqli',
                'host'=>'mysql',
                'user'=>'jelix',
                'password'=>"jelixpass",
                "database"=>"jelixtests"
            ), array('charset'=>'UTF-8'));


            self::$connection = Connection::create($parameters->getParameters());
        }
        return self::$connection;
    }

    public function testTransaction()
    {
        //labels_test is an InnoDb table so transaction are supported

        $cnx = $this->getConnection();
        $cnx->exec('DELETE FROM labels_test');

        $this->assertTableIsEmpty('labels_test');
        $cnx->beginTransaction();

        $cnx->exec("INSERT into labels_test ( `key`, `lang` ,`label`) values ( 12, 'fr', 'test1')");

        $this->assertTableIsNotEmpty('labels_test');

        $cnx->rollback();
        $this->assertTableIsEmpty('labels_test');

        $cnx->beginTransaction();

        $cnx->exec("INSERT into labels_test (`key`, `lang` ,`label`) values ( 15, 'en', 'test2')");

        $cnx->commit();
        $this->assertTableIsNotEmpty('labels_test');
    }


    public function testExecMulti()
    {
        $cnx = $this->getConnection();

        $cnx->exec('DELETE FROM labels_test');
        $queries = "INSERT INTO `labels_test` (`key`,`lang` ,`label`) VALUES ('12', 'fr', 'test1');";
        $queries .= "INSERT INTO `labels_test` (`key`,`lang` ,`label`) VALUES ('24', 'en', 'test2');";

        $res = $cnx->execMulti($queries);
        $this->assertTrue($res);
        $this->assertTableHasNRecords('labels_test', 2);
    }


    public function testPreparedQueries()
    {
        $cnx = $this->getConnection();
        $cnx->exec('DELETE FROM labels_test');
        //INSERT
        $stmt = $cnx->prepare('INSERT INTO `labels_test` (`key`,`lang` ,`label`) VALUES (:k, :lg, :lb)');
        $this->assertTrue($stmt instanceof ResultSet);

        $key = 11;
        $lang = 'fr';
        $label = "France";
        $bind = $stmt->bindParam('lg', $lang, 's');
        $bind = $stmt->bindParam('k', $key, 'i');
        $bind = $stmt->bindParam('lb', $label, 's');
        $stmt->execute();

        $key = 15;
        $lang = 'fr';
        $label = "test";
        $bind = $stmt->bindParam('lb', $label, 's');
        $bind = $stmt->bindParam('k', $key, 'i');
        $bind = $stmt->bindParam('lg', $lang, 's');
        $stmt->execute();

        $bind = $stmt->bindValue('k', 22, 'i');
        $bind = $stmt->bindValue('lg', 'en', 's');
        $bind = $stmt->bindValue('lb', 'test2', 's');
        $stmt->execute();

        $this->assertTableHasNRecords('labels_test', 3);
        $stmt = null;

        //SELECT
        $stmt = $cnx->prepare('SELECT `key`,`lang` ,`label` FROM labels_test WHERE lang = :la ORDER BY `key` asc');
        $this->assertTrue($stmt instanceof ResultSet);
        $lang = 'fr';
        $bind = $stmt->bindParam('la', $lang, 's');
        $this->assertTrue($bind);

        $stmt->execute();
        $this->assertEquals(2, $stmt->rowCount());

        $result = $stmt->fetch();
        $this->assertEquals('11', $result->key);
        $this->assertEquals('fr', $result->lang);
        $this->assertEquals('France', $result->label);
    }

    public function testFieldNameEnclosure()
    {
        $this->assertEquals('`toto`', $this->getConnection()->encloseName('toto'));
    }
}
