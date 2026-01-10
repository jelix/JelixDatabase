<?php
/**
 * @author     Gérald Croes, Laurent Jouanneau
 * @contributor Laurent Jouanneau, Julien Issler
 * @copyright  2001-2005 CopixTeam, 2005-2026 Laurent Jouanneau, 2008 Julien Issler
 *
 * @see        https://jelix.org
 * @licence     http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */
namespace Jelix\Database\Schema;

use Jelix\Database\Connection;
use Jelix\Database\ConnectionInterface;
use Jelix\Database\Utilities;

/**
 * Provides utilities methods for SQL
 */
abstract class AbstractSqlTools extends \jDbTools implements SqlToolsInterface
{
    /**
     * @deprecated use SQLSyntaxHelpers::trueValue const instead
     * @see Connection::getSqlSyntaxHelpers()
     */
    public $trueValue = '1';

    /**
     * @deprecated use SQLSyntaxHelpers::falseValue const instead
     * @see Connection::getSqlSyntaxHelpers()
     */
    public $falseValue = '0';

    /**
     * the database connector.
     *
     * @var ConnectionInterface
     */
    protected $_conn;

    /**
     * @var SQLSyntaxHelpersInterface
     * @deprecated
     */
    protected $_syntax;

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

    /**
     * @inheritDoc
     * @deprecated
     */
    public function getTypeInfo($nativeType)
    {
        return $this->_syntax->getTypeInfo($nativeType);
    }

    /**
     * @inheritDoc
     * @deprecated
     */
    public function unifiedToPHPType($unifiedType)
    {
        return $this->_syntax->unifiedToPHPType($unifiedType);
    }

    /**
     * @inheritDoc
     * @deprecated
     */
    public function stringToPhpValue($unifiedType, $value, $checkNull = false)
    {
        return $this->_syntax->stringToPhpValue($unifiedType, $value, $checkNull);
    }

    /**
     * @inheritDoc
     * @deprecated
     */
    public function parseSQLType($type)
    {
        return $this->_syntax->parseSQLType($type);
    }

    /**
     * @inheritDoc
     * @deprecated
     */
    public function escapeValue($unifiedType, $value, $checkNull = false, $toPhpSource = false)
    {
        if ($toPhpSource) {
            return $this->_syntax->escapeValueAsPHPSource($unifiedType, $value, $checkNull);
        }
        return $this->_syntax->escapeValue($unifiedType, $value, $checkNull);
    }

    /**
     * @inheritDoc
     * @deprecated
     */
    public function getBooleanValue($value)
    {
        return $this->_syntax->getBooleanValue($value);
    }

    /**
     * @inheritDoc
     * @deprecated
     */
    public function encloseName($fieldName)
    {
        return $this->_syntax->encloseName($fieldName);
    }

    /**
     * @inheritDoc
     * @deprecated
     */
    public function parseSQLFunctionAndConvert($expression)
    {
        return $this->_syntax->parseSQLFunctionAndConvert($expression);
    }

    /**
     * @inheritDoc
     * @deprecated
     */
    public function getNativeSQLFunction($name, $parametersString = null)
    {
        return $this->_syntax->getNativeSQLFunction($name, $parametersString);
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
                        $val = $this->_syntax->getBooleanValue($value);

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
