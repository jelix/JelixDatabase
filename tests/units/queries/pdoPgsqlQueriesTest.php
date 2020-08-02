<?php
/**
 * @author      Laurent Jouanneau
 * @copyright   2012-2020 Laurent Jouanneau
 * @link        https://jelix.org
 * @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
 */

require_once(__DIR__.'/queriesTestAbstract.php');

use \Jelix\Database\Connection;

class pdoPgsqlQueriesTest extends queriesTestAbstract
{
    protected $connectionInstanceName =  '\\Jelix\\Database\\Connector\\PDO\\Connection';
    protected $recordSetClassName = '\\Jelix\\Database\\Connector\\PDO\\ResultSet';

    protected static $connectionPDOPgsql = null;

    protected function getConnection()
    {
        if (self::$connectionPDOPgsql === null) {
            $parameters = new \Jelix\Database\AccessParameters(array(
                'driver'=>'pgsql',
                'usepdo' => true,
                'host'=>'pgsql',
                'user'=>'jelix',
                'password'=>"jelixpass",
                "database"=>"jelixtests"
            ), array('charset'=>'UTF-8'));


            self::$connectionPDOPgsql = Connection::create($parameters);
        }
        return self::$connectionPDOPgsql;
    }
}
