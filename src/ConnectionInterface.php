<?php
/**
 * @author      Laurent Jouanneau
 * @copyright   2020-2025 Laurent Jouanneau
 *
 * @see        http://jelix.org
 * @licence     http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */

namespace Jelix\Database;

use Jelix\Database\Schema\SchemaInterface;
use Jelix\Database\Schema\SqlToolsInterface;
use Psr\Log\LoggerInterface;
use Jelix\Database\Schema\TableNameInterface;

interface ConnectionInterface
{

    /**
     * do a connection to the database, using properties of the given profile.
     *
     * @param array $profile profile properties. Its content must be normalized by AccessParameters
     */
    public function __construct($profile, ?LoggerInterface $logger = null);

    /**
     * Close the connection.
     *
     * Automatically called by the destructor
     */
    public function close();

    /**
     * alias of close()
     * @deprecated
     */
    public function disconnect();

    public function isClosed();

    public function getProfileName();

    public function getConnectionCharset();

    /**
     * The SQL language using by the database.
     * It is not the driver name. Several drivers could connect to the same database
     * type. This type name is often used to know which SQL language we should use.
     *
     * @return string
     */
    public function getSQLType();


    public function getDriverName();

    public function getLastQuery();


    /**
     * Launch a SQL Query which returns rows (typically, a SELECT statement).
     *
     * @param string        $queryString the SQL query
     * @param int           $fetchmode   FETCH_OBJ, FETCH_CLASS or FETCH_INTO
     * @param object|string $param       class name if FETCH_CLASS, an object if FETCH_INTO. else null.
     * @param array         $ctoargs     arguments for the constructor if FETCH_CLASS
     * @param null|mixed    $arg1
     *
     * @return ResultSetInterface|false false if the query has failed
     * @throws Exception
     */
    public function query($queryString, $fetchmode = ConnectionConstInterface::FETCH_OBJ, $arg1 = null, $ctoargs = null);

    /**
     * Launch a SQL Query with limit parameter, so it returns only a subset of a result.
     *
     * @param string $queryString the SQL query
     * @param int    $limitOffset the offset of the first row to return
     * @param int    $limitCount  the maximum of number of rows to return
     *
     * @return ResultSetInterface|false SQL Select. False if the query has failed.
     */
    public function limitQuery($queryString, $limitOffset, $limitCount);

    /**
     * Launch a SQL Query (update, delete..) which doesn't return rows.
     *
     * @param string $query the SQL query
     *
     * @return int the number of affected rows. False if the query has failed.
     */
    public function exec($query);

    /**
     * Escape and quotes strings.
     *
     * @param string $text           string to quote
     * @param int    $parameter_type unused, just for compatibility with PDO
     *
     * @return string escaped string
     */
    public function quote($text, $parameter_type = 0);

    /**
     * Escape and quotes strings. if null, will only return the text "NULL".
     *
     * @param string $text      string to quote
     * @param bool   $checknull if true, check if $text is a null value, and then return NULL
     * @param bool   $binary    set to true if $text contains a binary string
     *
     * @return string escaped string
     */
    public function quote2($text, $checknull = true, $binary = false);

    /**
     * enclose the field name.
     *
     * @param string $fieldName the field name
     *
     * @return string the enclosed field name
     */
    public function encloseName($fieldName);

    /**
     * Prefix the given table with the prefix specified in the connection's profile
     * If there's no prefix for the connection's profile, return the table's name unchanged.
     *
     * @param string $tableName      the table's name
     *
     * @return string the prefixed table's name
     *
     * @author Julien Issler
     */
    public function prefixTable($tableName);

    /**
     * Remove the prefix of the given table name.
     *
     * @param string $tableName
     *
     * @return string the table name unprefixed
     */
    public function unprefixTable($tableName);

    /**
     * Check if the current connection has a table prefix set.
     *
     * @return bool
     *
     * @author Julien Issler
     */
    public function hasTablePrefix();

    /**
     * @return string
     */
    public function getTablePrefix();

    /**
     * Create a TableNameInterface name tied to the type of database server
     *
     * @param string $name
     * @return TableNameInterface
     */
    public function createTableName(string $name) : TableNameInterface;

    /**
     * sets the autocommit state.
     *
     * @param bool $state the status of autocommit
     */
    public function setAutoCommit($state = true);

    /**
     * begin a transaction. Call it before query, limitQuery, exec
     * And then commit() or rollback().
     */
    public function beginTransaction();

    /**
     * validate all queries and close a transaction.
     */
    public function commit();

    /**
     * cancel all queries of a transaction and close the transaction.
     */
    public function rollback();

    /**
     * prepare a query. It may contain some named parameters declared as ':a_name'
     * in the query.
     *
     * @param string $query a sql query with parameters
     *
     * @return ResultSetInterface a statement with which you can bind values or variables to
     *                      named parameters, and execute the statement
     */
    public function prepare($query, $driverOptions = []);

    /**
     * @return string the last error description
     */
    public function errorInfo();

    /**
     * @return int the last error code
     */
    public function errorCode();

    /**
     * return the id value of the last inserted row.
     * Some driver need a sequence name, so give it at first parameter.
     *
     * @param string $fromSequence the sequence name
     *
     * @return int the id value
     */
    public function lastInsertId($fromSequence = '');

    /**
     * @param int $id the attribut id
     *
     * @return string the attribute value
     *
     * @see \PDO::getAttribute()
     */
    public function getAttribute($id);

    /**
     * @param int    $id    the attribut id
     * @param string $value the attribute value
     *
     * @see \PDO::setAttribute()
     */
    public function setAttribute($id, $value);

    /**
     * return the maximum value of the given primary key in a table.
     *
     * @param string $fieldName the name of the primary key
     * @param string $tableName the name of the table
     *
     * @return int the maximum value
     */
    public function lastIdInTable($fieldName, $tableName);

    /**
     * Indicate the default schema used for the user of the connection.
     *
     * For connectors that don't support schema, return an empty name
     *
     * @return string the schema name
     */
    public function getDefaultSchemaName();

    /**
     * @return SqlToolsInterface
     */
    public function tools();

    /**
     * @return SchemaInterface
     */
    public function schema();
}
