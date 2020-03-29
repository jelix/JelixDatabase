<?php
/**
 * @author     Laurent Jouanneau
 * @contributor     Loic Mathaud
 *
 * @copyright  2006 Loic Mathaud, 2007-2020 Laurent Jouanneau
 *
 * @see        https://jelix.org
 * @licence     http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */
namespace Jelix\Database\Schema\Sqlite;

use Jelix\Database\ConnectionInterface;
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
        $this->columns = array();
        $this->primaryKey = null;
        $sql = 'PRAGMA table_info('.$conn->quote($this->name).')';
        $rs = $conn->query($sql);
        $tools = $conn->tools();
        $this->primaryKey = false;
        while ($c = $rs->fetch()) {
            $hasDefault = false;
            $default = null;
            $isPrimary = ($c->pk == 1);
            $notNull = ($c->notnull != 0 || $c->pk == 1);

            list($type, $length, $precision, $scale, $tail) = $tools->parseSQLType($c->type);
            $autoIncrement = false;
            if (strtolower($tail) == 'autoincrement'
                || strtolower($type) == 'rowid'
                || (strtolower($type) == 'integer' && $isPrimary)) {
                // in sqlite, rowid or integer primary key is always an auto_increment field
                // AUTOINCREMENT keyword just change the incremental algorithm
                // the purpose of AUTOINCREMENT is to prevent the reuse of ROWIDs from previously deleted rows.
                // see http://sqlite.org/autoinc.html
                $autoIncrement = true;
                $hasDefault = true;
                $default = '';
            }

            if (!$isPrimary && $c->dflt_value !== null) {
                $hasDefault = true;
                $default = ($c->dflt_value === 'NULL' ? null : $c->dflt_value);
            }
            $typeinfo = $tools->getTypeInfo($type);
            if ($typeinfo[6]) {
                $autoIncrement = true;
                $hasDefault = true;
                $default = '';
            }

            if ($typeinfo[1] == 'boolean' && $hasDefault) {
                $default = ($default == '1' || $default === true || strtolower($default) == 'true');
            }

            $col = new Column($c->name, $type, $length, $hasDefault, $default, $notNull);
            $col->nativeType = $typeinfo[0];
            $col->maxValue = $typeinfo[3];
            $col->minValue = $typeinfo[2];
            $col->maxLength = $typeinfo[5];
            $col->minLength = $typeinfo[4];
            $col->precision = $precision;
            $col->scale = $scale;
            if ($col->length != 0) {
                $col->maxLength = $col->length;
            }
            $col->autoIncrement = $autoIncrement;

            $this->columns[$col->name] = $col;

            if ($isPrimary) {
                if (!$this->primaryKey) {
                    $this->primaryKey = new PrimaryKey('', $c->name);
                } else {
                    $this->primaryKey->columns[] = $c->name;
                }
            }
        }
    }

    protected function _alterColumn(Column $oldCol, Column $newCol)
    {

        // new list of columns with the modified column
        $newColumns = array();
        $newIndexes = null;
        $newReferences = null;
        $newPrimaryKey = null;
        $newUniqueKeys = null;

        foreach ($this->columns as $colName => $col) {
            if ($colName == $oldCol->name) {
                if ($oldCol->name !== $newCol->name) {
                    $colName = $newCol->name;
                    $this->_updateColumnInConstraintsAndIndexes(
                        $oldCol->name,
                        $newIndexes,
                        $newReferences,
                        $newPrimaryKey,
                        $newUniqueKeys,
                        1,
                        $newCol->name
                    );
                }
                $col = $newCol;
            }
            $newColumns[$colName] = $col;
        }

        // recreate the table with the new column
        $conn = $this->schema->getConn();
        $this->schema->recreateTable(
            $this,
            $newColumns,
            $this->_getSqlColumnsList($conn, $this->columns),
            $this->_getSqlColumnsList($conn, $newColumns),
            $newPrimaryKey,
            $newIndexes,
            $newReferences,
            $newUniqueKeys
        );
        $this->columns = $newColumns;
    }

    /**
     * In order to apply some changes, we need to update constraints and
     * indexes in order to create a new table with no wrong columns.
     *
     * @param $colName
     * @param $newIndexes
     * @param $newReferences
     * @param $newPrimaryKey
     * @param $newUniqueKeys
     * @param int        $mode       1:rename, 2:drop
     * @param null|mixed $newColName
     */
    protected function _updateColumnInConstraintsAndIndexes(
        $colName,
        &$newIndexes,
        &$newReferences,
        &$newPrimaryKey,
        &$newUniqueKeys,
        $mode,
        $newColName = null
    ) {
        $indexes = ($newIndexes ? $newIndexes : $this->indexes);
        $changedIndexes = $indexes;
        $indexesChanged = false;
        foreach ($indexes as $name => $index) {
            $pos = array_search($colName, $index->columns);
            if ($pos !== false) {
                $index = clone $index;
                if ($mode == 1) {
                    $index->columns[$pos] = $newColName;
                } else {
                    $index->columns = array_diff($index->columns, array($colName));
                }

                if (count($index->columns) == 0) {
                    unset($changedIndexes[$name]);
                } else {
                    $changedIndexes[$name] = $index;
                }
                $indexesChanged = true;
            }
        }
        if ($indexesChanged) {
            $newIndexes = $changedIndexes;
        }

        $constraints = ($newReferences ? $newReferences : $this->references);
        $changedConstraints = $constraints;
        $constraintsChanged = false;
        foreach ($constraints as $name => $constraint) {
            $pos = array_search($colName, $constraint->columns);
            if ($pos !== false) {
                $constraint = clone $constraint;
                if ($mode == 1) {
                    $constraint->columns[$pos] = $newColName;
                } else {
                    $constraint->columns = array_diff($constraint->columns, array($colName));
                }
                if (count($constraint->columns) == 0) {
                    unset($changedConstraints[$name]);
                } else {
                    $changedConstraints[$name] = $constraint;
                }
                $constraintsChanged = true;
            }
        }
        if ($constraintsChanged) {
            $newReferences = $changedConstraints;
        }

        $constraints = ($newUniqueKeys ? $newUniqueKeys : $this->uniqueKeys);
        $changedConstraints = $constraints;
        $constraintsChanged = false;
        foreach ($constraints as $name => $constraint) {
            $pos = array_search($colName, $constraint->columns);
            if ($pos !== false) {
                $constraint = clone $constraint;
                if ($mode == 1) {
                    $constraint->columns[$pos] = $newColName;
                } else {
                    $constraint->columns = array_diff($constraint->columns, array($colName));
                }
                if (count($constraint->columns) == 0) {
                    unset($changedConstraints[$name]);
                } else {
                    $changedConstraints[$name] = $constraint;
                }
                $constraintsChanged = true;
            }
        }
        if ($constraintsChanged) {
            $newUniqueKeys = $changedConstraints;
        }

        $primaryKey = ($newPrimaryKey !== null ? $newPrimaryKey : $this->primaryKey);
        if ($primaryKey) {
            $pos = array_search($colName, $primaryKey->columns);
            if ($pos !== false) {
                $primaryKey = clone $primaryKey;
                if ($mode == 1) {
                    $primaryKey->columns[$pos] = $newColName;
                } else {
                    $primaryKey->columns = array_diff($primaryKey->columns, array($colName));
                }
                if (count($primaryKey->columns) == 0) {
                    $newPrimaryKey = false;
                } else {
                    $newPrimaryKey = $primaryKey;
                }
            }
        }
    }

    protected function _addColumn(Column $new)
    {
        $conn = $this->schema->getConn();
        $pk = $this->getPrimaryKey();
        $isPk = ($pk && in_array($new->name, $pk->columns));
        $isSinglePk = $isPk && count($pk->columns) == 1;
        $sql = 'ALTER TABLE '.$conn->encloseName($this->name)
            .' ADD COLUMN '.$this->schema->_prepareSqlColumn($new, $isPk, $isSinglePk);
        $conn->exec($sql);
    }

    protected function _dropColumn(Column $oldCol)
    {
        $newColumns = array();
        $newIndexes = null;
        $newReferences = null;
        $newPrimaryKey = null;
        $newUniqueKeys = null;

        foreach ($this->columns as $colName => $col) {
            if ($colName == $oldCol->name) {
                $this->_updateColumnInConstraintsAndIndexes(
                    $oldCol->name,
                    $newIndexes,
                    $newReferences,
                    $newPrimaryKey,
                    $newUniqueKeys,
                    2
                );

                continue;
            }
            $newColumns[$colName] = $col;
        }

        $conn = $this->schema->getConn();
        $colList = $this->_getSqlColumnsList($conn, $newColumns);
        $this->schema->recreateTable(
            $this,
            $newColumns,
            $colList,
            $colList,
            $newPrimaryKey,
            $newIndexes,
            $newReferences,
            $newUniqueKeys
        );
    }

    protected function _splitColumnsName($sqlList)
    {
        $columns = array();
        $list = preg_split('/\\s*,\\s*/', $sqlList);
        foreach ($list as $col) {
            $columns[] = trim($col, '"\'');
        }

        return $columns;
    }

    protected function _loadIndexesAndKeys()
    {
        $this->indexes = array();
        $this->uniqueKeys = array();
        $this->references = array();

        $conn = $this->schema->getConn();
        $tools = $conn->tools();

        // indexes created with CREATE INDEX are in sqlite_master
        // we parse also constraints in CREATE TABLE as PRAGMA lists doesn't
        // provide enough informations about name (and as SQLITE ignore
        // constraint name in CREATE TABLE)

        $sql = 'SELECT name, sql FROM sqlite_master 
                WHERE tbl_name = '.$conn->quote($this->name).' AND sql IS NOT NULL';
        $rs = $conn->query($sql);
        while ($rec = $rs->fetch()) {
            if (isset($rec->type)) {
                $type = $rec->type;
            } else {
                // old sqlite3 version
                if (preg_match('/^\\s*CREATE\\s+(TEMPORARY|TEMP|UNIQUE\\s+)?(INDEX|TABLE)/msi', $rec->sql, $m)) {
                    $type = (strtolower($m[2]) == 'table' ? 'table' : 'index');
                } else {
                    continue;
                }
            }

            if ($type == 'index') {
                $index = new Index($rec->name);
                $this->indexes[$rec->name] = $index;
            } elseif ($type == 'table') {
                $definition = $tools->parseCREATETABLE($rec->sql);
                if ($definition === false) {
                    continue;
                }

                foreach ($definition['columns'] as $k => $colDef) {
                    if (preg_match('/ (?:CONSTRAINT "?(\\w+)"? )?UNIQUE/i', $colDef, $m)) {
                        if (!preg_match('/^"?(\w+)', $colDef, $n)) {
                            continue;
                        }
                        $column = $n[1];
                        if ($m[1]) {
                            $name = $m[1];
                        } else {
                            $name = $this->name.'_'.$column.'_unique';
                        }
                        $this->uniqueKeys[$name] = new UniqueKey($name, $column);
                    }
                }
                foreach ($definition['constraints'] as $k => $constDef) {
                    if (preg_match('/^(?:CONSTRAINT "?(\\w+)"? )?UNIQUE ?\\(([^)]+)\\)/i', $constDef, $m)) {
                        $columns = $this->_splitColumnsName($m[2]);
                        if ($m[1]) {
                            $name = $m[1];
                        } else {
                            $name = $this->name.'_'.implode('_', $columns).'_unique';
                        }

                        $this->uniqueKeys[$name] = new UniqueKey($name, $columns);
                    } elseif (preg_match('/^(?:CONSTRAINT "?(\\w+)"? )?FOREIGN KEY ?\\(([^)]+)\\) ?REFERENCES "?(\\w+)"? ?\\(([^)]+)\\)(.*)$/i', $constDef, $m)) {
                        $ref = new Reference();
                        $ref->columns = $this->_splitColumnsName($m[2]);
                        $ref->fTable = $m[3];
                        $ref->fColumns = $this->_splitColumnsName($m[4]);
                        $ref->name = ($m[1] != '' ? $m[1] : $this->name.'_'.implode('_', $ref->columns).'_fkey');
                        $this->references[$ref->name] = $ref;
                        if (preg_match('/ON\s+DELETE\s+([^,)])/msi', $m[5], $m2)) {
                            $ref->onDelete = trim($m2[1]);
                        }
                        if (preg_match('/ON\s+UPDATE\s+([^,)])/msi', $m[5], $m2)) {
                            $ref->onUpdate = trim($m2[1]);
                        }
                    }
                }
            }
        }

        // retrieve unicity of indexes
        $sql = 'PRAGMA index_list('.$conn->quote($this->name).')';
        $rs = $conn->query($sql);
        while ($indexRec = $rs->fetch()) {
            if (isset($this->indexes[$indexRec->name])) {
                if ($indexRec->unique == '1') {
                    $this->indexes[$indexRec->name]->isUnique = true;
                }
            }
        }

        // retrieve columns of indexes
        foreach ($this->indexes as $index) {
            $rs = $conn->query('PRAGMA index_info('.$index->name.')');
            $cols = array();
            while ($idxinfo = $rs->fetch()) {
                $cols[$idxinfo->seqno] = $idxinfo->name;
            }
            ksort($cols, SORT_NUMERIC);
            $index->columns = array_values($cols);
        }
    }

    protected function _createIndex(Index $index)
    {
        $conn = $this->schema->getConn();
        $sql = 'CREATE ';
        if ($index->isUnique) {
            $sql .= 'UNIQUE ';
        }
        $sql .= 'INDEX '.$conn->encloseName($index->name).
            ' ON '.$conn->encloseName($this->name).
            ' ('.$conn->tools()->getSQLColumnsList($index->columns).')';
        $conn->exec($sql);
    }

    protected function _dropIndex(Index $index)
    {
        $conn = $this->schema->getConn();
        $sql = 'DROP INDEX IF EXISTS '.$conn->encloseName($index->name);
        $conn->exec($sql);
    }

    protected function _loadReferences()
    {
        // already loaded by _loadIndexesAndKeys
    }

    protected function _createConstraint(AbstractConstraint $constraint)
    {
        $conn = $this->schema->getConn();
        $colList = $this->_getSqlColumnsList($conn, $this->columns);
        if ($constraint instanceof PrimaryKey) {
            $this->schema->recreateTable(
                $this,
                $this->columns,
                $colList,
                $colList,
                $constraint
            );
        } elseif ($constraint instanceof UniqueKey) {
            $uniqueKeys = $this->uniqueKeys;
            $uniqueKeys[$constraint->name] = $constraint;
            $this->schema->recreateTable(
                $this,
                $this->columns,
                $colList,
                $colList,
                null,
                null,
                null,
                $uniqueKeys
            );
        } elseif ($constraint instanceof Reference) {
            $references = $this->references;
            $references[$constraint->name] = $constraint;
            $this->schema->recreateTable(
                $this,
                $this->columns,
                $colList,
                $colList,
                null,
                null,
                $references
            );
        }
    }

    protected function _dropConstraint(AbstractConstraint $constraint)
    {
        $conn = $this->schema->getConn();
        $colList = $this->_getSqlColumnsList($conn, $this->columns);
        if ($constraint instanceof PrimaryKey) {
            $this->schema->recreateTable(
                $this,
                $this->columns,
                $colList,
                $colList,
                false
            );
        } elseif ($constraint instanceof UniqueKey) {
            $uniqueKeys = $this->uniqueKeys;
            unset($uniqueKeys[$constraint->name]);
            $this->schema->recreateTable(
                $this,
                $this->columns,
                $colList,
                $colList,
                null,
                null,
                null,
                $uniqueKeys
            );
        } elseif ($constraint instanceof Reference) {
            $references = $this->references;
            unset($references[$constraint->name]);
            $this->schema->recreateTable(
                $this,
                $this->columns,
                $colList,
                $colList,
                null,
                null,
                $references
            );
        }
    }

    protected function _replaceConstraint(AbstractConstraint $oldConstraint, AbstractConstraint $newConstraint)
    {
        $conn = $this->schema->getConn();
        $colList = $this->_getSqlColumnsList($conn, $this->columns);
        if ($oldConstraint instanceof PrimaryKey) {
            $this->schema->recreateTable(
                $this,
                $this->columns,
                $colList,
                $colList,
                $newConstraint
            );
        } elseif ($oldConstraint instanceof UniqueKey) {
            $uniqueKeys = $this->uniqueKeys;
            unset($uniqueKeys[$oldConstraint->name]);
            $uniqueKeys[$newConstraint->name] = $newConstraint;
            $this->schema->recreateTable(
                $this,
                $this->columns,
                $colList,
                $colList,
                null,
                null,
                null,
                $uniqueKeys
            );
        } elseif ($oldConstraint instanceof Reference) {
            $references = $this->references;
            unset($references[$oldConstraint->name]);
            $references[$newConstraint->name] = $newConstraint;
            $this->schema->recreateTable(
                $this,
                $this->columns,
                $colList,
                $colList,
                null,
                null,
                $references
            );
        }
    }

    /**
     * @param ConnectionInterface $conn
     * @param array         $columns
     */
    protected function _getSqlColumnsList($conn, &$columns)
    {
        $columnNames = array();
        foreach ($columns as $name => $col) {
            $columnNames[] = $conn->encloseName($name);
        }

        return implode(',', $columnNames);
    }
}
