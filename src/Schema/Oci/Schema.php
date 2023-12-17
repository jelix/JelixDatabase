<?php
/**
 * @author      Laurent Jouanneau
 * @contributor Gwendal Jouannic
 *
 * @copyright   2008 Gwendal Jouannic, 2009-2020 Laurent Jouanneau
 *
 * @see        https://jelix.org
 * @licence     http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */
namespace Jelix\Database\Schema\Oci;

use Jelix\Database\Schema\AbstractSchema;

/**
 */
class Schema extends AbstractSchema
{
    protected function _createTable($name, $columns, $primaryKey, $attributes = array())
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

        $rs = $this->conn->query('SELECT TABLE_NAME FROM USER_TABLES');

        while ($line = $rs->fetch()) {
            $unpName = $this->conn->unprefixTable($line->table_name);
            $results[$unpName] = new Table($line->table_name, $this);
        }

        return $results;
    }

    protected function _getTableInstance($name)
    {
        return new Table($name, $this);
    }

    protected function _renameTable($oldName, $newName)
    {
        $this->conn->exec('RENAME TABLE '.$this->conn->encloseName($oldName).
            ' TO '.$this->conn->encloseName($newName));
    }
}
