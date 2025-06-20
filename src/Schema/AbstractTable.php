<?php
/**
 * @author     Laurent Jouanneau
 * @copyright  2010-2024 Laurent Jouanneau
 *
 * @see        https://jelix.org
 * @licence     http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */
namespace Jelix\Database\Schema;

abstract class AbstractTable implements TableInterface
{
    /**
     * @var TableNameInterface the name of the table
     */
    protected $tableName;

    /**
     * @var AbstractSchema the schema which holds the table
     */
    protected $schema;

    /**
     * @var Column[]. null means "columns are not loaded"
     */
    protected $columns;

    /**
     * @var PrimaryKey the primary key. null means "primary key is not loaded". false means : no primary key
     */
    protected $primaryKey;

    /**
     * @var UniqueKey[] list unique keys. null means "unique key are not loaded"
     */
    protected $uniqueKeys;

    /**
     * @var Index[] list of indexes. null means "indexes are not loaded"
     */
    protected $indexes;

    /**
     * @var Reference[] list of references. null means "references are not loaded"
     */
    protected $references;

    public $attributes = array();
    /**
     * @param TableNameInterface    $name   the table name
     * @param SchemaInterface $schema
     */
    public function __construct(TableNameInterface $name, SchemaInterface $schema)
    {
        $this->tableName = $name;
        $this->schema = $schema;
    }

    public function getName()
    {
        return $this->tableName->getTableName();
    }

    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * @return Column[]
     */
    public function getColumns()
    {
        if ($this->columns === null) {
            $this->_loadTableDefinition();
        }

        return $this->columns;
    }

    public function getColumn($name, $forChange = false)
    {
        if ($this->columns === null) {
            $this->_loadTableDefinition();
        }
        if (isset($this->columns[$name])) {
            if ($forChange) {
                return clone $this->columns[$name];
            }

            return $this->columns[$name];
        }

        return null;
    }

    /**
     * add a column.
     *
     * @return bool true if the column is added, false if not (already there)
     */
    public function addColumn(Column $column)
    {
        if ($this->columns === null) {
            $this->_loadTableDefinition();
        }
        if (isset($this->columns[$column->name])) {
            if ($this->columns[$column->name]->isEqualTo($column)) {
                return false;
            }
            $this->_alterColumn($this->columns[$column->name], $column);
            $this->columns[$column->name] = $column;

            return true;
        }
        $this->_addColumn($column);
        $this->columns[$column->name] = $column;

        return false;
    }

    /**
     * change a column definition. If the column does not exist,
     * it is created.
     *
     * @param Column $column      the colum with its new properties
     * @param string    $oldName     the name of the column to change (if the name is changed)
     * @param bool      $doNotCreate true if the column shoul dnot be created when it does not exist
     *
     * @return bool true if changed/created
     */
    public function alterColumn(Column $column, $oldName = '', $doNotCreate = false)
    {
        $oldColumn = $this->getColumn(($oldName ?: $column->name));
        if (!$oldColumn) {
            if ($doNotCreate) {
                return false;
            }
            $this->addColumn($column);

            return true;
        }

        if (!$column->nativeType) {
            $type = $this->schema->getConn()->tools()->getTypeInfo($column->type);
            $column->nativeType = $type[0];
        }

        if ($oldColumn->isEqualTo($column)) {
            return false;
        }
        // FIXME : if rename, modify indexes and table constraints that have this column
        $this->_alterColumn($oldColumn, $column);
        if ($oldName) {
            unset($this->columns[$oldName]);
        }
        $this->columns[$column->name] = $column;

        return true;
    }

    public function dropColumn($name)
    {
        if ($this->columns === null) {
            $this->_loadTableDefinition();
        }
        if (!isset($this->columns[$name])) {
            return;
        }
        $this->_dropColumn($this->columns[$name]);

        // FIXME : remove/modify indexes and table constraints that have this column
        unset($this->columns[$name]);
    }

    /**
     *	@return false|PrimaryKey  false if there is no primary key
     */
    public function getPrimaryKey()
    {
        if ($this->primaryKey === null) {
            $this->_loadTableDefinition();
        }

        return $this->primaryKey;
    }

    public function setPrimaryKey(PrimaryKey $key)
    {
        $pk = $this->getPrimaryKey();
        if ($pk == $key) {
            return;
        }
        if ($pk !== false) {
            $this->_replaceConstraint($pk, $key);
        } else {
            $this->_createConstraint($key);
        }
        $this->primaryKey = $key;
    }

    public function dropPrimaryKey()
    {
        $pk = $this->getPrimaryKey();
        if ($pk !== false) {
            $this->_dropConstraint($pk);
            $this->primaryKey = false;
        }
    }

