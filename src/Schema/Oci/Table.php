<?php
/**
 * @author      Laurent Jouanneau
 * @contributor Gwendal Jouannic
 *
 * @copyright   2008 Gwendal Jouannic, 2009-2020 Laurent Jouanneau
 *
 * @see        https://jelix.org
 * @licence     http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */
namespace Jelix\Database\Schema\Oci;

use Jelix\Database\Schema\AbstractConstraint;
use Jelix\Database\Schema\AbstractTable;
use Jelix\Database\Schema\Column;
use Jelix\Database\Exception;
use Jelix\Database\Schema\Index;
use Jelix\Database\Schema\PrimaryKey;
use Jelix\Database\Schema\Reference;
use Jelix\Database\Schema\UniqueKey;

/**
 */
class Table extends AbstractTable
{
    public function getPrimaryKey()
    {
        if ($this->primaryKey === null) {
            $this->_loadColumns();
        }

        return $this->primaryKey;
    }

    protected function _loadColumns()
    {
        $conn = $this->schema->getConn();
        $results = array();

        $query = 'SELECT COLUMN_NAME, DATA_TYPE, DATA_LENGTH, DATA_PRECISION, DATA_SCALE, NULLABLE, DATA_DEFAULT,  
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
                    WHERE UTC.TABLE_NAME = \''.strtoupper($this->tableName->getTableName()).'\'';

        $rs = $conn->query($query);
        $tools = new SQLTools($conn);

        while ($line = $rs->fetch()) {
            $name = strtolower($line->column_name);
            $type = strtolower($line->data_type);
            $length = intval($line->data_length);

            $typeinfo = $tools->getTypeInfo($type);
            $phpType = $tools->unifiedToPHPType($typeinfo[1]);
            $maxLength = $typeinfo[5];
            if ($phpType == 'string') {
                $maxLength = $length;
            }

            $notNull = ($line->nullable == 'N');
            $isPrimary = $line->constraint_type == 'P';
            $hasDefault = false;
            $default = '';
            if ($line->data_default !== null || !($line->data_default === null && $notNull)) {
                $hasDefault = true;
                $default = $line->data_default;
            }

            $col = new Column($name, $type, $length, $hasDefault, $default, $notNull);
            $col->nativeType = $typeinfo[0];
            $col->maxValue = $typeinfo[3];
            $col->minValue = $typeinfo[2];
            $col->maxLength = $maxLength;
            $col->minLength = $typeinfo[4];
            $col->scale = intval($line->data_scale);
            $col->precision = intval($line->data_precision);

            // FIXME, retrieve autoincrement property for other field than primary key
            if ($isPrimary) {
                $sequence = $this->_getAISequenceName($this->tableName->getTableName(), $name);
                if ($sequence != '') {
                    $sqlai = "SELECT 'Y' FROM USER_SEQUENCES US
                                WHERE US.SEQUENCE_NAME = '".$sequence."'";
                    $rsai = $conn->query($sqlai);
                    if ($rsai->fetch()) {
                        $col->autoIncrement = true;
                        $col->sequence = $sequence;
                    }
                }
            }

            $this->columns[$name] = $col;

            if ($isPrimary) {
                if (!$this->primaryKey) {
                    $this->primaryKey = new PrimaryKey($name);
                } else {
                    $this->primaryKey->columns[] = $name;
                }
            }
        }
        if ($this->primaryKey === null) {
            $this->primaryKey = false;
        }
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

    protected function _alterColumn(Column $old, Column $new)
    {
        throw new Exception('Not Implemented');
    }

    protected function _addColumn(Column $new)
    {
        throw new Exception('Not Implemented');
    }

    protected function _loadIndexesAndKeys()
    {
        throw new Exception('Not Implemented');
    }

    protected function _createIndex(Index $index)
    {
        throw new Exception('Not Implemented');
    }

    protected function _dropIndex(Index $index)
    {
        throw new Exception('Not Implemented');
    }

    protected function _loadReferences()
    {
        throw new Exception('Not Implemented');
    }

    protected function _createConstraint(AbstractConstraint $constraint)
    {
        throw new Exception('Not Implemented');
    }

    protected function _dropConstraint(AbstractConstraint $constraint)
    {
        throw new Exception('Not Implemented');
    }
}
