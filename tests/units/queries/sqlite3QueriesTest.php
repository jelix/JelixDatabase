<?php
/**
 * @author      Laurent Jouanneau
 * @copyright   2006-2020 Laurent Jouanneau
 * @link        https://jelix.org
 * @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
 */

require_once(__DIR__.'/queriesTestAbstract.php');

use \Jelix\Database\Connection;

class sqlite3QueriesTest extends queriesTestAbstract
{
    protected $connectionInstanceName =  '\\Jelix\\Database\\Connector\\SQLite3\\Connection';
    protected $recordSetClassName = '\\Jelix\\Database\\Connector\\SQLite3\\ResultSet';

    protected static $connectionSqlite = null;

    protected function getConnection()
    {
        if (self::$connectionSqlite === null) {
            $parameters = new \Jelix\Database\AccessParameters(array(
                'driver'=>'sqlite3',
                "database"=>"/src/tests/tests/units/tests.sqlite3"
            ), array('charset'=>'UTF-8'));

            self::$connectionSqlite = Connection::create($parameters->getParameters());
        }
        return self::$connectionSqlite;
    }
}