    /**
     * @return Index[]
     */
    public function getIndexes()
    {
        if ($this->indexes === null) {
            $this->_loadTableDefinition();
        }

        return $this->indexes;
    }

    /**
     * @param mixed $name
     *
     * @return null|Index
     */
    public function getIndex($name)
    {
        if ($this->indexes === null) {
            $this->_loadTableDefinition();
        }
        if (isset($this->indexes[$name])) {
            return $this->indexes[$name];
        }

        return null;
    }

    public function addIndex(Index $index)
    {
        $this->alterIndex($index);
    }

    public function alterIndex(Index $index)
    {
        if (trim($index->name) == '') {
            throw new Exception('Index should have name');
        }
        $idx = $this->getIndex($index->name);
        if ($idx) {
            $this->_dropIndex($idx);
        }
        $this->_createIndex($index);
        $this->indexes[$index->name] = $index;
    }

    public function dropIndex($indexName)
    {
        $idx = $this->getIndex($indexName);
        if ($idx) {
            $this->_dropIndex($idx);
            unset($this->indexes[$indexName]);
        }
    }

    /**
     * @return UniqueKey[]
     */
    public function getUniqueKeys()
    {
        if ($this->uniqueKeys === null) {
            $this->_loadTableDefinition();
        }

        return $this->uniqueKeys;
    }

    /**
     * @param mixed $name
     *
     * @return null|UniqueKey
     */
    public function getUniqueKey($name)
    {
        if ($this->uniqueKeys === null) {
            $this->_loadTableDefinition();
        }
        if (isset($this->uniqueKeys[$name])) {
            return $this->uniqueKeys[$name];
        }

        return null;
    }

    public function addUniqueKey(UniqueKey $key)
    {
        if (trim($key->name) == '') {
            $key->name = $this->name.'_'.implode('_', $key->columns).'_unique';
        }
        $this->alterUniqueKey($key);
    }

    public function alterUniqueKey(UniqueKey $key)
    {
        $idx = $this->getUniqueKey($key->name);
        if ($idx) {
            $this->_replaceConstraint($idx, $key);
            unset($this->uniqueKeys[$idx->name]);
        } else {
            $this->_createConstraint($key);
        }
        $this->uniqueKeys[$key->name] = $key;
    }

    public function dropUniqueKey($indexName)
    {
        $idx = $this->getUniqueKey($indexName);
        if ($idx) {
            $this->_dropConstraint($idx);
            unset($this->uniqueKeys[$idx->name]);
        }
    }

    /**
     * @return Reference[]
     */
    public function getReferences()
    {
        if ($this->references === null) {
            $this->_loadTableDefinition();
        }

        return $this->references;
    }

    /**
     * @param mixed $refName
     *
     * @return null|Reference
     */
    public function getReference($refName)
    {
        if ($this->references === null) {
            $this->_loadTableDefinition();
        }

        if (isset($this->references[$refName])) {
            return $this->references[$refName];
        }

        return null;
    }

    public function addReference(Reference $reference)
    {
        if (trim($reference->name) == '') {
            $reference->name = $this->tableName->getRealTableName().'_'.implode('_', $reference->columns).'_fkey';
        }
        $this->alterReference($reference);
    }

    public function alterReference(Reference $reference)
    {
        $ref = $this->getReference($reference->name);
        if ($ref) {
            $this->_replaceConstraint($ref, $reference);
            unset($this->references[$ref->name]);
        } else {
            $this->_createConstraint($reference);
        }
        $this->references[$reference->name] = $reference;
    }

    public function dropReference($refName)
    {
        $ref = $this->getReference($refName);
        if ($ref) {
            $this->_dropConstraint($ref);
            unset($this->references[$ref->name]);
        }
    }

    protected function _loadTableDefinition()
    {
        $this->_loadColumns();
        $this->_loadIndexesAndKeys();
        $this->_loadReferences();
    }

    abstract protected function _loadColumns();

    abstract protected function _alterColumn(Column $old, Column $new);

    abstract protected function _addColumn(Column $new);

    protected function _dropColumn(Column $col)
    {
        $conn = $this->schema->getConn();
        $sql = 'ALTER TABLE '.$this->tableName->getEnclosedFullName().
            ' DROP COLUMN '.$conn->encloseName($col->name);
        $conn->exec($sql);
    }

    abstract protected function _loadIndexesAndKeys();

    abstract protected function _loadReferences();

    abstract protected function _createIndex(Index $index);

    abstract protected function _dropIndex(Index $index);

    abstract protected function _createConstraint(AbstractConstraint $constraint);

    abstract protected function _dropConstraint(AbstractConstraint $constraint);

    protected function _replaceConstraint(AbstractConstraint $oldConstraint, AbstractConstraint $newConstraint)
    {
        $this->_dropConstraint($oldConstraint);
        $this->_createConstraint($newConstraint);
    }
}
