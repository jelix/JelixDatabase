<?php
/**
 * @author     Laurent Jouanneau
 * @contributor Aurélien Marcel
 *
 * @copyright  2017-2025 Laurent Jouanneau, 2011 Aurélien Marcel
 *
 * @see        https://jelix.org
 * @licence     http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */
namespace Jelix\Database\Schema;

use Jelix\Database\ConnectionInterface;

abstract class AbstractSchema extends \jDbSchema implements SchemaInterface
{
    /**
     * @var ConnectionInterface
     */
    protected $conn;

    public function __construct(ConnectionInterface $conn)
    {
        $this->conn = $conn;
    }

    /**
     * @return ConnectionInterface
     */
    public function getConn()
    {
        return $this->conn;
    }

    /**
     * create the given table if it does not exist.
     *
     * @param string       $name       the unprefixed table name, with an optional schema name
     * @param Column[]     $columns    list of columns
     * @param string|string[] $primaryKey the name of the column which contains the primary key
     * @param array           $attributes some table attributes specific to the database
     *
     * @return TableInterface the object corresponding to the created table
     */
    public function createTable($name, $columns, $primaryKey, $attributes = array())
    {
        $tableName = $this->conn->createTableName($name);
        $name = $tableName->getFullName();

        // be sure list of table is updated
        $this->tables = $this->_getTables();

        if (isset($this->tables[$name])) {
            return null;
        }

        $this->tables[$name] = $this->_createTable($tableName, $columns, $primaryKey, $attributes);

        return $this->tables[$name];
    }

    /**
     * load informations of the given.
     *
     * @param string $name the unprefixed table name, with optionnaly a schema
     *
     * @return TableInterface ready to make change
     */
    public function getTable($name)
    {
        $tableName = $this->conn->createTableName($name);
        $name = $tableName->getFullName();

        if ($this->tables === null) {
            $this->tables = $this->_getTables();
        }

        if (isset($this->tables[$name])) {
            return $this->tables[$name];
        }

        return null;
    }

    /**
     * @var null|TableInterface[] key of the array are unprefixed name of tables
     */
    protected $tables;

    /**
     * @return TableInterface[]
     */
    public function getTables()
    {
        // be sure list of table is updated
        $this->tables = $this->_getTables();

        return $this->tables;
    }

    /**
     * @param TableInterface|string $table the table object or the unprefixed table name
     */
    public function dropTable($table)
    {
        // be sure list of table is updated
        $this->tables = $this->_getTables();

        if (is_string($table)) {
            $tableName = $this->conn->createTableName($table);
        } else if ($table instanceof TableNameInterface) {
            $tableName = $table;
        } else if ($table instanceof TableInterface) {
            $tableName = $table->getTableName();
        } else {
            throw new \InvalidArgumentException();
        }
        $unprefixedName = $tableName->getFullName();

        if (isset($this->tables[$unprefixedName])) {
            $this->_dropTable($tableName);
            unset($this->tables[$unprefixedName]);
        }
    }

    /**
     * @param string $oldName Unprefixed name of the table to rename
     * @param string $newName The new unprefixed name of the table
     *
     * @return null|TableInterface
     */
    public function renameTable($oldName, $newName)
    {
        if ($this->tables === null) {
            $this->tables = $this->_getTables();
        }

        $oldTableName = $this->conn->createTableName($oldName);
        $oldName = $oldTableName->getFullName();
        $newTableName = $this->conn->createTableName($newName);
        $newName = $newTableName->getFullName();

        if (isset($this->tables[$newName])) {
            return $this->tables[$newName];
        }

        if (isset($this->tables[$oldName])) {
            $this->_renameTable(
                $oldTableName,
                $newTableName
            );
            unset($this->tables[$oldName]);
            $this->tables[$newName] = $this->_getTableInstance($newTableName);

            return $this->tables[$newName];
        }

        return null;
    }

    /**
     * create the given table into the database.
     *
     * @param TableNameInterface  $name       the table name
     * @param Column[]  $columns
     * @param array|string $primaryKey the name of the column which contains the primary key
     * @param array        $attributes
     *
     * @return TableInterface the object corresponding to the created table
     */
    abstract protected function _createTable(TableNameInterface $name, $columns, $primaryKey, $attributes = array());

