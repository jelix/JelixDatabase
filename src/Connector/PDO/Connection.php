<?php
/**
 * @author     Laurent Jouanneau
 * @contributor Gwendal Jouannic, Thomas, Julien Issler, Vincent Herr
 *
 * @copyright  2005-2020 Laurent Jouanneau, 2008 Gwendal Jouannic, 2009 Thomas, 2009 Julien Issler, 2011 Vincent Herr
 *
 * @see      https://jelix.org
 * @licence  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */
namespace Jelix\Database\Connector\PDO;

use Jelix\Database\Log\QueryMessage;
use Jelix\Database\ResultSetInterface;
use Psr\Log\LoggerInterface;
use Jelix\Database\Exception;


/**
 * A connection object based on PDO.
 *
 * @package  jelix
 * @subpackage db
 */
class Connection extends \PDO implements \Jelix\Database\ConnectionInterface
{

    /**
     * profile properties used by the connector.
     *
     * @var array
     */
    protected $_profile;

    /**
     * The database type name (mysql, pgsql ...)
     * It is not the driver name. Several drivers could connect to the same database
     * type. This type name is often used to know whish SQL language we should use.
     *
     * @var string
     */
    protected $_dbms;

    /**
     * driver name.
     *
     * @var string
     */
    protected $_driverName;

    /**
     * last executed query.
     */
    protected $_lastQuery;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    private $_pgsqlCharsets = array('UTF-8' => 'UNICODE', 'ISO-8859-1' => 'LATIN1');


    protected $_debugMode = false;

    /**
     * Use a profile to do the connection.
     *
     * @param array $profile the profile data. Its content must be normalized by AccessParameters
     */
    public function __construct($profile, LoggerInterface $logger = null)
    {
        $this->_profile = $profile;
        $this->_dbms = $profile['dbtype'];
        $this->_driverName = $profile['driver'];

        $user = '';
        $password = '';

        $dsn = $profile['dsn'];
        if ($this->_dbms == 'sqlite') {
            $path = substr($dsn, 7);
            if ($this->_profile['filePathParser']) {
                $path = call_user_func_array($this->_profile['filePathParser'], array($path));
            }

            $dsn = 'sqlite:'.$path;
        }

        // we check user and password because some db like sqlite doesn't have user/password
        if (isset($profile['user'])) {
            $user = $profile['user'];
        }

        if (isset($profile['password'])) {
            $password = $profile['password'];
        }

        $pdoOptions = array();
        if ($profile['pdooptions'] != '') {
            foreach (explode(',', $profile['pdooptions']) as $optname) {
                $pdoOptions[$optname] = $profile[$optname];
            }
        }

        $initsql = '';
        if ($profile['force_encoding']) {
            $pdoDriver = $profile['pdodriver'];
            $charset = $profile['charset'];
            if ($pdoDriver == 'mysql' ||
                $pdoDriver == 'mssql' ||
                $pdoDriver == 'sybase' ||
                $pdoDriver == 'oci') {
                $dsn .= ';charset='.$charset;
            } elseif ($this->_dbms == 'pgsql' && isset($this->_pgsqlCharsets[$charset])) {
                $initsql = "SET client_encoding to '".$this->_pgsqlCharsets[$charset]."'";
            }
        }

        $this->_debugMode = (isset($profile['debug']) && $profile['debug']);
        if ($this->_debugMode) {
            if ($logger === null) {
                $logger = new \Psr\Log\NullLogger();
            }
            $this->logger = $logger;
        }

        parent::__construct($dsn, $user, $password, $pdoOptions);

        $this->setAttribute(\PDO::ATTR_STATEMENT_CLASS, array('\\Jelix\\Database\\Connector\\PDO\\ResultSet'));
        $this->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        // we cannot launch two queries at the same time with PDO ! except if
        // we use mysql with the attribute MYSQL_ATTR_USE_BUFFERED_QUERY
        // TODO check if PHP 5.3 or higher fixes this issue
        if ($this->_dbms == 'mysql') {
            $this->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        }

        // Oracle returns names of columns in upper case by default. so here
        // we force the case in lower.
        if ($this->_dbms == 'oci') {
            $this->setAttribute(\PDO::ATTR_CASE, \PDO::CASE_LOWER);
        }

        if ($initsql) {
            $this->exec($initsql);
        }
    }

    /**
     * Support of old previous public properties to keep compatibility with Jelix 1.x
     * @param string $name
     * @deprecated
     */
    public function __get($name)
    {
        switch ($name) {
            case 'driverName':
                return $this->_driverName;
            case 'dbms':
                return $this->_dbms;
            case 'profile':
                return $this->_profile;
            case 'lastQuery':
                return $this->_lastQuery;
        }
        return null;
    }


    public function getProfileName() {
        return $this->_profile['_name'];
    }

    /**
     * The SQL language using by the database.
     * It is not the driver name. Several drivers could connect to the same database
     * type. This type name is often used to know whish SQL language we should use.
     *
     * @var string
     */
    public function getSQLType()
    {
        return $this->_dbms;
    }

