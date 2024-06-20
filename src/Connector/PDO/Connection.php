<?php
/**
 * @author     Laurent Jouanneau
 * @contributor Gwendal Jouannic, Thomas, Julien Issler, Vincent Herr
 *
 * @copyright  2005-2024 Laurent Jouanneau, 2008 Gwendal Jouannic, 2009 Thomas, 2009 Julien Issler, 2011 Vincent Herr
 *
 * @see      https://jelix.org
 * @licence  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */
namespace Jelix\Database\Connector\PDO;

use Jelix\Database\AbstractConnection;
use Jelix\Database\Schema\Postgresql\SQLTools;
use Jelix\Database\Schema\SchemaInterface;
use Jelix\Database\Exception;

use \Jelix\Database\Connection as ConnectionFactory;
use Jelix\Database\Schema\TableNameInterface;

/**
 * A connection object based on PDO.
 *
 * @package  jelix
 * @subpackage db
 */
class Connection extends AbstractConnection
{

    /**
     * the internal connection.
     *
     * @var \PDO
     */
    protected $_connection;

    private $_pgsqlCharsets = array('UTF-8' => 'UNICODE', 'ISO-8859-1' => 'LATIN1');

    /**
     * @inheritDoc
     */
    protected function _connect()
    {
        $user = '';
        $password = '';

        $dsn = $this->_profile['dsn'];
        if ($this->_profile['dbtype'] == 'sqlite') {
            $path = substr($dsn, 7);
            if ($this->_profile['filePathParser']) {
                $path = call_user_func_array($this->_profile['filePathParser'], array($path));
            }

            $dsn = 'sqlite:'.$path;
        }

        // we check user and password because some db like sqlite doesn't have user/password
        if (isset($this->_profile['user'])) {
            $user = $this->_profile['user'];
        }

        if (isset($this->_profile['password'])) {
            $password = $this->_profile['password'];
        }

        $pdoOptions = array();
        if ($this->_profile['pdooptions'] != '') {
            foreach (explode(',', $this->_profile['pdooptions']) as $optname) {
                $pdoOptions[$optname] = $this->_profile[$optname];
            }
        }

        $initsql = '';
        if ($this->_profile['force_encoding']) {
            $pdoDriver = $this->_profile['pdodriver'];
            $charset = $this->_profile['charset'];
            if ($pdoDriver == 'mysql' ||
                $pdoDriver == 'mssql' ||
                $pdoDriver == 'sybase' ||
                $pdoDriver == 'oci') {
                $dsn .= ';charset='.$charset;
            } elseif ($this->_profile['dbtype'] == 'pgsql' && isset($this->_pgsqlCharsets[$charset])) {
                $initsql = "SET client_encoding to '".$this->_pgsqlCharsets[$charset]."'";
            }
        }

        $pdoConn = new \PDO($dsn, $user, $password, $pdoOptions);
        $pdoConn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        // we cannot launch two queries at the same time with PDO ! except if
        // we use mysql with the attribute MYSQL_ATTR_USE_BUFFERED_QUERY
        // TODO check if PHP 5.3 or higher fixes this issue
        if ($this->_profile['dbtype'] == 'mysql') {
            $pdoConn->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        }

        // Oracle returns names of columns in upper case by default. so here
        // we force the case in lower.
        if ($this->_profile['dbtype'] == 'oci') {
            $pdoConn->setAttribute(\PDO::ATTR_CASE, \PDO::CASE_LOWER);
        }

        if ($initsql) {
            $pdoConn->exec($initsql);
        }

        return $pdoConn;
    }

    protected function _disconnect()
    {
        $this->_connection = null;
    }


    /**
     * @inheritDoc
     */
    public function quote($text, $parameter_type = 0)
    {
        // for compatibility with older jelix version
        if ($parameter_type === false || $parameter_type === true) {
            trigger_error('signature of jDbConnection::quote has changed, you should use quote2()', E_USER_WARNING);
        }

        return $this->_connection->quote($text, $parameter_type);
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
        switch ($this->_profile['dbtype']) {
            case 'mysql': return '`'.$fieldName.'`';
            case 'pgsql': return '"'.$fieldName.'"';
            default: return $fieldName;
        }
    }

    /**
     * @inheritDoc
     */
    public function beginTransaction()
    {
        return $this->_connection->beginTransaction();
    }

    /**
     * @inheritDoc
     */
    public function commit()
    {
        return $this->_connection->commit();
    }

    /**
     * @inheritDoc
     */
    public function rollback()
    {
        return $this->_connection->rollback();
    }

