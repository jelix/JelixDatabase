<?php
/**
 * @author      Laurent Jouanneau
 * @copyright   2015-2025 Laurent Jouanneau
 *
 * @see         https://jelix.org
 * @licence     http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */

namespace Jelix\Database;

/**
 * Normalize & analyse database parameters, so connector don't have to check
 * list of parameters and their type.
 *
 * This class is useful to create a cache content of parameters coming from
 * a configuration file (like parameters given by Jelix\Profiles), and so there
 * are less checks during the runtime, at each http requests.
 *
 * Supported parameters in a profile:
 *  - driver: the jdb driver name, or "pdo" to use dbo ("pdo" value is deprecated, use usepdo instead)
 *  - database: the name of the database (for sqlite: path to the sqlite file)
 *  - host: the host of the database
 *  - port: the port of the database
 *  - user & password: credentials to connect to the database
 *  - force_encoding: force the encoding at the connection, using the default encoding of the application
 *  - dsn: dsn (pdo, odbc... optional)
 *  - usepdo: true if pdo should be used
 *  - pdodriver: name of the pdodriver to use. Guessed from dsn
 *  - pdoext: name of the pdo extension to use. Guessed from dsn
 *  - dbtype: type of the database (so it determines the SQL language)
 *  - phpext: name of the php extension to use
 *  - persistent: if true, the connection should be persistent
 *  - extensions: some information about extensions to load (For sqlite for example. optional)
 *  - single_transaction: indicate to execute all queries into a single transaction (pgsql, optional)
 *  - busytimeout: timeout for the connection (sqlite, optional)
 *  - timeout: timeout for the connection (pgsql, optional)
 *  - search_path: schema for pgsql (optional)
 *  - table_prefix: prefix to add to database table. Used by jDao (optional)
 */
class AccessParameters
{
    protected $parameters = array();

    /**
     * the constructor normalizes parameters: it ensure that all parameters needed by
     * jDb and the targeted database type are there, as some parameters can be
     * "guessed" by jDb or needed for internal use.
     *
     * @param array $profileParameters profile parameters for a jdb connection
     *                                 required keys: driver
     *                                 optional keys: dsn, host, username, password, database,....
     * @param array $options  some options:
     *      'charset': the charset code to use for the connection
     *      'filePathParser': a parser (callable) of file path (path to a sqlite database for example), in case
     *      the file path may content some protocole path to access to the real file.
     */
    public function __construct($profileParameters, $options = [])
    {
        $this->parameters = $profileParameters;

        $this->normalizeBoolean($this->parameters, 'usepdo');
        $this->normalizeBoolean($this->parameters, 'persistent');
        $this->normalizeBoolean($this->parameters, 'force_encoding');
        if (!isset($this->parameters['table_prefix'])) {
            $this->parameters['table_prefix'] = '';
        }

        $info = $this->getDatabaseInfo($this->parameters);
        $this->parameters = array_merge($this->parameters, $info);

        if ($this->parameters['usepdo'] &&
            (!isset($this->parameters['dsn']) ||
                $this->parameters['dsn'] == '')) {
            $this->parameters['dsn'] = $this->getPDODsn($this->parameters);
        }

        if (!isset($this->parameters['pdooptions'])) {
            $this->parameters['pdooptions'] = '';
        }

        $this->parameters['charset'] = isset($options['charset']) ? $options['charset']: 'UTF-8';
        $this->parameters['filePathParser'] = isset($options['filePathParser']) ? $options['filePathParser']: '';

        if (!isset($this->parameters['_name'])) {
            $this->parameters['_name'] = '';
        }
    }

    /**
     * Return normalized parameters ready to be used with the Connection object
     * @return array list of normalized parameters
     */
    public function getNormalizedParameters()
    {
        return $this->parameters;
    }

    /**
     * @deprecated
     * @see getNormalizedParameters()
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * indicate if the php extension corresponding to the database configuration is available in
     * the current php configuration.
     */
    public function isExtensionActivated()
    {
        if ($this->parameters['usepdo']) {
            return extension_loaded('PDO') && extension_loaded($this->parameters['pdoext']);
        }
        if (isset($this->parameters['phpext'])) {
            return extension_loaded($this->parameters['phpext']);
        }

        throw new Exception('Unable to check existence of the extension corresponding to jdb driver '.$this->parameters['driver']);
    }

