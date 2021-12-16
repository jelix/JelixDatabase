<?php
/**
 * @author      Laurent Jouanneau
 * @copyright   2020-2021 Laurent Jouanneau
 *
 * @see        http://jelix.org
 * @licence     http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */

namespace Jelix\Database;

interface ResultSetInterface
{

    /**
     * @param string $text a binary string to unescape
     *
     * @return string the unescaped string
     */
    public function unescapeBin($text);

    /**
     * @param callable $function a callback function
     *                           the function should accept in parameter the record,
     *                           and the resulset object
     */
    public function addModifier($function);

    /**
     * set the fetch mode.
     *
     * @param int           $fetchmode FETCH_OBJ, FETCH_CLASS or FETCH_INTO
     * @param object|string $param     class name if FETCH_CLASS, an object if FETCH_INTO. else null.
     * @param array         $ctoargs   arguments for the constructor if FETCH_CLASS
     */
    public function setFetchMode($fetchmode, $param = null, $ctoargs = null);

    /**
     * fetch a result. The result is returned as an object.
     *
     * @return object|bool result object or false if there is no more result
     */
    public function fetch();

    /**
     * fetch a result. The result is returned as an associative array.
     *
     * modifiers are not applied on results
     * @return array|bool result array or false if there is no more result
     */
    public function fetchAssociative();

    /**
     * Return all results in an array. Each result is an object.
     *
     * @return object[]
     */
    public function fetchAll();

    /**
     * Return all results in an array. Each result is an associative array.
     *
     * modifiers are not applied on results
     *
     * @return array[]
     */
    public function fetchAllAssociative();

    /**
     * Retrieve a statement attribute.
     *
     * @param int $attr
     */
    public function getAttribute(int $attr);

    /**
     * Set a statement attribute.
     *
     * @param int   $attr
     * @param mixed $value
     */
    public function setAttribute($attr, $value);

    /**
     *  Bind a column to a PHP variable.
     *
     * @param mixed      $column
     * @param mixed      $param
     * @param null|mixed $type
     */
    public function bindColumn($column, &$param, $type = null);

    /**
     * Binds a parameter to the specified variable name.
     *
     * @param mixed      $parameterName
     * @param mixed      $variable
     * @param mixed      $data_type
     * @param null|mixed $length
     * @param null|mixed $driver_options
     */
    public function bindParam($parameterName, &$variable, $data_type = \PDO::PARAM_STR, $length = null, $driver_options = null);

    /**
     *  Binds a value to a parameter.
     *
     * @param mixed $parameterName
     * @param mixed $value
     * @param mixed $data_type
     */
    public function bindValue($parameterName, $value, $data_type = \PDO::PARAM_STR);

    /**
     * Returns the number of columns in the result set.
     */
    public function columnCount();

    /**
     * execute a prepared statement
     * It may accepted an array of named parameters and their value, if bindValue
     * or bindParam() did not called.
     *
     * @param array $parameters
     */
    public function execute($parameters = null);

    /**
     *  Returns the number of rows affected by the last SQL statement.
     */
    public function rowCount();

    /**
     * Free resources of the resultset
     *
     * Automatically called on the destruction of the resultset object
     */
    public function free();
}
