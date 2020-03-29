<?php
/**
 * @author     Laurent Jouanneau
 * @copyright  2010-2020 Laurent Jouanneau
 *
 * @see        https://jelix.org
 * @licence     http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */
namespace Jelix\Database\Schema\Postgresql;

use Jelix\Database\Schema\AbstractConstraint;
use Jelix\Database\Schema\AbstractTable;
use Jelix\Database\Schema\Column;
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
        $tools = $conn->tools();
        $version = $conn->getServerMajorVersion();
        // pg_get_expr on adbin, not compatible with pgsql < 9
        $adColName = ($version < 12 ? 'd.adsrc' : 'pg_get_expr(d.adbin,d.adrelid) AS adsrc');

        $sql = "SELECT a.attname, a.attnotnull, a.atthasdef, a.attlen, a.atttypmod,
                FORMAT_TYPE(a.atttypid, a.atttypmod) AS type,
                $adColName, co.contype AS primary, co.conname
            FROM pg_attribute AS a
            JOIN pg_class AS c ON a.attrelid = c.oid
            LEFT OUTER JOIN pg_constraint AS co
                ON (co.conrelid = c.oid AND a.attnum = ANY(co.conkey) AND co.contype = 'p')
            LEFT OUTER JOIN pg_attrdef AS d
                ON (d.adrelid = c.oid AND d.adnum = a.attnum)
            WHERE a.attnum > 0 AND c.relname = ".$conn->quote($this->name).
            ' ORDER BY a.attnum';
        $rs = $conn->query($sql);
        while ($line = $rs->fetch()) {
            $name = $line->attname;
            list($type, $length, $precision, $scale) = $tools->parseSQLType($line->type);
            $notNull = ($line->attnotnull == 't');
            $default = $line->adsrc;
            $hasDefault = ($line->atthasdef == 't');
            if ($type == 'boolean' && $hasDefault) {
                $default = (strtolower($default) === 'true');
            }

            $col = new Column($name, $type, $length, $hasDefault, $default, $notNull);

            $typeinfo = $tools->getTypeInfo($type);
            if (preg_match('/^nextval\(([^\)]*)\)$/', $default, $m)) {
                $col->autoIncrement = true;
                $col->default = '';
                if ($m[1]) {
                    $pos = strpos($m[1], '::');
                    if ($pos !== false) {
                        $col->sequence = trim(substr($m[1], 0, $pos), "'");
                    } else {
                        $col->sequence = $m[1];
                    }
                }
            } elseif ($typeinfo[6]) {
                $col->autoIncrement = true;
                $col->default = '';
            } elseif (preg_match('/^NULL::/', $default) && $hasDefault) {
                $col->default = null;
            }

            $col->nativeType = $typeinfo[0];
            $col->maxValue = $typeinfo[3];
            $col->minValue = $typeinfo[2];
            $col->maxLength = $typeinfo[5];
            $col->minLength = $typeinfo[4];
            $col->precision = $precision;
            $col->scale = $scale;
            if ($line->attlen == -1 && $line->atttypmod != -1) {
                $col->length = $line->atttypmod - 4;
            }
            if ($col->length != 0) {
                $col->maxLength = $col->length;
            }

            if ($line->primary) {
                if (!$this->primaryKey) {
                    $this->primaryKey = new PrimaryKey($line->conname, $name);
                } else {
                    $this->primaryKey->columns[] = $name;
                }
            }

            $this->columns[$name] = $col;
        }
        if ($this->primaryKey === null) {
            $this->primaryKey = false;
        }
    }

    protected function _alterColumn(Column $old, Column $new)
    {
        $conn = $this->schema->getConn();
        $tools = $conn->tools();
        if ($new->name != $old->name) {
            $conn->exec('ALTER TABLE '.$conn->encloseName($this->name).
                ' RENAME COLUMN '.$conn->encloseName($old->name).
                ' TO '.$conn->encloseName($new->name));
        }

        if ($new->type != $old->type ||
            $new->precision != $old->precision ||
            $new->scale != $old->scale ||
            $new->length != $old->length
        ) {
            $typeInfo = $tools->getTypeInfo($new->type);

            $sql = 'ALTER TABLE '.$conn->encloseName($this->name).
                ' ALTER COLUMN '.$conn->encloseName($new->name).
                ' TYPE '.$typeInfo[0];
            if ($new->precision) {
                $sql .= '('.$new->precision;
                if ($new->scale) {
                    $sql .= ','.$new->scale;
                }
                $sql .= ')';
            } elseif ($new->length && $typeInfo[0] != 'text') {
                $sql .= '('.$new->length.')';
            }
            $conn->exec($sql);
        }

        if ($new->hasDefault !== $old->hasDefault) {
            if ($new->hasDefault && $new->default !== null) {
                $sql = 'ALTER TABLE '.$conn->encloseName($this->name).
                    ' ALTER COLUMN '.$conn->encloseName($new->name).
                    ' SET DEFAULT '.$new->default;
                $conn->exec($sql);
            } else {
                $sql = 'ALTER TABLE '.$conn->encloseName($this->name).
                    ' ALTER COLUMN '.$conn->encloseName($new->name).
                    ' DROP DEFAULT';
                $conn->exec($sql);
            }
        } elseif ($new->hasDefault && $new->default !== null && $new->default != $old->default) {
            $sql = 'ALTER TABLE '.$conn->encloseName($this->name).
                ' ALTER COLUMN '.$conn->encloseName($new->name).
                ' SET DEFAULT '.$new->default;
            $conn->exec($sql);
        }

        if ($new->notNull != $old->notNull) {
            if ($new->notNull) {
                $sql = 'ALTER TABLE '.$conn->encloseName($this->name).
                    ' ALTER COLUMN '.$conn->encloseName($new->name).
                    ' SET NOT NULL ';
                $conn->exec($sql);
            } else {
                $sql = 'ALTER TABLE '.$conn->encloseName($this->name).
                    ' ALTER COLUMN '.$conn->encloseName($new->name).
                    ' DROP NOT NULL ';
                $conn->exec($sql);
            }
        }
    }

    protected function _addColumn(Column $new)
    {
        $conn = $this->schema->getConn();
        $sql = 'ALTER TABLE '.$conn->encloseName($this->name).
            ' ADD COLUMN '.$this->schema->prepareSqlColumn($new);
        $conn->exec($sql);
    }

    protected function _loadIndexesAndKeys()
    {
        $this->indexes = array();
        $conn = $this->schema->getConn();
        $sql = "SELECT n.nspname  as schemaname,  t.relname  as tablename,
                c.relname  as indexname, a.attname, i.indisunique, a.attnum
        FROM pg_class c
        JOIN pg_index i     on i.indexrelid = c.oid
        JOIN pg_namespace n  on n.oid        = c.relnamespace
        JOIN pg_class t      on i.indrelid   = t.oid
        JOIN pg_attribute a ON (a.attrelid = t.oid and a.attnum = ANY(i.indkey))
        LEFT JOIN pg_constraint co on (t.oid = co.conrelid and co.conindid = c.oid)
        WHERE c.relkind = 'i'
          and n.nspname not in ('pg_catalog', 'pg_toast')
          and pg_catalog.pg_table_is_visible(c.oid)
          and co.conindid is null
          AND t.relname = ".$conn->quote($this->getName());
        $rs = $conn->query($sql);
        while ($indexRec = $rs->fetch()) {
            if (isset($this->indexes[$indexRec->indexname])) {
                $index = $this->indexes[$indexRec->indexname];
            } else {
                $index = new Index($indexRec->indexname);
                $this->indexes[$indexRec->indexname] = $index;
                $index->isUnique = ($indexRec->indisunique == 't');
            }
            $index->columns[] = $indexRec->attname;
        }
    }

    protected function _createIndex(Index $index)
    {
        $conn = $this->schema->getConn();
        $sql = 'CREATE ';
        if ($index->isUnique) {
            $sql .= 'UNIQUE ';
        }
        $sql .= 'INDEX '.$conn->encloseName($index->name).' ON '.$conn->encloseName($this->getName());
        $sql .= ' ('.$conn->tools()->getSQLColumnsList($index->columns).')';
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
        $this->primaryKey = false;
        $this->uniqueKeys = array();
        $this->references = array();

        $conn = $this->schema->getConn();
        $sql = "SELECT
          tc.constraint_name,
          tc.constraint_type,
          tc.table_name,
          kcu.column_name,
          rc.update_rule AS on_update,
          rc.delete_rule AS on_delete,
          ccu.table_name AS references_table,
          ccu.column_name AS references_field
        
        FROM information_schema.table_constraints tc
        
        LEFT JOIN information_schema.key_column_usage kcu
          ON tc.constraint_catalog = kcu.constraint_catalog
          AND tc.constraint_schema = kcu.constraint_schema
          AND tc.constraint_name = kcu.constraint_name
        
        LEFT JOIN information_schema.referential_constraints rc
          ON tc.constraint_catalog = rc.constraint_catalog
          AND tc.constraint_schema = rc.constraint_schema
          AND tc.constraint_name = rc.constraint_name
        
        LEFT JOIN information_schema.constraint_column_usage ccu
          ON rc.unique_constraint_catalog = ccu.constraint_catalog
          AND rc.unique_constraint_schema = ccu.constraint_schema
          AND rc.unique_constraint_name = ccu.constraint_name
        
        WHERE tc.table_schema NOT IN ('pg_catalog', 'information_schema')
          AND constraint_type IN ('PRIMARY KEY', 'UNIQUE', 'FOREIGN KEY')
          AND tc.table_name = ".$conn->quote($this->getName());

        $rs = $conn->query($sql);
        while ($constraint = $rs->fetch()) {
            switch ($constraint->constraint_type) {
                case 'PRIMARY KEY':
                    if (!$this->primaryKey) {
                        $this->primaryKey = new PrimaryKey(
                            $constraint->constraint_name,
                            $constraint->column_name
                        );
                    } else {
                        $this->primaryKey->columns[] = $constraint->column_name;
                    }

                    break;
                case 'UNIQUE':
                    if (!isset($this->uniqueKeys[$constraint->constraint_name])) {
                        $unique = new UniqueKey(
                            $constraint->constraint_name,
                            $constraint->column_name
                        );
                        $this->uniqueKeys[$constraint->constraint_name] = $unique;
                    } else {
                        $this->uniqueKeys[$constraint->constraint_name]->columns[] = $constraint->column_name;
                    }

                    break;
                case 'FOREIGN KEY':
                    if (!isset($this->references[$constraint->constraint_name])) {
                        $fk = new Reference(
                            $constraint->constraint_name,
                            $constraint->column_name,
                            $constraint->references_table,
                            array($constraint->references_field)
                        );
                        $this->references[$constraint->constraint_name] = $fk;
                    } else {
                        $fk = $this->references[$constraint->constraint_name];
                        $fk->columns[] = $constraint->column_name;
                        $fk->fColumns[] = $constraint->references_field;
                    }

                    break;
            }
        }
    }

    protected function _createConstraint(AbstractConstraint $constraint)
    {
        $conn = $this->schema->getConn();
        $tools = $conn->tools();
        $sql = 'ALTER TABLE '.$conn->encloseName($this->name).
            ' ADD CONSTRAINT '.$conn->encloseName($constraint->name);
        if ($constraint instanceof PrimaryKey) {
            $sql .= ' PRIMARY KEY ('.$tools->getSQLColumnsList($constraint->columns).')';
        } elseif ($constraint instanceof UniqueKey) {
            $sql .= ' UNIQUE ('.$tools->getSQLColumnsList($constraint->columns).')';
        } elseif ($constraint instanceof Reference) {
            $sql .= ' FOREIGN KEY ('.$tools->getSQLColumnsList($constraint->columns).')';
            $sql .= ' REFERENCES '.$conn->encloseName($constraint->fTable).
                '  ('.$tools->getSQLColumnsList($constraint->fColumns).')';
        }
        $conn->exec($sql);
    }

    protected function _dropConstraint(AbstractConstraint $constraint)
    {
        $conn = $this->schema->getConn();
        $sql = 'ALTER TABLE '.$conn->encloseName($this->name).
            ' DROP CONSTRAINT IF EXISTS '.$conn->encloseName($constraint->name);
        $conn->exec($sql);
    }
}
