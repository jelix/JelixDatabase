<?php
/**
 * @author     Laurent Jouanneau
 * @copyright  2024 Laurent Jouanneau
 *
 * @see        https://jelix.org
 * @licence    http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */
namespace Jelix\Database\Schema;

abstract class TableName implements TableNameInterface
{

    protected $tableName = '';

    protected $schemaName = '';

    protected $supportSchema = true;

    protected $encloseCharacterLeft = '';
    protected $encloseCharacterRight = '';


    public function __construct($name, $schemaName = '')
    {
        if (strpos($name, '.') !== false) {
            // we get only two last element.
            $path = explode('.', $name);
            $name = array_pop($path);
            if ($this->supportSchema) {
                $schema = array_pop($path);
                if ($schema) {
                    $schemaName = $schema;
                }
            }
        }

        $this->tableName = $name;
        if ($this->supportSchema) {
            $this->schemaName = $schemaName;
        }
    }

    public function getTableName()
    {
        return $this->tableName;
    }

    public function getSchemaName()
    {
        return $this->schemaName;
    }

    public function getFullName()
    {
        if ($this->schemaName) {
            return $this->schemaName.'.'.$this->tableName;
        }
        return $this->tableName;
    }

    public function getEnclosedFullName()
    {
        $cl = $this->encloseCharacterLeft;
        $cr = $this->encloseCharacterRight;
        if ($this->schemaName) {
            return $cl.$this->schemaName.$cr.'.'.$cl.$this->tableName.$cr;
        }
        return $cl.$this->tableName.$cr;
    }
}