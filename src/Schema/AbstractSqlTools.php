<?php
/**
 * @author     Gérald Croes, Laurent Jouanneau
 * @contributor Laurent Jouanneau, Julien Issler
 * @copyright  2001-2005 CopixTeam, 2005-2025 Laurent Jouanneau, 2008 Julien Issler
 *
 * @see        https://jelix.org
 * @licence     http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */
namespace Jelix\Database\Schema;

use Jelix\Database\ConnectionInterface;
use Jelix\Database\Utilities;
use Jelix\IniFile\Util;

/**
 * Provides utilities methods for SQL
 */
abstract class AbstractSqlTools implements SqlToolsInterface
{
    public $trueValue = '1';

    public $falseValue = '0';

    /**
     * the database connector.
     *
     * @var ConnectionInterface
     */
    protected $_conn;

    /**
     * @param ConnectionInterface $connector the connection to a database
     */
    public function __construct(?ConnectionInterface $connector = null)
    {
        $this->_conn = $connector;
    }

    /**
     * @return ConnectionInterface
     */
    public function getConnection() {
        return $this->_conn;
    }

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
     * Get informations about the given SQL type.
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
    public function getTypeInfo($nativeType)
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
    public function unifiedToPHPType($unifiedType)
    {
        if (isset($this->unifiedToPhp[$unifiedType])) {
            return $this->unifiedToPhp[$unifiedType];
        }

        throw new Exception('bad unified type name:'.$unifiedType);
    }

    /**
     * @param string $unifiedType the unified type name
     * @param string $value       the value
     * @param mixed  $checkNull
     *
     * @return string the php value corresponding to the type
     *
*/
    public function stringToPhpValue($unifiedType, $value, $checkNull = false)
    {
        if ($checkNull && ($value === null || strtolower($value) == 'null')) {
            return null;
        }
        switch ($this->unifiedToPHPType($unifiedType)) {
            case 'boolean':
                return $this->getBooleanValue($value) == $this->trueValue;
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
    public function parseSQLType($type)
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
     * @param mixed  $checkNull
     * @param mixed  $toPhpSource
     *
     * @return string the value which is ready to include a SQL query string
     *
*/
    public function escapeValue($unifiedType, $value, $checkNull = false, $toPhpSource = false)
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
                if ($toPhpSource) {
                    if ($unifiedType == 'varbinary' || $unifiedType == 'binary') {
                        return '\'.$this->_conn->quote2(\''.str_replace('\'', '\\\'', $value).'\',true,true).\'';
                    }
                    if (strpos($value, "'") !== false) {
                        return '\'.$this->_conn->quote(\''.str_replace('\'', '\\\'', $value).'\').\'';
                    }

                    return "\\'".$value."\\'";
                } elseif ($this->_conn) {
                    return $this->_conn->quote($value);
                }

                return "'".addslashes($value)."'";
        }
    }

    /**
     * @param bool|string $value a value which is a boolean
     *
     * @return string the string value representing a boolean in SQL
     *
     */
    public function getBooleanValue($value)
    {
        if (is_string($value)) {
            $value = strtolower($value);
        }
        if ($value === 'true' || $value === true || intval($value) === 1 || $value === 't' || $value === 'on') {
            return $this->trueValue;
        }

        return $this->falseValue;
    }

