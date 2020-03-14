<?php
/**
 * @author     Laurent Jouanneau
 * @copyright  2010-2020 Laurent Jouanneau
 *
 * @see        https://jelix.org
 * @licence     http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */

namespace Jelix\Database\Schema\Postgresql;

use Jelix\Database\Schema\AbstractSchema;

/**
 */
class Schema extends AbstractSchema
{
    /**
     * @param mixed $name
     * @param mixed $columns
     * @param mixed $primaryKeys
     * @param mixed $attributes
     */
    public function _createTable($name, $columns, $primaryKeys, $attributes = array())
    {
        $sql = $this->_createTableQuery($name, $columns, $primaryKeys, $attributes);

        $this->conn->exec($sql);

        $table = new Table($name, $this);
        $table->attributes = $attributes;

        return $table;
    }

    public function _prepareSqlColumn($col, $isPrimaryKey = false, $isSinglePrimaryKey = false)
    {
        if ($isSinglePrimaryKey && $col->autoIncrement) {
            $col->type = 'serial';
        }

        return parent::_prepareSqlColumn($col, $isPrimaryKey, $isSinglePrimaryKey);
    }

    protected function _getTables()
    {
        $results = array();
        $sql = "SELECT tablename FROM pg_tables
                  WHERE schemaname NOT IN ('pg_catalog', 'information_schema')
                  ORDER BY tablename";
        $rs = $this->getConn()->query($sql);
        while ($line = $rs->fetch()) {
            $unpName = $this->conn->unprefixTable($line->tablename);
            $results[$unpName] = new Table($line->tablename, $this);
        }

        return $results;
    }

    protected function _getTableInstance($name)
    {
        return new Table($name, $this);
    }
}
