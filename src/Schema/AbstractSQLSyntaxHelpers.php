<?php
/**
 * @author      Laurent Jouanneau
 * @contributor Laurent Jouanneau
 * @copyright   2009-2026 Laurent Jouanneau
 *
 * @see         https://jelix.org
 * @licence     http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */
namespace Jelix\Database\Schema;

use Jelix\Database\Utilities;

/**
 * Provides utilities methods to manipulate SQL syntax content
 */
abstract class AbstractSQLSyntaxHelpers implements SQLSyntaxHelpersInterface
{
    protected const sqlType = '';
    public const trueValue = '1';
    public const falseValue = '0';

    protected $unifiedToPhp = array(
        'boolean' => 'boolean',
        'integer' => 'integer',
        'float' => 'float',
        'double' => 'float',
        'numeric' => 'numeric',
        'decimal' => 'decimal',
        'date' => 'string',
        'time' => 'string',
        'datetime' => 'string',
        'year' => 'string',
        'char' => 'string',
        'varchar' => 'string',
        'text' => 'string',
        'blob' => 'string',
        'binary' => 'string',
        'varbinary' => 'string',
        'json' => 'array',
    );

    protected $typesInfo = array();

    /**
     * The SQL language supported by the object.
     *
     * @return string
     */
    public function getSQLType()
    {
        return static::sqlType;
    }


    /**
     * Get information about the given SQL type.
     *
     * @param string $nativeType the SQL type
     *
     * @return array an array which contains characteristics of the type
     *               array (
     *                   0 => 'nativetype',
     *                   1 => 'corresponding unifiedtype',
     *                   2 => minvalue,
     *                   3 => maxvalue,
     *                   4 => minlength,
     *                   5 => maxlength,
     *                   6 => autoincrement)
     *               minvalue, maxvalue, minlength, maxlength can be null
     */
    public function getTypeInfo(string $nativeType): array
    {
        $nativeType = strtolower($nativeType);
        if (isset($this->typesInfo[$nativeType])) {
            $r = $this->typesInfo[$nativeType];
        } else {
            $r = $this->typesInfo['varchar'];
        }
        $r[] = ($nativeType == 'serial' || $nativeType == 'bigserial' || $nativeType == 'autoincrement' || $nativeType == 'bigautoincrement');

        return $r;
    }

    /**
     * Return the PHP type corresponding to the given unified type.
     *
     * @param string $unifiedType
     *
     * @throws Exception
     *
     * @return string the php type
     *
*/
    public function unifiedToPHPType(string $unifiedType): string
    {
        if (isset($this->unifiedToPhp[$unifiedType])) {
            return $this->unifiedToPhp[$unifiedType];
        }

        throw new Exception('bad unified type name:'.$unifiedType);
    }

    /**
     * @param string $unifiedType the unified type name
     * @param string $value       the value
     * @param bool  $checkNull
     *
     * @return mixed the php value corresponding to the type
     *
     */
    public function stringToPhpValue(string $unifiedType, $value, bool $checkNull = false) : mixed
    {
        if ($checkNull && ($value === null || strtolower($value) == 'null')) {
            return null;
        }
        switch ($this->unifiedToPHPType($unifiedType)) {
            case 'boolean':
                return $this->getBooleanValue($value) == static::trueValue;
            case 'integer':
                return intval($value);
            case 'float':
                return floatval($value);
            case 'numeric':
            case 'decimal':
                if (is_numeric($value)) {
                    return $value;
                }

                return floatval($value);
            default:
                return $value;
        }
    }

    /**
     * Parse a SQL type and gives type, length...
     *
     * @param string $type
     *
     * @return array [$realtype, $length, $precision, $scale, $otherTypeDef]
     */
    public function parseSQLType(string $type): array
    {
        $length = 0;
        $scale = 0;
        $precision = 0;
        $tail = '';
        if (preg_match('/^(\w+)\s*(\(\s*(\d+)(,(\d+))?\s*\))?(.*)$/', $type, $m)) {
            $type = strtolower($m[1]);
            if (isset($m[3])) {
                $typeInfo = $this->getTypeInfo($type);
                $phpType = $this->unifiedToPHPType($typeInfo[1]);
                if ($phpType == 'string') {
                    $length = intval($m[3]);
                } else {
                    $precision = intval($m[3]);
                }
            }
            if (isset($m[4]) && $m[5]) {
                $precision = $length;
                $length = 0;
                $scale = intval($m[5]);
            }
            if (isset($m[6])) {
                $tail = $m[6];
            }
        }

        return array($type, $length, $precision, $scale, $tail);
    }

