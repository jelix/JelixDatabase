<?php
/**
 * @author      Laurent Jouanneau
 * @copyright   2020-2021 Laurent Jouanneau
 *
 * @see        http://jelix.org
 * @licence     http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */

namespace Jelix\Database;

class Connection
{

    /**
     * @param AccessParameters $profile trusted parameters, given by AccessParameters
     * @return ConnectionInterface
     * @throws Exception
     */
    public static function create(AccessParameters $parameters)
    {
        return self::createWithNormalizedParameters($parameters->getNormalizedParameters());
    }

    /**
     * @param array $profile normalized parameters, given by AccessParameters
     * @return ConnectionInterface
     * @throws Exception
     */
    public static function createWithNormalizedParameters(array $profile)
    {
        if ($profile['driver'] == 'pdo' || $profile['usepdo']) {
            return new Connector\PDO\Connection($profile);
        }

        switch ($profile['driver']) {
            case 'mysqli':
                return new Connector\Mysqli\Connection($profile);
            case 'pgsql':
                return new Connector\Postgresql\Connection($profile);
            case 'sqlite3':
                return new Connector\SQLite3\Connection($profile);
            case 'sqlsrv':
                return new Connector\SQLServer\Connection($profile);
            case 'oci':
                return new Connector\Oci\Connection($profile);
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
        switch ($dbType) {
            case self::DB_TYPE_MYSQL:
                $tools = new Schema\Mysql\SQLTools($connection);
                break;
            case self::DB_TYPE_SQLITE:
                $tools = new Schema\Sqlite\SQLTools($connection);
                break;
            case self::DB_TYPE_PGSQL:
                $tools = new Schema\Postgresql\SQLTools($connection);
                break;
            case self::DB_TYPE_SQLSERVER:
                $tools = new Schema\Sqlserver\SQLTools($connection);
                break;
            case self::DB_TYPE_ORACLE:
                $tools = new Schema\Oci\SQLTools($connection);
                break;
            default:
                $tools = null;
                throw new Exception("not implemented");
        }
        return $tools;
    }
}
