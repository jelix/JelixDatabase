<?php
/**
 * @author     Laurent Jouanneau
 * @contributor Laurent Jouanneau
 * @contributor Nicolas Jeudy (patch ticket #99)
 *
 * @copyright  2005-2026 Laurent Jouanneau
 *
 * @see     https://jelix.org
 * @licence  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */
namespace Jelix\Database\Schema\Postgresql;

use Jelix\Database\Connection;
use Jelix\Database\ConnectionInterface;
use Jelix\Database\Schema\FieldProperties;
use Jelix\Database\Schema\Exception;

/**
 */
class SQLTools extends \Jelix\Database\Schema\AbstractSqlTools
{
    /**
     * @deprecated use SQLSyntaxHelpers::trueValue const instead
     * @see Connection::getSqlSyntaxHelpers()
     */
    public $trueValue = 'TRUE';

    /**
     * @deprecated use SQLSyntaxHelpers::falseValue const instead
     * @see Connection::getSqlSyntaxHelpers()
     */
    public $falseValue = 'FALSE';


    public function __construct(?ConnectionInterface $connector = null)
    {
        parent::__construct($connector);
        $this->_syntax = Connection::getSqlSyntaxHelpers(Connection::DB_TYPE_PGSQL);
    }


    /**
     * retrieve the list of fields of a table.
     *
     * @param string $tableName  the name of the table
     * @param string $sequence   the sequence used to auto increment the primary key
     * @param string $schemaName the name of the schema
     *
     * @throws Exception
     *
     * @return FieldProperties[] keys are field names and values are jDbFieldProperties objects
     */
    public function getFieldList($tableName, $sequence = '', $schemaName = '')
    {
        $tableName = $this->_conn->prefixTable($tableName);

        // get table informations
        $sql = 'SELECT pg_class.oid, coalesce(i.indisprimary, false) as relhaspkey, pg_class.relhasindex';
        $sql .= ' FROM pg_class';
        if (!empty($schemaName)) {
            $sql .= ' JOIN pg_catalog.pg_namespace n ON n.oid = pg_class.relnamespace';
        }
        $sql .= ' LEFT JOIN pg_index i ON (indrelid = pg_class.oid AND indisprimary)';
        $sql .= ' WHERE relname = \''.$tableName.'\'';
        if (!empty($schemaName)) {
            $sql .= ' AND n.nspname = \''.$schemaName.'\'';
        }

        $rs = $this->_conn->query($sql);
        if (!($table = $rs->fetch())) {
            throw new Exception('dbtools, pgsql: unknown table');
        }

        $pkeys = array();
        // get primary keys informations
        if ($table->relhaspkey == 't') {
            $sql = 'SELECT indkey FROM pg_index WHERE indrelid = '.$table->oid.' and indisprimary = true';
            $rs = $this->_conn->query($sql);
            $pkeys = preg_split('/[\\s]+/', $rs->fetch()->indkey);
        }

        // get field informations
        $version = $this->_conn->getServerMajorVersion();
        // pg_get_expr on adbin, not compatible with pgsql < 9
        $adColName = ($version < 12 ? 'd.adsrc' : 'pg_get_expr(d.adbin,d.adrelid) AS adsrc');

        $sql_get_fields = "SELECT t.typname, a.attname, a.attnotnull, a.attnum, a.attlen, a.atttypmod, a.attgenerated,
        a.attidentity, a.atthasdef, $adColName
        FROM pg_type t, pg_attribute a LEFT JOIN pg_attrdef d ON (d.adrelid=a.attrelid AND d.adnum=a.attnum)
        WHERE
          a.attnum > 0 AND a.attrelid = ".$table->oid.' AND a.atttypid = t.oid
        ORDER BY a.attnum';

        $toReturn = array();
        $rs = $this->_conn->query($sql_get_fields);
        while ($line = $rs->fetch()) {
            $field = new FieldProperties();
            $field->name = $line->attname;
            $field->type = preg_replace('/(\D*)\d*/', '\\1', $line->typname);
            $field->notNull = ($line->attnotnull == 't');
            $field->hasDefault = ($line->atthasdef == 't');
            $field->default = $line->adsrc;
            $field->generated = ($line->attgenerated != '');

            $typeinfo = $this->_syntax->getTypeInfo($field->type);
            $field->unifiedType = $typeinfo[1];
            $field->maxValue = $typeinfo[3];
            $field->minValue = $typeinfo[2];
            $field->maxLength = $typeinfo[5];
            $field->minLength = $typeinfo[4];

            if ((is_string($line->adsrc) && preg_match('/^nextval\(.*\)$/', $line->adsrc)) || $typeinfo[6]) {
                $field->autoIncrement = true;
                $field->default = '';
            } elseif ($line->attidentity == 'a' || $line->attidentity == 'd') {
                $field->autoIncrement = true;
                $field->default = '';
            }

            if (in_array($line->attnum, $pkeys)) {
                $field->primary = true;
            }

            if ($field->autoIncrement && $sequence && $field->primary) {
                $field->sequence = $sequence;
            }

            if ($line->attlen == -1 && $line->atttypmod != -1) {
                $field->length = $line->atttypmod - 4;
                $field->maxLength = $field->length;
            }

            $toReturn[$line->attname] = $field;
        }

        return $toReturn;
    }

