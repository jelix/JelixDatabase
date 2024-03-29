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
     * @param mixed $primaryKey
     * @param mixed $attributes
     */
    public function _createTable($name, $columns, $primaryKey, $attributes = array())
    {
        $sql = $this->_createTableQuery($name, $columns, $primaryKey, $attributes);

        $this->conn->exec($sql);

        $table = new Table($name, $this);
        $table->attributes = $attributes;

        return $table;
    }

    public function prepareSqlColumn($col, $isPrimaryKey = false, $isSinglePrimaryKey = false)
    {
        if ($isSinglePrimaryKey && $col->autoIncrement) {
            $col->type = 'serial';
        }

        return parent::prepareSqlColumn($col, $isPrimaryKey, $isSinglePrimaryKey);
    }

    protected function _getTables()
    {
        $searchPath = $this->getConn()->getSearchPath();
        $c = $this->getConn();
        $schemas = implode(',', array_map(function($schema) use ($c) {
            return $c->quote($schema);
        }, $searchPath));

        $results = array();
        $sql = "SELECT tablename, schemaname FROM pg_tables
                  WHERE schemaname ILIKE ANY (array[".$schemas."])
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