    /**
     * it gives the name of the jDb driver and the database type indicated in a profile.
     * (or corresponding to the PDO dsn indicated in the profile).
     *
     * @param array $profile 'driver' key is required. It should indicates 'pdo' or a jdb driver.
     *                       if 'pdo', a 'dsn' key is required.
     *
     * @throws Exception
     *
     * @return array ['database type', 'native extension name', 'pdo extension name', 'jdb driver name', 'pdo driver name']
     */
    protected function getDatabaseInfo($profile)
    {
        $info = null;
        if (!isset($profile['driver']) || $profile['driver'] == '') {
            throw new Exception('jDb profile: driver is missing');
        }
        // driver = "pdo"
        if ($profile['driver'] == 'pdo') {
            $usepdo = true;
            $pdoDriver = '';
            if (isset($profile['dsn'])) {
                $pdoDriver = substr($profile['dsn'], 0, strpos($profile['dsn'], ':'));
            } elseif (isset($profile['pdodriver'])) {
                $pdoDriver = $profile['pdodriver'];
            }

            if (!$pdoDriver) {
                throw new Exception('PDO profile: dsn is missing or mal-formed');
            }

            if (isset(self::$PDODriverIndex[$pdoDriver])) {
                $info = self::$driversInfos[self::$PDODriverIndex[$pdoDriver]];
            } else {
                throw new Exception('Unknown pdo driver ('.$pdoDriver.')');
            }
        }
        // driver = jdb driver name
        else {
            $usepdo = $profile['usepdo'];
            $driver = $profile['driver'];
            if (isset(self::$JdbDriverIndex[$driver])) {
                $info = self::$driversInfos[self::$JdbDriverIndex[$driver]];
            } else {
                $info = array('', '', '', $driver, '');
                $info[0] = (isset($profile['dbtype']) ? $profile['dbtype'] : '');
                $info[1] = (isset($profile['phpext']) ? $profile['phpext'] : '');
            }
        }

        $info = array_combine(array('dbtype', 'phpext', 'pdoext',
            'driver', 'pdodriver', ), $info);

        $info['usepdo'] = $usepdo;

        return $info;
    }

    protected function normalizeBoolean(&$profile, $param)
    {
        if (!isset($profile[$param])) {
            $profile[$param] = false;
        } elseif (!is_bool($profile[$param])) {
            if ($profile[$param] === '1' ||
                $profile[$param] === 1 ||
                $profile[$param] === 'on' ||
                $profile[$param] === 'true') {
                $profile[$param] = true;
            } else {
                $profile[$param] = false;
            }
        }
    }

    protected static $JdbDriverIndex = array(
        'mysqli' => 0,
        'mysql' => 1,
        'pgsql' => 2,
        'sqlite3' => 3,
        'sqlite' => 4,
        'oci' => 5,
        'mssql' => 6,
        'sqlsrv' => 7,
        'sybase' => 9,
        'odbc' => 10,
    );

    protected static $PDODriverIndex = array(
        'mysql' => 0,
        'pgsql' => 2,
        'sqlite' => 3,
        'sqlite2' => 4,
        'oci' => 5,
        'sqlsrv' => 7,
        'odbc' => 10,
        'mssql' => 6,
        'sybase' => 8,
    );

    /**
     * informations about correspondence between pdo driver and native driver, type of function etc...
     */
    protected static $driversInfos = array(
        //array('database type', 'native extension name', 'pdo extension name', 'jdb driver name', 'pdo driver name')
        0 => array('mysql',  'mysqli',    'pdo_mysql',   'mysqli',  'mysql'),
        1 => array('mysql',  'mysql',     'pdo_mysql',   'mysql',   'mysql'),
        2 => array('pgsql',  'pgsql',     'pdo_pgsql',   'pgsql',   'pgsql'), // pgsql:host=;port=;dbname=;user=;password=;
        3 => array('sqlite', 'sqlite3',   'pdo_sqlite',  'sqlite3', 'sqlite'),
        4 => array('sqlite', 'sqlite',    'pdo_sqlite2', 'sqlite',  'sqlite2'),
        5 => array('oci',    'oci8',      'pdo_oci',     'oci',     'oci'), // experimental  oci:dbname=tnsname  oci://localhost:1521/mydb
        6 => array('mssql',  'mssql',     'pdo_dblib',   'mssql',   'mssql'),     // deprecated since PHP 5.3
        7 => array('sqlsrv',  'sqlsrv',    'pdo_sqlsrv',  'sqlsrv',  'sqlsrv'), //mssql 2005+ sqlsrv:Server=localhost,port;Database=
        8 => array('sybase', 'sybase',    'pdo_dblib',   'sybase',  'sybase'), // deprecated
        9 => array('sybase', 'sybase_ct', 'pdo_dblib',   'sybase',  'sybase'),
        10 => array('odbc',  'odbc',      'pdo_odbc',    'odbc',    'odbc'), // odbc:DSN
    );

