<?php
/**
 * @author     Laurent Jouanneau
 * @contributor Laurent Jouanneau
 * @copyright  2020-2026 Laurent Jouanneau
 *
 * @see        https://jelix.org
 * @licence     http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */
namespace Jelix\Database\Schema;

use Jelix\Database\Connection;
use Jelix\Database\ConnectionInterface;

/**
 * Interface for SQL utilities for a database type
 */
interface SqlToolsInterface
{
    /**
     * @param ConnectionInterface $connector the connection to a database
     */
    public function __construct(?ConnectionInterface $connector = null);

    /**
     * @return ConnectionInterface
     */
    public function getConnection();

    /**
     * returns the list of tables.
     *
     * @throws Exception
     *
     * @return string[] list of table names
     * @deprecated
     * @see SchemaInterface
     */
    public function getTableList();


    /**
     * Retrieve the list of fields of a table.
     *
     * @param string $tableName  the name of the table
     * @param string $sequence   the sequence used to auto increment the primary key
     * @param string $schemaName the name of the schema (only for PostgreSQL)
     *
     * @return FieldProperties[] keys are field names
     * @deprecated
     * @see SchemaInterface
     */
    public function getFieldList($tableName, $sequence = '', $schemaName = '');

    /**
     * execute a list of queries stored in a file.
     *
     * @param string $file path of the sql file
     */
    public function execSQLScript($file);

    /**
     *
     * @param string[] $columns list of column names
     *
     * @return string the list in SQL
     */
    public function getSQLColumnsList($columns);

    /**
     * Parse a SQL CREATE TABLE statement and returns all of its components
     * separately.
     *
     * @param $createTableStatement
     *
     * @return array|bool false if parsing has failed. Else an array :
     *                    'name' => the schema/table name,
     *                    'temporary'=> true if there is the temporary keywork ,
     *                    'ifnotexists' => true if there is the IF NOT EXISTS statement,
     *                    'columns' => list of columns definitions,
     *                    'constraints' => list of table constraints definitions,
     *                    'options' => all options at the end of the CREATE TABLE statement.
     */
    public function parseCREATETABLE($createTableStatement);

    const IBD_NO_CHECK = 0;
    const IBD_EMPTY_TABLE_BEFORE = 1;
    const IBD_INSERT_ONLY_IF_TABLE_IS_EMPTY = 2;
    const IBD_IGNORE_IF_EXIST = 3;
    const IBD_UPDATE_IF_EXIST = 4;

    /**
     * Insert several records into a table.
     *
     * @param string               $tableName
     * @param string[]             $columns    the column names in which data will be inserted
     * @param mixed[][]            $data       the data. each row is an array of values. Values are
     *                                         in the same order as $columns
     * @param null|string|string[] $primaryKey the column names that are
     *                                         the primary key. Don't give the primary key if it
     *                                         is an autoincrement field, or if option is not
     *                                         IBD_*_IF_EXIST
     * @param int                  $options    one of IDB_* const
     *
     * @return int number of records inserted/updated
     */
    public function insertBulkData($tableName, $columns, $data, $primaryKey = null, $options = 0);

}
