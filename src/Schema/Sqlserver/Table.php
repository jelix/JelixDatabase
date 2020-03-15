<?php
/**
 * @author     Laurent Jouanneau
 * @copyright  2010-2020 Laurent Jouanneau
 *
 * @see        https://jelix.org
 * @licence     http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */
namespace Jelix\Database\Schema\Sqlserver;

use Jelix\Database\Schema\AbstractSchema;
use Jelix\Database\Schema\Exception;
use Jelix\Database\Schema\AbstractConstraint;
use Jelix\Database\Schema\AbstractTable;
use Jelix\Database\Schema\Column;
use Jelix\Database\Schema\PrimaryKey;
use Jelix\Database\Schema\Index;
use Jelix\Database\Schema\UniqueKey;
use Jelix\Database\Schema\Reference;


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
        $tools = $conn->tools();

        $sql = 'exec sp_columns @table_name = '.$conn->encloseName($this->name);
        $rs = $conn->query($sql);
        $tableOwner = null;
        while ($line = $rs->fetch()) {
            if ($tableOwner === null) {
                $tableOwner = $line->TABLE_OWNER;
            }
            $name = $line->COLUMN_NAME;
            $type = $line->TYPE_NAME;
            $autoIncrement = false;
            if ($type == 'int identity') {
                $type = 'int';
                $autoIncrement = true;
            } else {
                $pos = strpos($type, ' ');
                if ($pos !== false) {
                    $type = substr($type, 0, $pos);
                }
            }
            if ($type == 'bit') {
                $type = 'int';
            }
            $length = intval($line->LENGTH);
            $notNull = !($line->NULLABLE);
            $default = $line->COLUMN_DEF;
            $hasDefault = ($line->default != '');

            $col = new Column($name, $type, $length, $hasDefault, $default, $notNull);
            $col->autoIncrement = $autoIncrement;

            $typeinfo = $tools->getTypeInfo($type);
            $col->nativeType = $typeinfo[0];
            $col->maxValue = $typeinfo[3];
            $col->minValue = $typeinfo[2];
            $col->maxLength = $typeinfo[5];
            $col->minLength = $typeinfo[4];
            $col->precision = intval($line->PRECISION);
            $col->scale = intval($line->SCALE);
            if ($col->length != 0) {
                $col->maxLength = $col->length;
            }
            $this->columns[$name] = $col;
        }

        // get primary key info
        $sql = 'exec sp_pkeys @table_owner = '.$tableOwner.', @table_name = '.
            $conn->encloseName($this->name);
        $rs = $conn->query($sql);
        while ($line = $rs->fetch()) {
            if (!$this->primaryKey) {
                $this->primaryKey = new PrimaryKey('', $line->COLUMN_NAME);
            } else {
                $this->primaryKey->columns[] = $line->COLUMN_NAME;
            }
        }
        if ($this->primaryKey === null) {
            $this->primaryKey = false;
        }
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