    protected static $pdoNeededDsnInfo = array(
        'mysql' => array('host', 'database'),
        'pgsql' => array(array('host', 'database'), array('service')),
        'sqlite' => array('database'),
        'sqlite2' => array('database'),
        'oci' => array('database'),
        'sqlsrv' => array('host', 'database'),
        'odbc' => array('dsn'),
        'mssql' => array('host', 'database'),
        'sybase' => array('host', 'database'),
    );

    public static function getDriversInfosList()
    {
        return self::$driversInfos;
    }

    protected function _checkRequirements($requirements, &$profile)
    {
        foreach ($requirements as $param) {
            if (!isset($profile[$param])) {
                throw new Exception('Parameter '.$param.' is required for pdo driver '.$profile['pdodriver']);
            }
        }
    }

    protected function getPDODsn($profile)
    {
        if (!isset(self::$pdoNeededDsnInfo[$profile['pdodriver']])) {
            throw new Exception('PDO does not support database '.$profile['dbtype']);
        }
        $requirements = self::$pdoNeededDsnInfo[$profile['pdodriver']];
        if (is_array($requirements[0])) {
            $error = null;
            foreach ($requirements as $requirements2) {
                try {
                    $this->_checkRequirements($requirements2, $profile);
                    $error = null;

                    break;
                } catch (Exception $e) {
                    $error = $e;
                }
            }
            if ($error) {
                throw $error;
            }
        } else {
            $this->_checkRequirements($requirements, $profile);
        }

        switch ($profile['pdodriver']) {
            case 'mysql':
                $dsn = 'mysql:';
                if (isset($profile['unix_socket'])) {
                    $dsn .= 'unix_socket='.$profile['unix_socket'];
                } else {
                    $dsn .= 'host='.$profile['host'];
                    if (isset($profile['port'])) {
                        $dsn .= ';port='.$profile['port'];
                    }
                }
                $dsn .= ';dbname='.$profile['database'];

                break;
            case 'pgsql':
                if (isset($profile['service']) && $profile['service']) {
                    $dsn = 'pgsql:service='.$profile['service'];
                } else {
                    $dsn = 'pgsql:host='.$profile['host'];
                    if (isset($profile['port'])) {
                        $dsn .= ';port='.$profile['port'];
                    }
                    $dsn .= ';dbname='.$profile['database'];
                    if (isset($profile['pg_options']) && $profile['pg_options']) {
                        $dsn .= ';options='.$profile['pg_options'];
                    }
                }

                break;
            case 'sqlite':
                $dsn = 'sqlite:'.$profile['database'];

                break;
            case 'sqlite2':
                $dsn = 'sqlite2:'.$profile['database'];

                break;
            case 'oci':
                $dsn = 'oci:dbname=';
                if (isset($profile['host'])) {
                    $dsn .= $profile['host'];
                    if (isset($profile['port'])) {
                        $dsn .= ':'.$profile['port'];
                    }
                    $dsn .= '/';
                }
                $dsn .= $profile['database'];

                break;
            case 'mssql':
            case 'sybase':
                $dsn = $profile['pdodriver'].':';
                $dsn .= 'host='.$profile['host'];
                $dsn .= ';dbname='.$profile['database'];
                if (isset($profile['appname'])) {
                    $dsn .= ';appname='.$profile['appname'];
                }

                break;
            case 'sqlsrv':
                $dsn = 'sqlsrv:Server='.$profile['host'];
                if (isset($profile['port'])) {
                    $dsn .= ','.$profile['port'];
                }
                $dsn .= ';Database='.$profile['database'];

                break;
            case 'odbc':
            default:
                throw new Exception('Cannot construct the DSN string for PDO');

                break;
        }

        return $dsn;
    }
}
