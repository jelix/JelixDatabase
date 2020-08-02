<?php
/**
 * @author      Laurent Jouanneau
 * @contributor Julien Issler
 * @copyright   2007-2020 Laurent Jouanneau
 * @copyright   2009 Julien Issler
 * @link        https://jelix.org
 * @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
 */
require_once(__DIR__.'/queriesTestAbstract.php');

use \Jelix\Database\Connection;


class sqlserverQueriesTest extends queriesTestAbstract {

    protected $connectionInstanceName =  '\\Jelix\\Database\\Connector\\SQLServer\\Connection';
    protected $recordSetClassName = '\\Jelix\\Database\\Connector\\SQLServer\\ResultSet';
    protected $returnFloatType = 'float';
    protected $returnIntType = 'int';

    protected static $connectionSqlserver = null;

    protected function getConnection()
    {
        if (self::$connectionSqlserver === null) {
            $parameters = new \Jelix\Database\AccessParameters(array(
                'driver'=>'sqlsrv',
                'host'=>'sqlsrv',
                'user'=>'SA',
                'password'=>"JelixPass2020!",
                "database"=>"jelixtests"
            ), array('charset'=>'UTF-8'));

            self::$connectionSqlserver = Connection::create($parameters);
        }
        return self::$connectionSqlserver;
    }
}
