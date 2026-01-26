<?php

/**
 * @author      Laurent Jouanneau
 * @copyright   2020-2026 Laurent Jouanneau
 *
 * @see         https://jelix.org
 * @licence     http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */
namespace Jelix\Database\Schema;

/**
 * Interface for a class that helps to manipulate SQL syntax content
 */
interface SQLSyntaxHelpersInterface
{
    /**
     * The SQL language supported by the object.
     *
     * @return string
     */
    public function getSQLType();

    /**
     * Get information about the given SQL type.
     *
     * @param string $nativeType the SQL type
     *
     * @return array an array which contains characteristics of the type
     *               array ( 'nativetype', 'corresponding unifiedtype', minvalue, maxvalue, minlength, maxlength, autoincrement)
     *               minvalue, maxvalue, minlength, maxlength can be null
     *
     */
    public function getTypeInfo(string $nativeType): array;


    /**
     * Return the PHP type corresponding to the given unified type.
     *
     * @param string $unifiedType
     *
     * @return string the php type
     *
     * @throws Exception
     *
     */
    public function unifiedToPHPType(string $unifiedType) : string;

    /**
     * @param string $unifiedType the unified type name
     * @param string $value the value
     * @param bool $checkNull
     *
     * @return mixed the php value corresponding to the type
     *
     */
    public function stringToPhpValue(string $unifiedType, $value, bool $checkNull = false) : mixed;

    /**
     * Parse a SQL type and gives type, length...
     *
     * @param string $type
     *
     * @return array [$realtype, $length, $precision, $scale, $otherTypeDef]
     */
    public function parseSQLType(string $type) : array;


    /**
     * @param string $unifiedType the unified type name
     * @param mixed $value the value
     * @param boolean $checkNull
     *
     * @return string the value which is ready to include into a SQL query string
     *
     */
    public function escapeValue(string $unifiedType, $value, bool $checkNull = false) : string;

    /**
     * @param string $unifiedType the unified type name
     * @param mixed $value the value
     * @param boolean $checkNull
     *
     * @return string the value which is ready to include into a PHP source code
     *
     */
    public function escapeValueAsPHPSource(string $unifiedType, $value, bool $checkNull = false) : string;


    /**
     * @param bool|string $value a value which is a boolean
     *
     * @return string the string value representing a boolean in SQL
     *
     */
    public function getBooleanValue($value) : string;


    /**
     * Enclose the field name.
     *
     * @param string $fieldName the field name
     *
     * @return string the enclosed field name
     *
     */
    public function encloseName(string $fieldName) : string;

    /**
     * Naive escape string content for SQL query.
     *
     * Since it doesn't use a connection, some special characters may not be
     * escaped correctly when the charset is different from the database charset.
     */
    public function quoteString($value) : string;


    public function parseSQLFunctionAndConvert(string $expression) : string;


    public function getNativeSQLFunction(string $name, $parametersString = null) : string;
}