    /**
     * Launch a SQL Query which returns rows (typically, a SELECT statement).
     *
     * @param string        $queryString the SQL query
     * @param int           $fetchmode   FETCH_OBJ, FETCH_CLASS or FETCH_INTO
     * @param object|string $param       class name if FETCH_CLASS, an object if FETCH_INTO. else null.
     * @param array         $ctoargs     arguments for the constructor if FETCH_CLASS
     * @param null|mixed    $arg1
     *
     * @return bool|ResultSetInterface false if the query has failed
     *
     * @internal the implementation of Iterator on PDOStatement doesn't call
     * fetch method of classes which inherit of PDOStatement.
     * so, we cannot indicate to fetch object directly in jDbPDOResultSet::fetch().
     * So we overload query() to do it.
     * TODO check if this is still the case in PHP 5.3
     */
    public function query($queryString, $fetchmode = self::FETCH_OBJ, $arg1 = null, $ctoargs = null)
    {
        $this->_lastQuery = $queryString;
        if ($this->_debugMode) {
            $log = new QueryMessage($queryString);
        }
        if ($ctoargs !== null) {
            $result = parent::query($queryString, $fetchmode, $arg1, $ctoargs);
        }
        else if ($arg1 !== null) {
            $result =  parent::query($queryString, $fetchmode, $arg1);
        }
        else if ($fetchmode != self::FETCH_OBJ) {
            $result = parent::query($queryString, $fetchmode);
        }
        else {
            $result = parent::query($queryString);
            if ($result) {
                $result->setFetchMode(\PDO::FETCH_OBJ);
            }
        }

        if ($this->_debugMode) {
            $log->endQuery();
            $this->logger->debug($log);
        }

        return $result;
    }

    public function exec($query)
    {
        if ($this->_debugMode) {
            $log = new QueryMessage($query);
            $result = parent::exec($query);
            $log->endQuery();
            $this->logger->debug($log);
        }
        else {
            $result = parent::exec($query);
        }

        return $result;
    }

    /**
     * Launch a SQL Query with limit parameter (so only a subset of a result).
     *
     * @param string $queryString the SQL query
     * @param int    $limitOffset the offset of the first row to return
     * @param int    $limitCount  the maximum of number of rows to return
     *
     * @return bool|ResultSetInterface SQL Select. False if the query has failed.
     */
    public function limitQuery($queryString, $limitOffset, $limitCount)
    {
        $this->_lastQuery = $queryString;
        if ($this->_debugMode) {
            $log = new QueryMessage($queryString);
        }

        if ($this->_dbms == 'mysql' || $this->_dbms == 'sqlite') {
            $queryString .= ' LIMIT '.intval($limitOffset).','.intval($limitCount);
        } elseif ($this->_dbms == 'pgsql') {
            $queryString .= ' LIMIT '.intval($limitCount).' OFFSET '.intval($limitOffset);
        } elseif ($this->_dbms == 'oci') {
            $limitOffset = $limitOffset + 1; // rnum begins at 1
            $queryString = 'SELECT * FROM ( SELECT ocilimit.*, rownum rnum FROM ('.$queryString.') ocilimit WHERE
                rownum<'.(intval($limitOffset) + intval($limitCount)).'  ) WHERE rnum >='.intval($limitOffset);
        } elseif ($this->_dbms == 'sqlsrv') {
            $queryString = $this->limitQuerySqlsrv($queryString, $limitOffset, $limitCount);
        }

