<?php
/**
 * @author     Loic Mathaud
 * @contributor Laurent Jouanneau
 *
 * @copyright  2006 Loic Mathaud, 2007-2026 Laurent Jouanneau
 *
 * @see      https://jelix.org
 * @licence  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */
namespace Jelix\Database\Schema\Sqlite;

use Jelix\Database\Connection;
use Jelix\Database\ConnectionInterface;
use Jelix\Database\Schema\FieldProperties;

/**
 * tools to manage a sqlite database.
 *
 * @package    jelix
 * @subpackage db_driver
 */
class SQLTools extends \Jelix\Database\Schema\AbstractSqlTools
{
    public function __construct(?ConnectionInterface $connector = null)
    {
        parent::__construct($connector);
        $this->_syntax = Connection::getSqlSyntaxHelpers(Connection::DB_TYPE_SQLITE);
    }

    /**
     * retrieve the list of fields of a table.
     *
     * @param string $tableName  the name of the table
     * @param string $sequence   the sequence used to auto increment the primary key (not supported here)
     * @param string $schemaName the name of the schema (only for PostgreSQL, not supported here)
     *
     * @return FieldProperties[] keys are field names and values are jDbFieldProperties objects
     */
    public function getFieldList($tableName, $sequence = '', $schemaName = '')
    {
        $tableName = $this->_conn->prefixTable($tableName);
        $results = array();

        $query = 'PRAGMA table_info('.substr($this->_conn->quote($tableName), 1, -1).')';

        $rs = $this->_conn->query($query);
        while ($line = $rs->fetch()) {
            $field = new FieldProperties();
            $field->name = $line->name;
            $field->primary = ($line->pk == 1);
            $field->notNull = ($line->notnull != 0 || $line->pk == 1);

            if (preg_match('/^(\w+)\s*(\((\d+)\))?.*$/', $line->type, $m)) {
                $field->type = strtolower($m[1]);
                if (isset($m[3])) {
                    $field->length = intval($m[3]);
                }
            } else {
                $field->type = $line->type;
            }

            $typeinfo = $this->getTypeInfo($field->type);
            $field->unifiedType = $typeinfo[1];
            $field->maxValue = $typeinfo[3];
            $field->minValue = $typeinfo[2];
            $field->maxLength = $typeinfo[5];
            $field->minLength = $typeinfo[4];

            if ($field->length != 0) {
                $field->maxLength = $field->length;
            }

            if ($field->type == 'integer' && $field->primary) {
                $field->autoIncrement = true;
            }
            if (!$field->primary) {
                if ($line->dflt_value !== null || ($line->dflt_value === null && !$field->notNull)) {
                    $field->hasDefault = true;
                    $field->default = $line->dflt_value;
                }
            }
            $results[$line->name] = $field;
        }

        return $results;
    }
}
