<?php
/**
 * @author     Gérald Croes, Laurent Jouanneau
 * @contributor Laurent Jouanneau
 * @contributor Florian Lonqueu-Brochard
 *
 * @copyright  2001-2005 CopixTeam, 2005-2026 Laurent Jouanneau
 * @copyright  2012 Florian Lonqueu-Brochard
 *
 * @see      https://jelix.org
 * @licence  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */
namespace Jelix\Database\Schema\Mysql;

use Jelix\Database\Connection;
use Jelix\Database\ConnectionInterface;
use Jelix\Database\Schema\FieldProperties;

/**
 * Provides utilities methods for a mysql database.
 *
 */
class SQLTools extends \Jelix\Database\Schema\AbstractSqlTools
{
    public function __construct(?ConnectionInterface $connector = null)
    {
        parent::__construct($connector);
        $this->_syntax = Connection::getSqlSyntaxHelpers(Connection::DB_TYPE_MYSQL);
    }

    /**
     * retrieve the list of fields of a table.
     *
     * @param string $tableName  the name of the table
     * @param string $sequence   the sequence used to auto increment the primary key (not supported here)
     * @param string $schemaName the name of the schema (only for PostgreSQL, not supported here)
     *
     * @return array keys are field names and values are jDbFieldProperties objects
     */
    public function getFieldList($tableName, $sequence = '', $schemaName = '')
    {
        $tableName = $this->_conn->prefixTable($tableName);
        $results = array();

        // get FULL table information (to get comment for label form)
        $rs = $this->_conn->query('SHOW FULL FIELDS FROM `'.$tableName.'`');

        while ($line = $rs->fetch()) {
            $field = new FieldProperties();

            if (preg_match('/^(\w+)\s*(\((\d+)\))?.*$/', $line->Type, $m)) {
                $field->type = strtolower($m[1]);
                if ($field->type == 'varchar' && isset($m[3])) {
                    $field->length = intval($m[3]);
                }
            } else {
                $field->type = $line->Type;
            }
            $typeinfo = $this->_syntax->getTypeInfo($field->type);
            $field->unifiedType = $typeinfo[1];
            $field->maxValue = $typeinfo[3];
            $field->minValue = $typeinfo[2];
            $field->maxLength = $typeinfo[5];
            $field->minLength = $typeinfo[4];
            if ($field->length != 0) {
                $field->maxLength = $field->length;
            }

            $field->name = $line->Field;
            $field->notNull = ($line->Null == 'NO');
            $field->primary = ($line->Key == 'PRI');
            $field->autoIncrement = ($line->Extra == 'auto_increment');
            $field->hasDefault = ($line->Default != '' || !($line->Default == null && $field->notNull));
            // use Mysql comment on dao and form
            if (isset($line->Comment) && $line->Comment != '') {
                $field->comment = $line->Comment;
            }
            // to fix a bug in php 5.2.5 or mysql 5.0.51
            if ($field->notNull && $line->Default === null && !$field->autoIncrement) {
                $field->default = '';
            } else {
                $field->default = $line->Default;
            }
            $results[$line->Field] = $field;
        }

        return $results;
    }

    public function execSQLScript($file)
    {
        $prefix = $this->_conn->getTablePrefix();
        $sqlQueries = str_replace('%%PREFIX%%', $prefix, file_get_contents($file));
        $queries = $this->parseSQLScript($sqlQueries);
        foreach ($queries as $query) {
            $this->_conn->exec($query);
        }

        return count($queries);
    }

    /**
     * @param mixed $script
     */
    protected function parseSQLScript($script)
    {
        $distinctDelimiters = array(';');
        if (preg_match_all("/DELIMITER ([^\n]*)/i", $script, $d, PREG_SET_ORDER)) {
            $delimiters = $d[1];
            $distinctDelimiters = array_unique(array_merge($distinctDelimiters, $delimiters));
        }
        $preg = '';
        foreach ($distinctDelimiters as $dd) {
            $preg .= '|'.preg_quote($dd);
        }

        $tokens = preg_split('!(\'|"|\\\\|`|DELIMITER |#|/\\*|\\*/|\\-\\-(?=\s)|'."\n".$preg.')!i', $script, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        $currentDelimiter = ';';
        $context = 0;
        $queries = array();
        $query = '';
        $previousToken = '';
        foreach ($tokens as $k => $token) {
            switch ($context) {
                // 0 : statement
                case 0:
                    $previousToken = $token;
                    switch ($token) {
                        case $currentDelimiter:
                            if (preg_replace('/\\s/', '', $query) != '') {
                                $queries[] = trim($query);
                            }
                            $query = '';

                            break;
                        case '\'':
                            $context = 1;
                            $previousToken = '';
                            $query .= $token;

                            break;
                        case '"':
                            $context = 2;
                            $previousToken = '';
                            $query .= $token;

                            break;
                        case '`':
                            $context = 3;
                            $query .= $token;
                            $previousToken = '';

                            break;
                        case 'DELIMITER ':
                            $context = 6;

                            break;
                        case '#':
                        case '--':
                            $context = 4;

                            break;
                        case '/*':
                            $context = 5;

                            break;
                        case "\n":
                        default:
                            $query .= $token;
                    }

                    break;
                // 1 : string '
                case 1:
                    if ($token == "'") {
                        if ($previousToken != '\\' && $previousToken != "'") {
                            if (isset($tokens[$k + 1])) {
                                if ($tokens[$k + 1] != "'") {
                                    $context = 0;
                                }
                            } else {
                                $context = 0;
                            }
                        }
                    }
                    $previousToken = $token;
                    $query .= $token;

                    break;
                // 2 : string "
                case 2:
                    if ($token == '"') {
                        if ($previousToken != '\\' && $previousToken != '"') {
                            if (isset($tokens[$k + 1])) {
                                if ($tokens[$k + 1] != '"') {
                                    $context = 0;
                                }
                            } else {
                                $context = 0;
                            }
                        }
                    }
                    $previousToken = $token;
                    $query .= $token;

                    break;
                // 3 : name with `
                case 3:
                    if ($token == '`') {
                        if ($previousToken != '\\' && $previousToken != '`') {
                            if (isset($tokens[$k + 1])) {
                                if ($tokens[$k + 1] != '`') {
                                    $context = 0;
                                }
                            } else {
                                $context = 0;
                            }
                        }
                    }
                    $previousToken = $token;
                    $query .= $token;

                    break;
                // 4 : comment single line
                case 4:
                    if ($token == "\n") {
                        //$query.=$token;
                        $context = 0;
                    }

                    break;
                // 5 : comment multi line
                case 5:
                    if ($token == '*/') {
                        $context = 0;
                    }

                    break;
                // 6 : delimiter definition
                case 6:
                    $currentDelimiter = $token;
                    $context = 0;

                    break;
            }
        }
        if (preg_replace('/\\s/', '', $query) != '') {
            $queries[] = trim($query);
        }

        return $queries;
    }
}