    /**
     * @param string $unifiedType the unified type name
     * @param mixed  $value       the value
     * @param boolean  $checkNull
     *
     * @return string the value which is ready to include a SQL query string
     *
     */
    public function escapeValue(string $unifiedType, $value, bool $checkNull = false): string
    {
        if ($checkNull && ($value === null || strtolower($value) == 'null')) {
            return 'NULL';
        }
        switch ($this->unifiedToPHPType($unifiedType)) {
            case 'boolean':
                return $this->getBooleanValue($value);
            case 'integer':
                return (string) intval($value);
            case 'float':
            case 'numeric':
            case 'decimal':
               return Utilities::floatToStr($value);
            case 'array':
                if (!is_string($value)) {
                    $value = json_encode($value);
                }
            default:
                return $this->quoteString($value);
        }
    }

    /**
     * @param string $unifiedType the unified type name
     * @param mixed  $value       the value
     * @param boolean  $checkNull
     *
     * @return string the value which is ready to include a SQL query string
     *
     */
    public function escapeValueAsPHPSource(string $unifiedType, $value, bool $checkNull = false): string
    {
        if ($checkNull && ($value === null || strtolower($value) == 'null')) {
            return 'NULL';
        }
        switch ($this->unifiedToPHPType($unifiedType)) {
            case 'boolean':
                return $this->getBooleanValue($value);
            case 'integer':
                return (string) intval($value);
            case 'float':
            case 'numeric':
            case 'decimal':
               return Utilities::floatToStr($value);
            case 'array':
                if (!is_string($value)) {
                    $value = json_encode($value);
                }
            default:
                if ($unifiedType == 'varbinary' || $unifiedType == 'binary') {
                    return '\'.$this->_conn->quote2(\''.str_replace('\'', '\\\'', $value).'\',true,true).\'';
                }
                if (strpos($value, "'") !== false) {
                    return '\'.$this->_conn->quote(\''.str_replace('\'', '\\\'', $value).'\').\'';
                }

                return "\\'".$value."\\'";
        }
    }

    /**
     * @param bool|string $value a value which is a boolean
     *
     * @return string the string value representing a boolean in SQL
     *
     */
    public function getBooleanValue($value): string
    {
        if (is_string($value)) {
            $value = strtolower($value);
        }
        if ($value === 'true' || $value === true || intval($value) === 1 || $value === 't' || $value === 'on') {
            return static::trueValue;
        }

        return static::falseValue;
    }

    /**
     * Enclose a field name or a table name.
     *
     * @param string $fieldName the field/table name
     *
     * @return string the enclosed name
     *
     */
    public function encloseName(string $fieldName): string
    {
        return $fieldName;
    }


    public function quoteString($value): string
    {
        return "'".addslashes($value)."'";
    }


    protected $keywordNameCorrespondence = array(
        // sqlsrv,mysql,oci,pgsql -> date+time
        //'current_timestamp' => '',
        // mysql,oci,pgsql -> date
        //'current_date' => '',
        // mysql -> time, pgsql -> time+timezone
        //'current_time' => '',
        // oci -> date+fractional secon + timezone
        //'systimestamp' => '',
        // oci -> date+time+tz
        //'sysdate' => '',
        // pgsql -> time
        //'localtime' => '',
        // pgsql -> date+time
        //'localtimestamp' => '',
    );