    protected function _createTableQuery(TableNameInterface $name, $columns, $primaryKey, $attributes = array())
    {
        $cols = array();

        if (is_string($primaryKey)) {
            $primaryKey = array($primaryKey);
        }

        $autoIncrementUniqueKey = null;

        foreach ($columns as $col) {
            $isPk = (in_array($col->name, $primaryKey));
            $isSinglePk = $isPk && (count($primaryKey) == 1);
            $cols[] = $this->prepareSqlColumn($col, $isPk, $isSinglePk);
            if ($col->autoIncrement && !$isPk) {
                // we should declare it as unique key
                $autoIncrementUniqueKey = $col;
            }
        }

        if (isset($attributes['temporary']) && $attributes['temporary']) {
            $sql = 'CREATE TEMPORARY TABLE ';
        } else {
            $sql = 'CREATE TABLE ';
        }

        $sql .= $name->getEnclosedFullName().' ('.implode(', ', $cols);
        if (count($primaryKey) > 1) {
            $pkName = $this->conn->encloseName($name->getRealTableName().'_pkey');
            $pkEsc = array();
            foreach ($primaryKey as $k) {
                $pkEsc[] = $this->conn->encloseName($k);
            }
            $sql .= ', CONSTRAINT '.$pkName.' PRIMARY KEY ('.implode(',', $pkEsc).')';
        }

        if ($autoIncrementUniqueKey) {
            $ukName = $this->conn->encloseName($name->getRealTableName().'_'.$autoIncrementUniqueKey->name.'_ukey');
            $sql .= ', CONSTRAINT '.$ukName.' UNIQUE ('.$this->conn->encloseName($autoIncrementUniqueKey->name).')';
        }

        $sql .= ')';

        return $sql;
    }

    abstract protected function _getTables();

    protected function _dropTable(TableNameInterface $name)
    {
        $this->conn->exec('DROP TABLE '.$name->getEnclosedFullName());
    }

    protected function _renameTable(TableNameInterface $oldName, TableNameInterface $newName)
    {
        $this->conn->exec('ALTER TABLE '.$oldName->getEnclosedFullName().
        ' RENAME TO '.$this->conn->encloseName($newName->getRealTableName()));
    }

    abstract protected function _getTableInstance(TableNameInterface $name);


    /**
     * return the SQL string corresponding to the given column.
     *
     * @param Column $col                the column
     * @param mixed     $isPrimaryKey
     * @param mixed     $isSinglePrimaryKey
     *
     * @return string the sql string
     */
    public function prepareSqlColumn($col, $isPrimaryKey = false, $isSinglePrimaryKey = false)
    {
        $this->normalizeColumn($col);
        $colstr = $this->conn->encloseName($col->name).' '.$col->nativeType;
        $ti = $this->conn->tools()->getTypeInfo($col->type);
        if ($col->precision) {
            $colstr .= '('.$col->precision;
            if ($col->scale) {
                $colstr .= ','.$col->scale;
            }
            $colstr .= ')';
        } elseif ($col->length && $ti[1] != 'text' && $ti[1] != 'blob' && $ti[1] != 'json') {
            $colstr .= '('.$col->length.')';
        }

        $colstr .= $this->_getAutoIncrementKeyWord($col, $isPrimaryKey, $isSinglePrimaryKey);

        $colstr .= ($col->notNull ? ' NOT NULL' : '');

        if (!$col->autoIncrement && !$isPrimaryKey) {
            if ($col->hasDefault) {
                if ($col->default === null || strtoupper($col->default) == 'NULL') {
                    if (!$col->notNull) {
                        $colstr .= ' DEFAULT NULL';
                    }
                } else {
                    $colstr .= ' DEFAULT '.$this->conn->tools()->escapeValue($ti[1], $col->default, true);
                }
            }
        }
        if ($isSinglePrimaryKey) {
            $colstr .= ' PRIMARY KEY ';
        }

        return $colstr;
    }

    /**
     * @deprecated
     * @see prepareSqlColumn()
     */
    public function _prepareSqlColumn($col, $isPrimaryKey = false, $isSinglePrimaryKey = false)
    {
        return $this->prepareSqlColumn($col, $isPrimaryKey, $isSinglePrimaryKey);
    }

    /**
     * @param Column $col                the column
     */
    protected function _getAutoIncrementKeyWord($col, $isPrimaryKey, $isSinglePrimaryKey)
    {
        return '';
    }


    /**
     * fill correctly some properties of the column, depending of its type
     * and other properties.
     *
     * @param Column $col
     */
    public function normalizeColumn($col)
    {
        $type = $this->conn->tools()->getTypeInfo($col->type);

        $col->nativeType = $type[0];

        if ($type[6]) {
            $col->autoIncrement = true;
            $col->notNull = true;
        }
    }
}
