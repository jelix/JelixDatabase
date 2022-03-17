<?php
/**
 * @author      Laurent Jouanneau
 * @copyright   2012-2020 Laurent Jouanneau
 * @link        https://jelix.org
 * @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
 */

require_once(__DIR__.'/queriesTestAbstract.php');

use \Jelix\Database\Connection;

class pdoSqliteQueriesTest extends queriesTestAbstract
{
    protected $connectionInstanceName =  '\\Jelix\\Database\\Connector\\PDO\\Connection';
    protected $recordSetClassName = '\\Jelix\\Database\\Connector\\PDO\\ResultSet';

    protected static $connectionPDOSqlite = null;

    public function setUp() : void
    {
        parent::setUp();
        if (version_compare(PHP_VERSION, '8.1', '>=')) {
            $this->returnFloatType = 'float';
        }
    }



    protected function getConnection()
    {
        if (self::$connectionPDOSqlite === null) {
            $parameters = new \Jelix\Database\AccessParameters(array(
                'driver'=>'sqlite3',
                "database"=>"/app/tests/units/tests.sqlite3",
                'usepdo' => true
            ), array('charset'=>'UTF-8'));


            self::$connectionPDOSqlite = Connection::create($parameters);
        }
        return self::$connectionPDOSqlite;
    }
}
