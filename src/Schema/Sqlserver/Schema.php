<?php
/**
 * @author     Laurent Jouanneau
 * @copyright  2010-2024 Laurent Jouanneau
 *
 * @see        https://jelix.org
 * @licence     http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */
namespace Jelix\Database\Schema\Sqlserver;

use Jelix\Database\Schema\AbstractSchema;
use Jelix\Database\Schema\TableNameInterface;

/**
 */
class Schema extends AbstractSchema
{
    protected function _createTable(TableNameInterface $name, $columns, $primaryKey, $attributes = array())
    {
        $sql = $this->_createTableQuery($name, $columns, $primaryKey, $attributes);

        $this->conn->exec($sql);

        $table = new Table($name, $this);
        $table->attributes = $attributes;

        return $table;
    }

    protected function _getTables()
    {
        $results = array();
        $sql = 'SELECT TABLE_SCHEMA, TABLE_NAME FROM '.
            $this->conn->profile['database'].".INFORMATION_SCHEMA.TABLES
                WHERE TABLE_TYPE = 'BASE TABLE' AND
                TABLE_NAME NOT LIKE ('sys%') AND
                TABLE_NAME NOT LIKE ('dt%')";
        $rs = $this->conn->query($sql);
        $prefix = $this->conn->getTablePrefix();
        while ($line = $rs->fetch()) {
            $unpName = $this->conn->unprefixTable($line->TABLE_NAME);
            $tableName = new TableName($unpName, $line->TABLE_SCHEMA, $prefix);
            $results[$tableName->getFullName()] = new Table($tableName, $this);
        }

        return $results;
    }

    protected function _getTableInstance(TableNameInterface $name)
    {
        return new Table($name, $this);
    }

    protected function _renameTable(TableNameInterface $oldName, TableNameInterface $newName)
    {
        $this->conn->exec("EXEC sp_rename '".$oldName->getTableName().
            "', '".$newName->getTableName()."'");
    }
}
