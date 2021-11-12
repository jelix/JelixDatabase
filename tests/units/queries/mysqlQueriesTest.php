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
    protected $recordSetClassName =  '\\Jelix\\Database\\Connector\\Mysqli\\ResultSet';

    protected static $connectionMysql = null;

    protected function getConnection()
    {
        if (self::$connectionMysql === null) {
            $parameters = new \Jelix\Database\AccessParameters(array(
                'driver'=>'mysqli',
                'host'=>'mysql',
                'user'=>'jelix',
                'password'=>"jelixpass",
                "database"=>"jelixtests"
            ), array('charset'=>'UTF-8'));


            self::$connectionMysql = Connection::create($parameters);
        }
        return self::$connectionMysql;
    }

    public function testExecMulti()
    {
        $cnx = $this->getConnection();

        $cnx->exec('DELETE FROM labels_test');
        $queries = "INSERT INTO `labels_test` (`key`,`lang` ,`label`) VALUES ('12', 'fr', 'test1');";
        $queries .= "INSERT INTO `labels_test` (`key`,`lang` ,`label`) VALUES ('24', 'en', 'test2');";

        $res = $cnx->execMulti($queries);
        $this->assertEquals(2, $res);
        $this->assertTableHasNRecords('labels_test', 2);
    }

    public function testFieldNameEnclosure()
    {
        $this->assertEquals('`toto`', $this->getConnection()->encloseName('toto'));
    }
}