        $result = $this->query($queryString);
        if ($this->_debugMode) {
            $this->_lastQuery = $queryString;
            $log->endQuery();
            $log->setRealQuery($queryString);
            $this->logger->debug($log);
        }
        return $result;
    }

    /**
     * Create a limitQuery for the SQL server dbms.
     *
     * @param string $queryString the SQL query
     * @param int    $limitOffset the offset of the first row to return
     * @param int    $limitCount  the maximum of number of rows to return
     *
     * @return string SQL Select
     */
    protected function limitQuerySqlsrv($queryString, $limitOffset = null, $limitCount = null)
    {
        // we suppress existing 'TOP XX'
        $queryString = preg_replace('/^SELECT TOP[ ]\d*\s*/i', 'SELECT ', trim($queryString));

        $distinct = false;

        // we retrieve the select part and the from part
        list($select, $from) = preg_split('/\sFROM\s/mi', $queryString, 2);

        $fields = preg_split('/\s*,\s*/', $select);
        $firstField = preg_replace('/^\s*SELECT\s+/', '', array_shift($fields));

        // is there a distinct?
        if (stripos($firstField, 'DISTINCT') !== false) {
            $firstField = preg_replace('/DISTINCT/i', '', $firstField);
            $distinct = true;
        }

        // is there an order by? if not, we order with the first field
        $orderby = stristr($from, 'ORDER BY');
        if ($orderby === false) {
            if (stripos($firstField, ' as ') !== false) {
                list($field, $key) = preg_split('/ as /', $firstField);
            } else {
                $key = $firstField;
            }

            $orderby = ' ORDER BY '.strstr(strstr($key, '.'), '[').' ASC';
            $from .= $orderby;
        } else {
            if (strpos($orderby, '.', 8)) {
                $orderby = ' ORDER BY '.substr($orderby, strpos($orderby, '.') + 1);
            }
        }

        // first we select all records from the begining to the last record of the selection
        if (!$distinct) {
            $queryString = 'SELECT TOP ';
        } else {
            $queryString = 'SELECT DISTINCT TOP ';
        }

        $queryString .= ($limitCount + $limitOffset).' '.$firstField.','.implode(',', $fields).' FROM '.$from;

        // then we select the last $number records, by retrieving the first $number record in the reverse order
        $queryString = 'SELECT TOP '.$limitCount.' * FROM ('.$queryString.') AS inner_tbl ';
        $order_inner = preg_replace(array('/\bASC\b/i', '/\bDESC\b/i'), array('_DESC', '_ASC'), $orderby);
        $order_inner = str_replace(array('_DESC', '_ASC'), array('DESC', 'ASC'), $order_inner);
        $queryString .= $order_inner;

        // finally, we retrieve the result in the expected order
        return 'SELECT TOP '.$limitCount.' * FROM ('.$queryString.') AS outer_tbl '.$orderby;
    }


    public function prepare($query, $driverOptions = []) {
        $result = parent::prepare($query, $driverOptions);
        if ($result) {
            $result->setFetchMode(\PDO::FETCH_OBJ);
        }
        return $result;
    }

    /**
     * Escape and quotes strings. if null, will only return the text "NULL".
     *
     * @param string $text      string to quote
     * @param bool   $checknull if true, check if $text is a null value, and then return NULL
     * @param bool   $binary    set to true if $text contains a binary string
     *
     * @return string escaped string
     *
     * @todo $binary parameter is not really supported, check if PDOConnection::quote supports binary strings
     */
    public function quote2($text, $checknull = true, $binary = false)
    {
        if ($checknull) {
            return is_null($text) ? 'NULL' : $this->quote($text);
        }

        return $this->quote($text);
    }


    /**
     * enclose the field name.
     *
     * @param string $fieldName the field name
     *
     * @return string the enclosed field name
     */
    public function encloseName($fieldName)
    {
        switch ($this->_dbms) {
            case 'mysql': return '`'.$fieldName.'`';
            case 'pgsql': return '"'.$fieldName.'"';
            default: return $fieldName;
        }
    }

    /**
     * Prefix the given table with the prefix specified in the connection's profile
     * If there's no prefix for the connection's profile, return the table's name unchanged.
     *
     * @param string $table      the table's name
     * @param mixed  $table_name
     *
     * @return string the prefixed table's name
     *
     * @author Julien Issler
     */
    public function prefixTable($table_name)
    {
        return $this->_profile['table_prefix'].$table_name;
    }

    /**
     * Remove the prefix of the given table name.
     *
     * @param string $tableName
     *
     * @return string the table name unprefixed
     */
    public function unprefixTable($tableName)
    {
        if (!isset($this->_profile['table_prefix']) || $this->_profile['table_prefix'] == '') {
            return $tableName;
        }
        $prefix = $this->_profile['table_prefix'];
        if (strpos($tableName, $prefix) !== 0) {
            return $tableName;
        }

        return substr($tableName, strlen($prefix));
    }

    /**
     * Check if the current connection has a table prefix set.
     *
     * @return bool
     *
     * @author Julien Issler
     */
    public function hasTablePrefix()
    {
        return $this->_profile['table_prefix'] != '';
    }

    /**
     * sets the autocommit state.
     *
     * @param bool $state the status of autocommit
     */
    public function setAutoCommit($state = true)
    {
        $this->setAttribute(\PDO::ATTR_AUTOCOMMIT, $state);
    }

    /**
     * Get the ID of the last inserted row
     * Mssql pdo driver does not support this feature.
     * so, we use a custom query.
     *
     * @param string $fromSequence the sequence name, if needed
     *
     * @return string
     */
    public function lastInsertId($fromSequence = null)
    {
        if ($this->_dbms == 'mssql') {
            $res = $this->query('SELECT SCOPE_IDENTITY()');

            return (int) $res->fetchColumn();
        }

        return parent::lastInsertId($fromSequence);
    }

    /**
     * return the maximum value of the given primary key in a table.
     *
     * @param string $fieldName the name of the primary key
     * @param string $tableName the name of the table
     *
     * @return int the maximum value
     */
    public function lastIdInTable($fieldName, $tableName)
    {
        $rs = $this->query('SELECT MAX('.$fieldName.') as ID FROM '.$tableName);
        if (($rs !== null) && $r = $rs->fetch()) {
            return $r->ID;
        }

        return 0;
    }




    /**
     * @return AbstractTools
     */
    public function tools()
    {
        throw new Exception("Not implemented");
    }

    /**
     * @return AbstractSchema
     */
    public function schema()
    {
        throw new Exception("Not implemented");
    }


}
