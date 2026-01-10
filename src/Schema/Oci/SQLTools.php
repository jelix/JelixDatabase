<?php
/**
 * @author     Gwendal Jouannic
 * @contributor Laurent Jouanneau
 *
 * @copyright  2008 Gwendal Jouannic, 2009-2026 Laurent Jouanneau
 *
 * @see      https://jelix.org
 * @licence  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */
namespace Jelix\Database\Schema\Oci;

use Jelix\Database\Connection;
use Jelix\Database\ConnectionInterface;
use Jelix\Database\Schema\FieldProperties;

class SQLTools extends \Jelix\Database\Schema\AbstractSqlTools
{
    public function __construct(?ConnectionInterface $connector = null)
    {
        parent::__construct($connector);
        $this->_syntax = Connection::getSqlSyntaxHelpers(Connection::DB_TYPE_ORACLE);
    }

    /**
     * retrieve the list of fields of a table.
     *
     * @param string $tableName  the name of the table
     * @param string $sequence   the sequence used to auto increment the primary key
     * @param string $schemaName the name of the schema (only for PostgreSQL, not supported here)
     *
     * @return FieldProperties[] keys are field names and values are jDbFieldProperties objects
     */
    public function getFieldList($tableName, $sequence = '', $schemaName = '')
    {
        $tableName = $this->_conn->prefixTable($tableName);
        $results = array();

        $query = 'SELECT COLUMN_NAME, DATA_TYPE, DATA_LENGTH, NULLABLE, DATA_DEFAULT,  
                        (SELECT CONSTRAINT_TYPE 
                         FROM USER_CONSTRAINTS UC, USER_CONS_COLUMNS UCC 
                         WHERE UCC.TABLE_NAME = UTC.TABLE_NAME
                            AND UC.TABLE_NAME = UTC.TABLE_NAME
                            AND UCC.COLUMN_NAME = UTC.COLUMN_NAME
                            AND UC.CONSTRAINT_NAME = UCC.CONSTRAINT_NAME
                            AND UC.CONSTRAINT_TYPE = \'P\') AS CONSTRAINT_TYPE,  
                        (SELECT COMMENTS 
                         FROM USER_COL_COMMENTS UCCM
                         WHERE UCCM.TABLE_NAME = UTC.TABLE_NAME
                         AND UCCM.COLUMN_NAME = UTC.COLUMN_NAME) AS COLUMN_COMMENT
                    FROM USER_TAB_COLUMNS UTC 
                    WHERE UTC.TABLE_NAME = \''.strtoupper($tableName).'\'';

        $rs = $this->_conn->query($query);

        while ($line = $rs->fetch()) {
            $field = new FieldProperties();

            $field->name = strtolower($line->column_name);
            $field->type = strtolower($line->data_type);

            $typeinfo = $this->_syntax->getTypeInfo($field->type);
            $field->unifiedType = $typeinfo[1];
            $field->maxValue = $typeinfo[3];
            $field->minValue = $typeinfo[2];
            $field->maxLength = $typeinfo[5];
            $field->minLength = $typeinfo[4];

            if ($field->type == 'varchar2' || $field->type == 'varchar') {
                $field->length = intval($line->data_length);
                $field->maxLength = $field->length;
            }

            $field->notNull = ($line->nullable == 'N');
            $field->primary = ($line->constraint_type == 'P');

            if (isset($line->column_comment) && !empty($line->column_comment)) {
                $field->comment = $line->column_comment;
            }

            // FIXME, retrieve autoincrement property for other field than primary key
            if ($field->primary) {
                if ($sequence == '') {
                    $sequence = $this->_getAISequenceName($tableName, $field->name);
                }
                if ($sequence != '') {
                    $sqlai = "SELECT 'Y' FROM USER_SEQUENCES US
                                WHERE US.SEQUENCE_NAME = '".$sequence."'";
                    $rsai = $this->_conn->query($sqlai);
                    if ($rsai->fetch()) {
                        $field->autoIncrement = true;
                        $field->sequence = $sequence;
                    }
                }
            }

            if ($line->data_default !== null || !($line->data_default === null && $field->notNull)) {
                $field->hasDefault = true;
                $field->default = $line->data_default;
            }

            $results[$field->name] = $field;
        }

        return $results;
    }

    /**
     * Get the sequence name corresponding to an auto_increment field.
     *
     * @param mixed $tbName
     * @param mixed $clName
     *
     * @return string the sequence name, empty if not found
     */
    public function _getAISequenceName($tbName, $clName)
    {
        if (isset($this->_conn->profile['sequence_AI_pattern'])) {
            return preg_replace(
                array('/\*tbName\*/', '/\*clName\*/'),
                array(strtoupper($tbName), strtoupper($clName)),
                $this->_conn->profile['sequence_AI_pattern']
            );
        }

        return '';
    }
}