    /**
     * Enclose a field name or a table name.
     *
     * @param string $fieldName the field/table name
     *
     * @return string the enclosed name
     *
     */
    public function encloseName($fieldName)
    {
        return $fieldName;
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

    public function parseSQLFunctionAndConvert($expression)
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
    public function getNativeSQLFunction($name, $parametersString = null)
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

    /**
     * returns the list of tables.
     *
     * @throws Exception
     *
     * @return string[] list of table names
     * @deprecated use SchemaInterface::getTables() instead
      */
    public function getTableList()
    {
        $list = $this->_conn->schema()->getTables();

        return array_keys($list);
    }

    /**
     * Retrieve the list of fields of a table.
     *
     * @param string $tableName  the name of the table
     * @param string $sequence   the sequence used to auto increment the primary key
     * @param string $schemaName the name of the schema (only for PostgreSQL)
     *
     * @return FieldProperties[] keys are field names
     * @deprecated use SchemaInterface objects instead
     */
    abstract public function getFieldList($tableName, $sequence = '', $schemaName = '');

    /**
     * regular expression to detect comments and end of query.
     */
    protected $dbmsStyle = array('/^\s*#/', '/;\s*$/');

    /**
     * execute a list of queries stored in a file.
     *
     * @param string $file path of the sql file
     */
    public function execSQLScript($file)
    {

        $prefix = $this->_conn->getTablePrefix();

        $lines = file($file);
        $cmdSQL = '';
        $nbCmd = 0;

        $style = $this->dbmsStyle;

        foreach ((array) $lines as $key => $line) {
            if ((!preg_match($style[0], $line)) && (strlen(trim($line)) > 0)) { // The line isn't empty and isn't a comment
                //$line = str_replace("\\'","''",$line);
                //$line = str_replace($this->scriptReplaceFrom, $this->scriptReplaceBy,$line);

                $cmdSQL .= $line;

                if (preg_match($style[1], $line)) {
                    // If at the last line of the command, execute it
                    // Cleanup the command from the ending ";" and execute it
                    $cmdSQL = preg_replace($style[1], '', $cmdSQL);
                    $cmdSQL = str_replace('%%PREFIX%%', $prefix, $cmdSQL);
                    $this->_conn->exec($cmdSQL);
                    ++$nbCmd;
                    $cmdSQL = '';
                }
            }
        }

        return $nbCmd;
    }

    /**
     *
     * @param string[] $columns list of column names
     *
     * @return string the list in SQL
     */
    public function getSQLColumnsList($columns)
    {
        $cols = array();
        foreach ($columns as $col) {
            $cols[] = $this->_conn->encloseName($col);
        }

        return implode(',', $cols);
    }

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
    public function parseCREATETABLE($createTableStatement)
    {
        $result = array(
            'name' => '',
            'tableName' => null,
            'temporary' => false,
            'ifnotexists' => false,
            'columns' => array(),
            'constraints' => array(),
            'options' => '',
        );

        if (!preg_match('/^\\s*CREATE\\s+(TEMP(?:ORARY)?\\s+)?TABLE\\s+(IF\\s+NOT\\s+EXISTS\\s+)?([^(]+)/msi', $createTableStatement, $m)) {
            return false;
        }
        $result['temporary'] = (bool) ($m[1]);
        $result['ifnotexists'] = (bool) ($m[2]);

        // remove enclose characters
        $name = preg_replace('/[`"\[\]\']/', "", trim($m[3]));
        
        $tableName = $this->_conn->createTableName($name);
        $result['name'] = $tableName->getTableName();
        $result['tableName'] = $tableName;

        $posStart = strlen($m[0]);
        $posEnd = strrpos($createTableStatement, ')');
        $result['options'] = trim(substr($createTableStatement, $posEnd + 1));

        $def = substr($createTableStatement, $posStart + 1, $posEnd - $posStart - 1);

        $tokens = preg_split('/([,()])/msi', $def, -1, PREG_SPLIT_DELIM_CAPTURE);

        $regexpConstraint = '/^\\s*(CONSTRAINT|CHECK|UNIQUE|PRIMARY|EXCLUDE|FOREIGN|FULLTEXT|SPATIAL|INDEX|KEY)/msi';
        $columns = array();
        $constraints = array();
        $level = 0;
        $currentDef = '';
        foreach ($tokens as $token) {
            if ($token == '(') {
                ++$level;
                $currentDef .= $token;
            } elseif ($token == ')') {
                --$level;
                if ($level < 0) {
                    $level = 0;
                }
                $currentDef .= $token;
            } elseif ($token == ',') {
                if ($level > 0) {
                    $currentDef .= $token;
                } else {
                    // new current definition
                    $currentDef = trim(preg_replace('/\\s+/', ' ', $currentDef));
                    if (preg_match($regexpConstraint, $currentDef)) {
                        $constraints[] = $currentDef;
                    } else {
                        $columns[] = $currentDef;
                    }
                    $currentDef = '';
                }
            } else {
                $currentDef .= $token;
            }
        }
        if ($currentDef) {
            $currentDef = trim(preg_replace('/\\s+/', ' ', $currentDef));
            if (preg_match($regexpConstraint, $currentDef)) {
                $constraints[] = $currentDef;
            } else {
                $columns[] = $currentDef;
            }
        }

        $result['columns'] = $columns;
        $result['constraints'] = $constraints;

        return $result;
    }

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
    public function insertBulkData($tableName, $columns, $data, $primaryKey = null, $options = 0)
    {
        $tableName = $this->_conn->createTableName($tableName);
        $enclosedTableName = $tableName->getEnclosedFullName();

        if ($options == self::IBD_INSERT_ONLY_IF_TABLE_IS_EMPTY) {
            $rs = $this->_conn->query("SELECT count(*) as _cnt_ FROM ".$enclosedTableName);
            if ($rs) {
                $rec = $rs->fetch();
                if (intval($rec->_cnt_) > 0) {
                    return 0;
                }
            }
        }
        if ($primaryKey && !is_array($primaryKey)) {
            $primaryKey = array($primaryKey);
        }

        $checkExist = ($primaryKey &&
            ($options == self::IBD_IGNORE_IF_EXIST ||
                $options == self::IBD_UPDATE_IF_EXIST));

        $sqlColumns = array();
        $pki = 0;
        $pkIndexes = array();
        $sqlPk = array();
        foreach ($columns as $k => $col) {
            if ($checkExist &&
                count($primaryKey) > $pki &&
                $primaryKey[$pki] == $col
            ) {
                $pkIndexes[$k] = $this->_conn->encloseName($col);
                $sqlPk[] = $pkIndexes[$k];
                ++$pki;
            } else {
                $pkIndexes[$k] = false;
            }
            $sqlColumns[] = $this->_conn->encloseName($col);
        }

        $sqlInsert = 'INSERT INTO '.$enclosedTableName.' ('.
            implode(',', $sqlColumns).') VALUES (';
        if ($checkExist) {
            $sqlCheck = 'SELECT '.implode(',', $sqlPk).' FROM '.$enclosedTableName.' WHERE ';
        }

        $this->_conn->beginTransaction();

        if ($options == self::IBD_EMPTY_TABLE_BEFORE) {
            $this->_conn->exec("DELETE FROM ".$enclosedTableName);
        }
        $recCount = 0;
        foreach ($data as $rk => $row) {
            $values = array();
            if (count($row) != count($columns)) {
                $this->_conn->rollback();

                throw new Exception("insertBulkData: row {$rk} does not content right values count");
            }
            $sqlPk = array();
            $sqlUpdateValue = array();
            foreach ($row as $vk => $value) {
                $op = '=';
                switch (gettype($value)) {
                    case 'boolean':
                        $val = $this->getBooleanValue($value);

                        break;
                    case 'integer':
                        $val = (string) $value;

                        break;
                    case 'double':
                        $val = Utilities::floatToStr($value);

                        break;
                    case 'string':
                        $val = $this->_conn->quote($value);

                        break;
                    case 'NULL':
                        $val = 'NULL';
                        $op = 'IS';

                        break;
                    case 'array':
                    case 'object':
                        $val = $this->_conn->quote(json_encode($value));
                        break;
                    default:
                        $this->_conn->rollback();

                        throw new Exception('insertBulkData: Unexpected value type to insert into the database, '.$rk.':'.$vk);

                        break;
                }
                $values[] = $val;
                if ($pkIndexes[$vk] !== false) {
                    $sqlPk[] = $pkIndexes[$vk]." {$op} {$val}";
                } elseif ($options == self::IBD_UPDATE_IF_EXIST) {
                    $sqlUpdateValue[] = $sqlColumns[$vk]." = {$val}";
                }
            }

            if ($checkExist) {
                $rs = $this->_conn->query($sqlCheck.implode(' AND ', $sqlPk));
                if ($rs && $rs->fetch()) {
                    if ($options == self::IBD_IGNORE_IF_EXIST) {
                        continue;
                    }
                    if ($options == self::IBD_UPDATE_IF_EXIST) {
                        $sqlUpdate = 'UPDATE '.$enclosedTableName.' SET ';
                        $sqlUpdate .= implode(',', $sqlUpdateValue);
                        $sqlUpdate .= ' WHERE '.implode(' AND ', $sqlPk);
                        $this->_conn->exec($sqlUpdate);
                        ++$recCount;

                        continue;
                    }
                }
            }
            $this->_conn->exec($sqlInsert.implode(',', $values).')');
            ++$recCount;
        }
        $this->_conn->commit();

        return $recCount;
    }
}