    /**
     * @inheritDoc
     */
    public function prepare($query, $driverOptions = [])
    {
        $pdoStmt = $this->_connection->prepare($query, $driverOptions);
        if ($pdoStmt) {
            $result = new ResultSet($pdoStmt);
            $result->setFetchMode(\PDO::FETCH_OBJ);
            return $result;
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function errorInfo()
    {
        return $this->_connection->errorInfo();
    }

    /**
     * @inheritDoc
     */
    public function errorCode()
    {
        return $this->_connection->errorCode();
    }

    /**
     * Get the ID of the last inserted row
     *
     * Mssql pdo driver does not support this feature.
     * so, we use a custom query.
     *
     * @param string $fromSequence the sequence name, if needed
     *
     * @return string
     */
    public function lastInsertId($fromSequence = null)
    {
        if ($this->_profile['dbtype'] == 'mssql') {
            $res = $this->query('SELECT SCOPE_IDENTITY()');

            return (int) $res->fetchColumn();
        }

        return $this->_connection->lastInsertId($fromSequence);
    }

    /**
     * @inheritDoc
     */
    public function getAttribute($id)
    {
        return $this->_connection->getAttribute($id);
    }

    /**
     * @inheritDoc
     */
    public function setAttribute($id, $value)
    {
        return $this->_connection->setAttribute($id, $value);
    }

    /**
     * @inheritDoc
     */
    protected function _autoCommitNotify($state = true)
    {
        $this->setAttribute(\PDO::ATTR_AUTOCOMMIT, $state);
    }

    /**
     * @inheritDoc
     */
    protected function _doQuery($queryString)
    {
        $pdoStmt = $this->_connection->query($queryString);
        if ($pdoStmt) {
            $result = new ResultSet($pdoStmt);
            $result->setFetchMode(\PDO::FETCH_OBJ);
            return $result;
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    protected function _doExec($queryString)
    {
        return $this->_connection->exec($queryString);
    }

    /**
     * @inheritDoc
     */
    protected function _doLimitQuery($queryString, $limitOffset, $limitCount)
    {
        $dbms = $this->_profile['dbtype'];
        if ($dbms == 'mysql' || $dbms == 'sqlite') {
            $queryString .= ' LIMIT '.$limitOffset.','.$limitCount;
        } elseif ($dbms == 'pgsql') {
            $queryString .= ' LIMIT '.$limitCount.' OFFSET '.$limitOffset;
        } elseif ($dbms == 'oci') {
            $limitOffset = $limitOffset + 1; // rnum begins at 1
            $queryString = 'SELECT * FROM ( SELECT ocilimit.*, rownum rnum FROM ('.$queryString.') ocilimit WHERE
                rownum<'.($limitOffset + $limitCount).'  ) WHERE rnum >='.$limitOffset;
        } elseif ($dbms == 'sqlsrv') {
            $queryString = $this->limitQuerySqlsrv($queryString, $limitOffset, $limitCount);
        }

         return $this->_doQuery($queryString);
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

    /**
     * @return SchemaInterface
     */
    protected function _getSchema()
    {
        switch ($this->_profile['dbtype']) {
            case ConnectionFactory::DB_TYPE_MYSQL:
                $schema = new \Jelix\Database\Schema\Mysql\Schema($this);
                break;
            case ConnectionFactory::DB_TYPE_PGSQL:
                $schema = new \Jelix\Database\Schema\Postgresql\Schema($this);
                break;
            case ConnectionFactory::DB_TYPE_SQLITE:
                $schema = new \Jelix\Database\Schema\Sqlite\Schema($this);
                break;
            case ConnectionFactory::DB_TYPE_SQLSERVER:
                $schema = new \Jelix\Database\Schema\Sqlserver\Schema($this);
                break;
            case ConnectionFactory::DB_TYPE_ORACLE:
                $schema = new \Jelix\Database\Schema\Oci\Schema($this);
                break;
            default:
                $schema = null;
                throw new Exception("not implemented");
        }
        return $schema;
    }

    public function createTableName(string $name, $schema='') : TableNameInterface
    {
        switch ($this->_profile['dbtype']) {
            case ConnectionFactory::DB_TYPE_MYSQL:
                $schema = new \Jelix\Database\Schema\Mysql\TableName($name, $schema);
                break;
            case ConnectionFactory::DB_TYPE_PGSQL:
                $schema = new \Jelix\Database\Schema\Postgresql\TableName($name, $schema);
                break;
            case ConnectionFactory::DB_TYPE_SQLITE:
                $schema = new \Jelix\Database\Schema\Sqlite\TableName($name, $schema);
                break;
            case ConnectionFactory::DB_TYPE_SQLSERVER:
                $schema = new \Jelix\Database\Schema\Sqlserver\TableName($name, $schema);
                break;
            case ConnectionFactory::DB_TYPE_ORACLE:
                $schema = new \Jelix\Database\Schema\Oci\TableName($name, $schema);
                break;
            default:
                $schema = null;
                throw new Exception("not implemented");
        }
        return $schema;
    }

    public function getDefaultSchemaName()
    {
        if ($this->_profile['dbtype'] == ConnectionFactory::DB_TYPE_PGSQL) {
            $tools = new SQLTools();
            return $tools->getDefaultSchemaName($this);
        }
        elseif ($this->_profile['dbtype'] == ConnectionFactory::DB_TYPE_SQLSERVER) {
            $queryString = 'SELECT SCHEMA_NAME() as name';
            $result = $this->_doQuery($queryString);
            if ($result && $rec = $result->fetch()) {
                return $rec->name;
            }
        }
        return '';
    }
}
