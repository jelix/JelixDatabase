<?php
/**
 * @author      Laurent Jouanneau
 * @copyright   2020 Laurent Jouanneau
 *
 * @see        http://jelix.org
 * @licence     http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */

namespace Jelix\Database;

use Psr\Log\LoggerInterface;

class Connection
{

    /**
     * @param array $profile trusted parameters, given by AccessParameters
     * @param LoggerInterface|null $logger
     * @return ConnectionInterface
     * @throws Exception
     */
    public static function create($profile, LoggerInterface $logger = null)
    {

        if ($profile['driver'] == 'pdo' || $profile['usepdo']) {
            return new Connector\PDO\Connection($profile, $logger);
        }

        switch ($profile['driver']) {
            case 'mysqli':
                return new Connector\Mysqli\Connection($profile, $logger);
            case 'pgsql':
                return new Connector\Postgresql\Connection($profile, $logger);
            case 'sqlite3':
                return new Connector\SQLite3\Connection($profile, $logger);
            /*case 'sqlsrv':
                return new Connector\SqlServer\Connection($profile, $logger);
            case 'oci':
                return new Connector\OCI\Connection($profile, $logger);*/
        }
        throw new Exception('Unknown connector: '.$profile['driver']);
    }


    const DB_TYPE_MYSQL = 'mysql';
    const DB_TYPE_SQLITE = 'sqlite';
    const DB_TYPE_PGSQL = 'pgsql';
    const DB_TYPE_SQLSERVER = 'sqlsrv';
    const DB_TYPE_ORACLE = 'oci';

    public static function getTools($dbType, $connection = null)
    {
        throw new Exception("not implemented");
        /*switch($dbType) {
            case self::DB_TYPE_MYSQL:
                $tools = new Tools\MysqlTools($connection);
                break;
            case self::DB_TYPE_SQLITE:
                $tools = new Tools\SqliteTools($connection);
                break;
            case self::DB_TYPE_PGSQL:
                $tools = new Tools\PgsqlTools($connection);
                break;
            case self::DB_TYPE_SQLSERVER:
                $tools = new Tools\SqlsrvTools($connection);
                break;
            case self::DB_TYPE_ORACLE:
                $tools = new Tools\OciTools($connection);
                break;
            default:
                $tools = null;
        }
        return $tools;*/
    }
}
