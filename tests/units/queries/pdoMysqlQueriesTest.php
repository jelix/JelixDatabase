<?php
/**
 * @author      Laurent Jouanneau
 * @copyright   2012-2020 Laurent Jouanneau
 * @link        https://jelix.org
 * @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
 */

require_once(__DIR__.'/queriesTestAbstract.php');

use \Jelix\Database\Connection;

class pdoMysqlQueriesTest extends queriesTestAbstract
{
    protected $connectionInstanceName =  '\\Jelix\\Database\\Connector\\PDO\\Connection';
    protected $recordSetClassName = '\\Jelix\\Database\\Connector\\PDO\\ResultSet';

    protected static $connectionPDOMysql = null;

    public function setUp() : void
    {
        parent::setUp();
        if (version_compare(PHP_VERSION, '8.1', '>=')) {
            $this->returnFloatType = 'float';
        }
    }

    protected function getConnection()
    {
        if (self::$connectionPDOMysql === null) {
            $parameters = new \Jelix\Database\AccessParameters(array(
                'driver'=>'mysqli',
                'usepdo' => true,
                'host'=>'mysql',
                'user'=>'jelix',
                'password'=>"jelixpass",
                "database"=>"jelixtests"
            ), array('charset'=>'UTF-8'));


            self::$connectionPDOMysql = Connection::create($parameters);
        }
        return self::$connectionPDOMysql;
    }


    public function testFieldNameEnclosure()
    {
        $this->assertEquals('`toto`', $this->getConnection()->encloseName('toto'));
    }
}