    protected $functionNameCorrespondence = array(

        // sqlsrv, -> date+time
        //'sysdatetime' => '',
        // sqlsrv, -> date+time+offset
        //'sysdatetimeoffset' => '',
        // sqlsrv, -> date+time at utc
        //'sysutcdatetime' => '',
        // sqlsrv -> date+time
        //'getdate' => '',
        // sqlsrv -> date+time at utc
        //'getutcdate' => '',
        // sqlsrv,mysql (datetime)-> integer
        //'day' => '',
        // sqlsrv,mysql (datetime)-> integer
        //'month' => '',
        // sqlsrv, mysql (datetime)-> integer
        //'year' => '',
        // mysql -> date
        //'curdate' => '',
        // mysql -> date
        //'current_date' => '',
        // mysql -> time
        //'curtime' => '',
        // mysql -> time
        //'current_time' => '',
        // mysql,pgsql -> date+time
        //'now' => '',
        // mysql date+time
        //'current_timestamp' => '',
        // mysql (datetime)->date, sqlite (timestring, modifier)->date
        //'date' => '!dateConverter',
        // mysql = day()
        //'dayofmonth' => '',
        // mysql -> date+time
        //'localtime' => '',
        // mysql -> date+time
        //'localtimestamp' => '',
        // mysql utc current date
        //'utc_date' => '',
        // mysql utc current time
        //'utc_time' => '',
        // mysql utc current date+time
        //'utc_timestamp' => '',
        // mysql (datetime)->time, , sqlite (timestring, modifier)->time
        //'time' => '!timeConverter',
        // mysql (datetime/time)-> hour
        //'hour'=> '',
        // mysql (datetime/time)-> minute
        //'minute'=> '',
        // mysql (datetime/time)-> second
        //'second'=> '',
        // sqlite (timestring, modifier)->datetime
        //'datetime' => '',
        // oci, mysql (year|month|day|hour|minute|second FROM <datetime>)->value ,
        // pgsql (year|month|day|hour|minute|second <datetime>)->value
        //'extract' => '!extractDateConverter',
        // pgsql ('year'|'month'|'day'|'hour'|'minute'|'second', <datetime>)->value
        //'date_part' => '!extractDateConverter',
        // sqlsrv (year||month|day|hour|minute|second, <datetime>)->value
        //'datepart' => '!extractDateConverter',
    );

    protected function extractDateConverter($parametersString)
    {
        if (preg_match("/^'?([a-z]+)'?(?:\\s*,\\s*|\\s+FROM(?:\\s+TIMESTAMP)?\\s+|\\s+)(.*)$/i", trim($parametersString), $p)) {
            $param2 = $this->parseSQLFunctionAndConvert(strtolower($p[2]));

            return 'extract('.$p[1].' FROM '.$param2.')';
        }

        // strange format
        return 'extract('.$parametersString.')';
    }

    public function parseSQLFunctionAndConvert(string $expression): string
    {
        if (preg_match('/^([a-z0-9_]+)(\\((.*)\\))?$/i', trim($expression), $func)) {
            if (isset($func[2]) && $func[2] != '') {
                $params = $func[3];
            } else {
                $params = null;
            }

            return $this->getNativeSQLFunction($func[1], $params);
        }

        return $expression;
    }

    /**
     * Give the expression that works with the target database, corresponding
     * to the given function name.
     *
     * @param string      $name             a SQL function, maybe a SQL function of another database type
     * @param null|string $parametersString parameters given to the function. Null if no parenthesis
     *
     * @return string the SQL expression, possibly with a native SQL function corresponding
     *                to the given foreign SQL function
     */
    public function getNativeSQLFunction(string $name, $parametersString = null): string
    {
        $index = strtolower($name);
        if ($parametersString === null) {
            if (isset($this->keywordNameCorrespondence[$index])) {
                return str_replace('%!p', $parametersString, $this->keywordNameCorrespondence[$index]);
            }

            return $name;
        }
        if (isset($this->functionNameCorrespondence[$index])) {
            $func = $this->functionNameCorrespondence[$index];
            if ($func[0] == '!') {
                $func = substr($func, 1);

                return $this->{$func}($parametersString);
            }

            return str_replace('%!p', $parametersString, $this->functionNameCorrespondence[$index]);
        }

        return $name.'('.$parametersString.')';
    }

}
