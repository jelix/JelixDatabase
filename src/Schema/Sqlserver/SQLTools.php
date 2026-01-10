<?php
/**
 * @author     Yann Lecommandoux
 * @contributor Julien, Laurent Jouanneau
 *
 * @copyright  2008 Yann Lecommandoux, 2010 Julien, 2017-2026 Laurent Jouanneau
 *
 * @see      https://jelix.org
 * @licence  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */
namespace Jelix\Database\Schema\Sqlserver;

use Jelix\Database\Connection;
use Jelix\Database\ConnectionInterface;
use Jelix\Database\Schema\FieldProperties;

class SQLTools extends \Jelix\Database\Schema\AbstractSqlTools
{
    protected $dbmsStyle = array('/^\s*(#|\-\- )/', '/;\s*$/');

    public function __construct(?ConnectionInterface $connector = null)
    {
        parent::__construct($connector);
        $this->_syntax = Connection::getSqlSyntaxHelpers(Connection::DB_TYPE_SQLSERVER);
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
        $results = array();

        $pkeys = array();
        // get primary keys informations
        $rs = $this->_conn->query('EXEC sp_pkeys '.$tableName);
        while ($line = $rs->fetch()) {
            $pkeys[] = $line->COLUMN_NAME;
        }
        // get table informations
        unset($line);
        $rs = $this->_conn->query('EXEC sp_columns '.$tableName);
        while ($line = $rs->fetch()) {
            $field = new FieldProperties();
            $field->name = $line->COLUMN_NAME;
            $field->type = $line->TYPE_NAME;
            $field->length = $line->LENGTH;
            if ($field->type == 'int identity') {
                $field->type = 'int';
                $field->autoIncrement = true;
            }
            if ($field->type == 'bit') {
                $field->type = 'int';
            }
            if ($line->NULLABLE) {
                $field->notNull = false;
            }
            if ($line->COLUMN_DEF === null) {
                if ($field->notNull) {
                    $field->hasDefault = true;
                    $field->default = '';
                } else {
                    $field->hasDefault = true;
                    $field->default = null;
                }
            } else {
                $field->hasDefault = ($line->COLUMN_DEF !== '');
                $field->default = str_replace(array('((', '))'), array('',''), $line->COLUMN_DEF);
            }

            if (in_array($field->name, $pkeys)) {
                $field->primary = true;
            }
            $results[$line->COLUMN_NAME] = $field;
        }

        return $results;
    }
}