    public function execSQLScript($file)
    {
        $prefix = $this->_conn->getTablePrefix();
        $sqlQueries = str_replace('%%PREFIX%%', $prefix, file_get_contents($file));
        $this->_conn->query($sqlQueries);
    }

    /**
     * @deprecated use SQLSyntaxHelpers::decodeArrayValue() instead
     * @see Connection::getSqlSyntaxHelpers()
     */
    public function decodeArrayValue($value)
    {
        return $this->_syntax->decodeArrayValue($value);
    }

    /**
     * @deprecated use SQLSyntaxHelpers::ARRAY_VALUE_TYPE_INT instead
     * @see Connection::getSqlSyntaxHelpers()
     */
    const ARRAY_VALUE_TYPE_INT = 'int';
    /**
     * @deprecated use SQLSyntaxHelpers::ARRAY_VALUE_TYPE_FLOAT instead
     * @see Connection::getSqlSyntaxHelpers()
     */
    const ARRAY_VALUE_TYPE_FLOAT = 'float';
    /**
     * @deprecated use SQLSyntaxHelpers::ARRAY_VALUE_TYPE_TEXT instead
     * @see Connection::getSqlSyntaxHelpers()
     */
    const ARRAY_VALUE_TYPE_TEXT = 'text';

    /**
     * @deprecated use SQLSyntaxHelpers::encodeArrayValue() instead
     * @see Connection::getSqlSyntaxHelpers()
     */
    public function encodeArrayValue(array $value, $type)
    {
        return $this->_syntax->encodeArrayValue($value, $type);
    }

    public function getDefaultSchemaName(ConnectionInterface $conn)
    {
        $defaultSchema = '';

        // retrieve the search path for the current connection
        $queryString = 'show search_path';
        $result = $conn->query($queryString);
        if ($result) {
            $schemasList = preg_split('/\"?\s*,\s*\"?/', trim($result->fetch()->search_path, " \t\n\r\0\x0B\""));
            if (count($schemasList)) {
                // we take the first existing schema from the list indicated into the search_path
                foreach($schemasList as $schema) {
                    if ($schema == '$user') {
                        $resUser = $conn->query('SELECT CURRENT_USER');
                        if ($resUser && ($user = $resUser->fetch())) {
                            $schema = $user->current_user;
                        }
                        else {
                            continue;
                        }
                    }
                    $sql = "SELECT oid FROM pg_namespace WHERE nspname ILIKE ".$conn->quote($schema);
                    $resSchema = $conn->query($sql);
                    if ($resSchema && ($recSchema = $resSchema->fetch())) {
                        $defaultSchema = $schema;
                        break;
                    }
                }
            }
        }
        return $defaultSchema;
    }
}
