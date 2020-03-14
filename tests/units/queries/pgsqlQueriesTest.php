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


class pgsqlQueriesTest extends queriesTestAbstract {

    protected $connectionInstanceName =  '\\Jelix\\Database\\Connector\\Postgresql\\Connection';
    protected $recordSetClassName = '\\Jelix\\Database\\Connector\\Postgresql\\ResultSet';

    protected static $connectionPgsql = null;

    protected function getConnection()
    {
        if (self::$connectionPgsql === null) {
            $parameters = new \Jelix\Database\AccessParameters(array(
                'driver'=>'pgsql',
                'host'=>'pgsql',
                'user'=>'jelix',
                'password'=>"jelixpass",
                "database"=>"jelixtests"
            ), array('charset'=>'UTF-8'));

            self::$connectionPgsql = Connection::create($parameters->getParameters());
        }
        return self::$connectionPgsql;
    }

    function testVersion() {

        $cnx = $this->getConnection();
        $version = $cnx->getAttribute($cnx::ATTR_CLIENT_VERSION);
        $this->assertNotEquals('', $version);
    }
}
