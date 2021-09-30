<?php
/**
 * @author     Yann Lecommandoux
 * @contributor Laurent Jouanneau, Louis S.
 *
 * @copyright  2008 Yann Lecommandoux, 2011-2021 Laurent Jouanneau, Louis S.
 *
 * @see     https://jelix.org
 * @licence  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */
namespace Jelix\Database\Connector\SQLServer;

use Jelix\Database\AbstractConnection;
use Jelix\Database\Exception;

/**
 *
 */
class Connection extends AbstractConnection
{
    /**
     * @inheritDoc
     */
    public function __construct($profile)
    {
        if (!function_exists('sqlsrv_connect')) {
            throw new Exception('sqlsrv extension is not installed in PHP', 405);
        }
        parent::__construct($profile);
    }

    /**
     * begin a transaction.
     */
    public function beginTransaction()
    {
        sqlsrv_begin_transaction($this->_connection);
    }

    /**
     * Commit since the last begin.
     */
    public function commit()
    {
        sqlsrv_commit($this->_connection);
    }

    /**
     * Rollback since the last BEGIN.
     */
    public function rollback()
    {
        sqlsrv_rollback($this->_connection);
    }

    /**
     * tell sqlsrv to be implicit commit or not.
     *
     * @param bool $state the state of the autocommit value
     * @see https://docs.microsoft.com/en-us/sql/t-sql/statements/set-implicit-transactions-transact-sql?view=sql-server-ver15
     */
    protected function _autoCommitNotify($state)
    {
        if ($state) {
            // per doc: When OFF, each of the preceding T-SQL statements is
            // bounded by an unseen BEGIN TRANSACTION and an unseen COMMIT
            // TRANSACTION statement. When OFF, we say the transaction mode
            // is autocommit.
            $this->query('SET IMPLICIT_TRANSACTIONS OFF');
        } else {
            $this->query('SET IMPLICIT_TRANSACTIONS ON');
        }
    }

    public function errorInfo()
    {
        return sqlsrv_errors(SQLSRV_ERR_ERRORS);
    }

    public function errorCode()
    {
        $err = sqlsrv_errors(SQLSRV_ERR_ERRORS);
        if ($err) {
            return $err['code'];
        }

        return 0;
    }

    /**
     * initialize the connection to the database.
     *
     */
    protected function _connect()
    {
        $connectOptions = array();
        if (isset($this->profile['user']) && $this->profile['user'] != '') {
            $connectOptions['UID'] = $this->profile['user'];
        }
        if (isset($this->profile['password']) && $this->profile['password'] != '') {
            $connectOptions['PWD'] = $this->profile['password'];
        }
        if (isset($this->profile['database']) && $this->profile['database'] != '') {
            $connectOptions['Database'] = $this->profile['database'];
        }
        if (isset($this->profile['force_encoding']) && $this->profile['force_encoding'] == true) {
            $connectOptions['CharacterSet'] = 'UTF-8';
        }

        if ($cnx = sqlsrv_connect($this->profile['host'], $connectOptions)) {
            return $cnx;
        }

        throw new Exception('Error during the connection on '.$this->profile['host'], 402);
    }

    /**
     * 	close the connection to the database.
     *
     */
    protected function _disconnect()
    {
        sqlsrv_close($this->_connection);
    }

    /**
     * 	execute an SQL instruction.
     *
     * @param mixed $query
     */
    protected function _doQuery($query)
    {
        if (preg_match('/^\s*EXEC\s+/i', $query)) {
            $stmt = sqlsrv_query($this->_connection, $query);
        } else {
            $stmt = sqlsrv_query($this->_connection, $query, null, array('Scrollable' => SQLSRV_CURSOR_STATIC));
        }

        if ($stmt) {
            return new ResultSet($stmt);
        }

        throw new Exception('invalid query: '.$this->_getErrorMsg().'('.$query.')', 403);
    }

    /**
     * @param mixed $query
     */
    protected function _doExec($query)
    {
        if ($stmt = sqlsrv_query($this->_connection, $query)) {
            $nbRows = sqlsrv_rows_affected($stmt);
            sqlsrv_free_stmt($stmt);

            return $nbRows;
        }

        throw new Exception('invalid query: '.$this->_getErrorMsg().'('.$query.')', 403);
    }

    protected function _getErrorMsg()
    {
        $errors = sqlsrv_errors();
        $msg = '';
        foreach ($errors as $error) {
            $msg .= '['.$error[ 'SQLSTATE'].'] '.$error[ 'code'].': '.$error[ 'message']."\n";
        }
        return $msg;
    }

    /**
     * @param mixed $queryString
     * @param mixed $offset
     * @param mixed $number
     */
    protected function _doLimitQuery($queryString, $offset, $number)
    {
        if ($number == 0) {
            // SQLServer does not support FETCH NEXT 0 ROWS ONLY, so we
            // return an empty result set
            return new ResultSet(null);
        }

        $limit = ' OFFSET '.$offset. ' ROWS FETCH NEXT '.$number .' ROWS ONLY';

        // we suppress existing 'TOP XX'
        $queryString = preg_replace('/^SELECT TOP[ ]\d*\s*/i', 'SELECT ', trim($queryString));

        // we retrieve the select part and the from part
        list($select, $from) = preg_split('/\sFROM\s/mi', $queryString, 2);

        $fields = preg_split('/\s*,\s*/', $select);
        $firstField = preg_replace('/^\s*SELECT\s+/', '', array_shift($fields));

        // is there a distinct?
        if (stripos($firstField, 'DISTINCT') !== false) {
            $firstField = preg_replace('/DISTINCT/i', '', $firstField);
        }

        // is there an order by? if not, we order with the first field
        $orderby = stristr($from, 'ORDER BY');
        if ($orderby === false) {
            if (stripos($firstField, ' as ') !== false) {
                list($field, $key) = preg_split('/ as /', $firstField);
            } else {
                $key = $firstField;
            }

            $queryString .= ' ORDER BY '.$key.' ASC ';
        }

        $queryString .= $limit;
        $this->lastQuery = $queryString;

        return $this->_doQuery($queryString);
    }

    /**
     * @param mixed $fromSequence
     *
     * @return int the last inserted ID incremented in database
     */
    public function lastInsertId($fromSequence = '')
    {
        $queryString = 'SELECT @@IDENTITY AS id';
        $result = $this->_doQuery($queryString);
        if ($result) {
            return $result->id;
        }

        return null;
    }

    /**
     * @param mixed $query
     */
    public function prepare($query, $driverOptions = [])
    {
        list($newQuery, $parameterNames) = $this->findParameters($query, '?');
        return new ResultSet(null, $this->_connection, $newQuery, $parameterNames);
    }

    /**
     * @inheritdoc
     */
    public function encloseName($fieldName)
    {
        return '['.$fieldName.']';
    }

    /**
     * escape special characters.
     *
     * @todo support of binary strings
     *
     * @param mixed $text
     * @param mixed $binary
     */
    protected function _quote($text, $binary)
    {
        return str_replace("'", "''", $text);
    }

    /**
     * @param int $id the attribut id
     *
     * @return string the attribute value
     *
     * @see PDO::getAttribute()
     */
    public function getAttribute($id)
    {
        return '';
    }

    /**
     * @param int    $id    the attribut id
     * @param string $value the attribute value
     *
     * @see PDO::setAttribute()
     */
    public function setAttribute($id, $value)
    {
    }

    protected function _getSchema()
    {
        return new \Jelix\Database\Schema\Sqlserver\Schema($this);
    }
}
