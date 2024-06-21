<?php
/**
 * @author     Laurent Jouanneau
 * @contributor     Loic Mathaud
 *
 * @copyright  2006 Loic Mathaud, 2007-2024 Laurent Jouanneau
 *
 * @see        https://jelix.org
 * @licence     http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */
namespace Jelix\Database\Schema\Sqlite;

use Jelix\Database\Schema\AbstractSchema;
use Jelix\Database\Schema\Column;
use Jelix\Database\Schema\Exception;
use Jelix\Database\Schema\TableNameInterface;

/**
 */
class Schema extends AbstractSchema
{
    protected function _createTable(TableNameInterface $name, $columns, $primaryKey, $attributes = array())
    {
        $sql = $this->_createTableQuery($name, $columns, $primaryKey, $attributes);
        $this->conn->exec($sql);

        return new Table($name, $this);
    }

    protected function _getTables()
    {
        $results = array();

        $rs = $this->conn->query('SELECT name FROM sqlite_master WHERE type="table"');
        $prefix = $this->conn->getTablePrefix();

        while ($line = $rs->fetch()) {
            $unpName = $this->conn->unprefixTable($line->name);
            $results[$unpName] = new Table(new TableName($unpName, '', $prefix), $this);
        }

        return $results;
    }

    protected function _getTableInstance(TableNameInterface $name)
    {
        return new Table($name, $this);
    }

    /**
     * Modify a table by recreating it and by migrating data.
     *
     * This is the only way to modify a table with SQLite.
     * It creates a new table with a temporary name, and new columns.
     * Then it executes a INSERT INTO newtable (...) SELECT ... FROM oldtable
     * Then it drops the old table and rename the new table with the old name.
     *
     * @param Table $table
     * @param Column[]    $newColumns
     * @param string         $sqlOldTableColumns list of columns for the SELECT
     * @param string         $sqlNewTableColumns list of columns for the INSERT
     * @param null|mixed     $newPrimaryKey
     * @param null|mixed     $newIndexes
     * @param null|mixed     $newReferences
     * @param null|mixed     $newUniqueKeys
     *
     * @internal internal method, only called by Table
     */
    public function recreateTable(
        $table,
        $newColumns,
        $sqlOldTableColumns,
        $sqlNewTableColumns,
        $newPrimaryKey = null,
        $newIndexes = null,
        $newReferences = null,
        $newUniqueKeys = null
    ) {
        $conn = $this->getConn();

        $tmpName = $table->getTableName()->getTableName().'_tmp';
        $count = 0;
        while ($this->getTable($tmpName.$count) !== null) {
            ++$count;
        }
        $tmpName .= $count;
        $tmpTableName = $conn->createTableName($tmpName);

        $conn->beginTransaction();

        try {
            $sql = $this->_createTableFromObject(
                $table,
                $tmpName,
                $newColumns,
                $newPrimaryKey,
                $newReferences,
                $newUniqueKeys
            );
            $conn->exec($sql);

            $sql = 'INSERT INTO '.$conn->encloseName($tmpName).'('.
                $sqlNewTableColumns.') SELECT '.$sqlOldTableColumns.
                ' FROM '.$table->getTableName()->getEnclosedFullName();
            $conn->exec($sql);

            $this->_dropTable($table->getTableName());
            $this->_renameTable($tmpTableName, $table->getTableName());
            $conn->commit();

            if ($newIndexes !== null) {
                $indexes = $newIndexes;
            } else {
                $indexes = $table->getIndexes();
            }
            foreach ($indexes as $index) {
                $sql = 'CREATE ';
                if ($index->isUnique) {
                    $sql .= 'UNIQUE ';
                }
                $sql .= 'INDEX '.$conn->encloseName($index->name).
                    ' ON '.$table->getTableName()->getEnclosedFullName().
                    ' ('.$conn->tools()->getSQLColumnsList($index->columns).')';
                $conn->exec($sql);
            }
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    protected function _createTableFromObject(
        Table $table,
        $tmpName,
        $newColumns,
        $newPrimaryKey,
        $newReferences,
        $newUniqueKeys
    ) {
        $cols = array();
        if ($newPrimaryKey !== null) {
            $primaryKey = $newPrimaryKey;
        } else {
            $primaryKey = $table->getPrimaryKey();
        }

        if ($primaryKey) {
            $primaryKeys = $primaryKey->columns;
        } else {
            $primaryKeys = array();
        }

        foreach ($newColumns as $col) {
            $isPk = in_array($col->name, $primaryKeys);
            $isSinglePk = $isPk && count($primaryKeys) == 1;
            $cols[] = $this->prepareSqlColumn($col, $isPk, $isSinglePk);
        }

        $sql = 'CREATE TABLE '.$this->conn->encloseName($tmpName);
        $sql .= ' ('.implode(', ', $cols);
        if (count($primaryKeys) > 1) {
            $pkName = $this->conn->encloseName($primaryKey->name);
            $pkEsc = $this->conn->tools()->getSQLColumnsList($primaryKeys);
            $sql .= ', CONSTRAINT '.$pkName.' PRIMARY KEY ('.$pkEsc.')';
        }
        if ($newUniqueKeys !== null) {
            $uniqueKeys = $newUniqueKeys;
        } else {
            $uniqueKeys = $table->getUniqueKeys();
        }
        foreach ($uniqueKeys as $uniqueKey) {
            $sql .= ', CONSTRAINT '.$this->conn->encloseName($uniqueKey->name).
                ' UNIQUE ('.$this->conn->tools()->getSQLColumnsList($uniqueKey->columns).')';
        }
        if ($newReferences !== null) {
            $references = $newReferences;
        } else {
            $references = $table->getReferences();
        }
        foreach ($references as $ref) {
            $sql .= ', CONSTRAINT '.$this->conn->encloseName($ref->name).
                ' FOREIGN KEY ('.$this->conn->tools()->getSQLColumnsList($ref->columns).')'.
                ' REFERENCES '.$this->conn->encloseName($ref->fTable).
                ' ('.$this->conn->tools()->getSQLColumnsList($ref->fColumns).')';
        }

        $sql .= ')';

        return $sql;
    }

    /**
     * return the SQL string corresponding to the given column.
     * private method, should be used only by a jDbTable object.
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
        } elseif ($col->length && $ti[1] != 'text' && $ti[1] != 'blob') {
            $colstr .= '('.$col->length.')';
        }

        $colstr .= ($col->notNull && !$col->autoIncrement ? ' NOT NULL' : '');

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

        $colstr .= $this->_getAutoIncrementKeyWord($col, $isPrimaryKey, $isSinglePrimaryKey);

        return $colstr;
    }

    /**
     * @param Column $col the column
     */
    protected function _getAutoIncrementKeyWord($col, $isPrimaryKey, $isSinglePrimaryKey)
    {
        if ($col->autoIncrement && $col->nativeType == 'integer') {
            if ($isPrimaryKey && $isSinglePrimaryKey) {
                return ' AUTOINCREMENT';
            }
            $col->autoIncrement = false;
            // we don't set the AUTOINCREMENT keyword, because it is not needed
            // in sqlite, as it is only allowed with INTEGER PRIMARY KEY, and
            // as INTEGER PRIMARY KEY is automatically set as auto incremented.
            // if we set AUTOINCREMENT, it also prevents to remove the "PRIMARY KEY"
            // constraint
        }
        return '';
    }
}
