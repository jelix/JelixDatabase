<?php
/**
 * @author     Laurent Jouanneau
 * @copyright  2005-2024 Laurent Jouanneau
 *
 * @see        https://www.jelix.org
 * @licence     http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */
namespace Jelix\Database\Schema\Mysql;

use Jelix\Database\Schema\AbstractSchema;
use Jelix\Database\Schema\Column;
use Jelix\Database\Schema\Exception;
use Jelix\Database\Schema\TableNameInterface;

/**
 */
class Schema extends AbstractSchema
{
    /**
     * @param TableNameInterface          $name
     * @param Column[]     $columns
     * @param string|string[] $primaryKey  names of columns that represents primary keys
     * @param mixed           $attributes
     *
     * @return Table
     */
    public function _createTable(TableNameInterface $name, $columns, $primaryKey, $attributes = array())
    {
        $sql = $this->_createTableQuery($name, $columns, $primaryKey, $attributes);

        if (isset($attributes['engine'])) {
            $sql .= ' ENGINE='.$attributes['engine'];
        }
        if (isset($attributes['charset'])) {
            $sql .= ' CHARACTER SET '.$attributes['charset'];
        }
        if (isset($attributes['collate'])) {
            $sql .= ' COLLATE '.$attributes['collate'];
        }

        $this->conn->exec($sql);

        $table = new Table($name, $this);
        $table->attributes = $attributes;

        return $table;
    }

    public function prepareSqlColumn($col, $isPrimaryKey = false, $isSinglePrimaryKey = false)
    {
        $colStr = parent::prepareSqlColumn($col, $isPrimaryKey, $isSinglePrimaryKey);
        if ($col->comment) {
            $colStr .= ' COMMENT '.$this->conn->quote($col->comment);
        }

        return $colStr;
    }

    protected function _getTables()
    {
        $results = array();
        $profile = $this->conn->profile;
        if (isset($profile['database'])) {
            $db = $profile['database'];
        } elseif (isset($profile['dsn'])
            && preg_match('/dbname=([a-z0-9_ ]*)/', $profile['dsn'], $m)) {
            $db = $m[1];
        } else {
            throw new Exception('No database defined in the profile "'.$profile['name'].'"');
        }
        $rs = $this->conn->query('SHOW TABLES FROM '.$this->conn->encloseName($db));
        $col_name = 'Tables_in_'.$db;
        $prefix = $this->conn->getTablePrefix();

        while ($line = $rs->fetch()) {
            $unpName = $this->conn->unprefixTable($line->{$col_name});
            $results[$unpName] = new Table(new TableName($unpName, '', $prefix), $this);
        }

        return $results;
    }

    protected function _getTableInstance(TableNameInterface $name)
    {
        return new Table($name, $this);
    }

    /**
     * @param Column $col the column
     */
    protected function _getAutoIncrementKeyWord($col, $isPrimaryKey, $isSinglePrimaryKey)
    {
        if ($col->autoIncrement) {
            return ' AUTO_INCREMENT';
        }
        return '';
    }
}
