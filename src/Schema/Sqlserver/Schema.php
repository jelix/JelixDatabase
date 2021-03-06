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

/**
 */
class Schema extends AbstractSchema
{
    protected function _createTable($name, $columns, $primaryKeys, $attributes = array())
    {
        $sql = $this->_createTableQuery($name, $columns, $primaryKeys, $attributes);

        $this->conn->exec($sql);

        $table = new Table($name, $this);
        $table->attributes = $attributes;

        return $table;
    }

    protected function _getTables()
    {
        $results = array();
        $sql = 'SELECT TABLE_NAME FROM '.
            $this->conn->profile['database'].".INFORMATION_SCHEMA.TABLES
                WHERE TABLE_TYPE = 'BASE TABLE' AND
                TABLE_NAME NOT LIKE ('sys%') AND
                TABLE_NAME NOT LIKE ('dt%')";
        $rs = $this->conn->query($sql);
        while ($line = $rs->fetch()) {
            $unpName = $this->conn->unprefixTable($line->TABLE_NAME);
            $results[$unpName] = new Table($line->TABLE_NAME, $this);
        }

        return $results;
    }

    protected function _getTableInstance($name)
    {
        return new Table($name, $this);
    }

    protected function _renameTable($oldName, $newName)
    {
        $this->conn->exec("EXEC sp_rename '".$oldName.
            "', '".$newName."'");
    }
}
