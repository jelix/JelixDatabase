<?php
/**
 * @author     Gérald Croes, Laurent Jouanneau
 * @contributor Laurent Jouanneau
 * @contributor Florian Lonqueu-Brochard
 *
 * @copyright  2001-2005 CopixTeam, 2005-2025 Laurent Jouanneau
 * @copyright  2012 Florian Lonqueu-Brochard
 *
 * @see      https://jelix.org
 * @licence  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */
namespace Jelix\Database\Schema\Mysql;

use Jelix\Database\Schema\FieldProperties;

/**
 * Provides utilities methods for a mysql database.
 *
 */
class SQLTools extends \Jelix\Database\Schema\AbstractSqlTools
{
    protected $typesInfo = array(
        // type                  native type        unified type  minvalue     maxvalue   minlength  maxlength
        'bool' => array('boolean',          'boolean',  0,           1,          null,     null),
        'boolean' => array('boolean',          'boolean',  0,           1,          null,     null),
        'bit' => array('bit',              'integer',  0,           1,          null,     null),
        'tinyint' => array('tinyint',          'integer',  -128,        127,        null,     null),
        'smallint' => array('smallint',         'integer',  -32768,      32767,      null,     null),
        'mediumint' => array('mediumint',        'integer',  -8388608,    8388607,    null,     null),
        'integer' => array('integer',          'integer',  -2147483648, 2147483647, null,     null),
        'int' => array('integer',          'integer',  -2147483648, 2147483647, null,     null),
        'bigint' => array('bigint',           'numeric',  '-9223372036854775808', '9223372036854775807', null, null),
        'serial' => array('integer',          'numeric',  '-9223372036854775808', '9223372036854775807', null, null),
        'bigserial' => array('integer',          'numeric',  '-9223372036854775808', '9223372036854775807', null, null),
        'autoincrement' => array('integer',          'integer',  -2147483648, 2147483647, null,     null), // for old dao files
        'bigautoincrement' => array('bigint',           'numeric',  '-9223372036854775808', '9223372036854775807', null, null), // for old dao files

        'float' => array('float',            'float',    null,       null,       null,     null), //4bytes
        'money' => array('float',            'float',    null,       null,       null,     null), //4bytes
        'smallmoney' => array('float',            'float',    null,       null,       null,     null),
        'double precision' => array('double precision', 'decimal',  null,       null,       null,     null), //8bytes
        'double' => array('double precision', 'decimal',  null,       null,       null,     null), //8bytes
        'real' => array('real',             'decimal',  null,       null,       null,     null), //8bytes
        'number' => array('real',             'decimal',  null,       null,       null,     null), //8bytes
        'binary_float' => array('float',            'float',    null,       null,       null,     null), //4bytes
        'binary_double' => array('real',             'decimal',  null,       null,       null,     null), //8bytes

        'numeric' => array('numeric',          'numeric',  null,       null,       null,     null),
        'decimal' => array('decimal',          'decimal',  null,       null,       null,     null),
        'dec' => array('decimal',          'decimal',  null,       null,       null,     null),

        'date' => array('date',       'date',       null,       null,       10,    10),
        'time' => array('time',       'time',       null,       null,       8,     8),
        'datetime' => array('datetime',   'datetime',   null,       null,       19,    19),
        'datetime2' => array('datetime',   'datetime',   null,       null,       19,    27), // sqlsrv / 9999-12-31 23:59:59.9999999
        'datetimeoffset' => array('datetime',   'datetime',   null,       null,       19,    34), // sqlsrv / 9999-12-31 23:59:59.9999999 +14:00
        'smalldatetime' => array('datetime',   'datetime',   null,       null,       19,    19), // sqlsrv / 2079-06-06 23:59
        'timestamp' => array('datetime',   'datetime',   null,       null,       19,    19), // oracle/pgsql timestamp
        'utimestamp' => array('timestamp',  'integer',    0,          2147483647, null,  null), // mysql timestamp
        'year' => array('year',       'year',       null,       null,       2,     4),
        'interval' => array('datetime',   'datetime',   null,       null,       19,    19),

        'char' => array('char',       'char',       null,       null,       0,     255),
        'nchar' => array('char',       'char',       null,       null,       0,     255),
        'varchar' => array('varchar',    'varchar',    null,       null,       0,     65535),
        'varchar2' => array('varchar',    'varchar',    null,       null,       0,     4000),
        'nvarchar2' => array('varchar',    'varchar',    null,       null,       0,     4000),
        'character' => array('varchar',    'varchar',    null,       null,       0,     65535),
        'character varying' => array('varchar',   'varchar',    null,       null,       0,     65535),
        'name' => array('varchar',    'varchar',    null,       null,       0,     64),
        'longvarchar' => array('varchar',    'varchar',    null,       null,       0,     65535),
        'string' => array('varchar',    'varchar',    null,       null,       0,     65535), // for old dao files

        'tinytext' => array('tinytext',   'text',       null,       null,       0,     255),
        'text' => array('text',       'text',       null,       null,       0,     65535),
        'ntext' => array('text',       'text',       null,       null,       0,     0),
        'mediumtext' => array('mediumtext', 'text',       null,       null,       0,     16777215),
        'longtext' => array('longtext',   'text',       null,       null,       0,     0),
        'long' => array('longtext',   'text',       null,       null,       0,     0),
        'clob' => array('longtext',   'text',       null,       null,       0,     0),
        'nclob' => array('longtext',   'text',       null,       null,       0,     0),

        'json' => array('json', 'json',       null,       null,       0,     0),
        'jsonb' => array('json', 'json',       null,       null,       0,     0),

        'tinyblob' => array('tinyblob',   'varbinary',  null,       null,       0,     255),
        'blob' => array('blob',       'varbinary',  null,       null,       0,     65535),
        'mediumblob' => array('mediumblob', 'varbinary',  null,       null,       0,     16777215),
        'longblob' => array('longblob',   'varbinary',  null,       null,       0,     0),
        'bfile' => array('longblob',   'varbinary',  null,       null,       0,     0),

        'bytea' => array('longblob',   'varbinary',  null,       null,       0,     0),
        'binary' => array('binary',     'binary',     null,       null,       0,     255),
        'varbinary' => array('varbinary',  'varbinary',  null,       null,       0,     255),
        'raw' => array('varbinary',  'varbinary',  null,       null,       0,     2000),
        'long raw' => array('varbinary',  'varbinary',  null,       null,       0,     0),
        'image' => array('varbinary',  'varbinary',  null,       null,       0,     0),

        'enum' => array('varchar',    'varchar',    null,       null,       0,     65535),
        'set' => array('varchar',    'varchar',    null,       null,       0,     65535),
        'xmltype' => array('varchar',    'varchar',    null,       null,       0,     65535),
        'xml' => array('text',       'text',       null,       null,       0,     0),

        'point' => array('varchar',    'varchar',    null,       null,       0,     16),
        'line' => array('varchar',    'varchar',    null,       null,       0,     32),
        'lsed' => array('varchar',    'varchar',    null,       null,       0,     32),
        'box' => array('varchar',    'varchar',    null,       null,       0,     32),
        'path' => array('varchar',    'varchar',    null,       null,       0,     65535),
        'polygon' => array('varchar',    'varchar',    null,       null,       0,     65535),
        'circle' => array('varchar',    'varchar',    null,       null,       0,     24),
        'cidr' => array('varchar',    'varchar',    null,       null,       0,     24),
        'inet' => array('varchar',    'varchar',    null,       null,       0,     24),
        'macaddr' => array('integer',    'integer',    0,          0xFFFFFFFFFFFF, null,       null),
        'bit varying' => array('varchar',    'varchar',    null,       null,       0,     65535),
        'arrays' => array('varchar',    'varchar',    null,       null,       0,     65535),
        'complex types' => array('varchar',    'varchar',    null,       null,       0,     65535),
    );

    protected $keywordNameCorrespondence = array(
        // sqlsrv,mysql,oci,pgsql -> date+time
        //'current_timestamp' => '',
        // mysql,oci,pgsql -> date
        //'current_date' => '',
        // mysql -> time, pgsql -> time+timezone
        //'current_time' => '',
        // oci -> date+fractional secon + timezone
        'systimestamp' => 'current_timestamp',
        // oci -> date+time+tz
        'sysdate' => 'current_timestamp',
        // pgsql -> time
        'localtime' => 'current_time',
        // pgsql -> date+time
        //'localtimestamp' => '',
    );

    protected $functionNameCorrespondence = array(

        // sqlsrv, -> date+time
        'sysdatetime' => 'current_timestamp',
        // sqlsrv, -> date+time+offset
        'sysdatetimeoffset' => 'current_timestamp',
        // sqlsrv, -> date+time at utc
        'sysutcdatetime' => 'UTC_TIMESTAMP()',
        // sqlsrv -> date+time
        'getdate' => 'current_timestamp',
        // sqlsrv -> date+time at utc
        'getutcdate' => 'UTC_TIMESTAMP()',
        // sqlsrv,mysql (datetime)-> integer
        //'day' => '',
        // sqlsrv,mysql (datetime)-> integer
        //'month' => '',
        // sqlsrv, mysql (datetime)-> integer
        //'year' => '',
        // mysql -> date
        //'curdate' => '',
        // mysql -> date
        //'current_date' => '',
        // mysql -> time
        //'curtime' => '',
        // mysql -> time
        //'current_time' => '',
        // mysql,pgsql -> date+time
        //'now' => '',
        // mysql date+time
        //'current_timestamp' => '',
        // mysql (datetime)->date, sqlite (timestring, modifier)->date
        //'date' => '!dateConverter',
        // mysql = day()
        //'dayofmonth' => '',
        // mysql -> date+time
        //'localtime' => '',
        // mysql -> date+time
        //'localtimestamp' => '',
        // mysql utc current date
        //'utc_date' => '',
        // mysql utc current time
        //'utc_time' => '',
        // mysql utc current date+time
        //'utc_timestamp' => '',
        // mysql (datetime)->time, , sqlite (timestring, modifier)->time
        //'time' => '!timeConverter',
        // mysql (datetime/time)-> hour
        //'hour'=> '',
        // mysql (datetime/time)-> minute
        //'minute'=> '',
        // mysql (datetime/time)-> second
        //'second'=> '',
        // sqlite (timestring, modifier)->datetime
        'datetime' => 'DATE_FORMAT(%1p, \'%Y-%m-%d %H:%i:%s\')',
        // oci, mysql (year|month|day|hour|minute|second FROM <datetime>)->value ,
        // pgsql (year|month|day|hour|minute|second <datetime>)->value
        'extract' => '!extractDateConverter',
        // pgsql ('year'|'month'|'day'|'hour'|'minute'|'second', <datetime>)->value
        'date_part' => '!extractDateConverter',
        // sqlsrv (year||month|day|hour|minute|second, <datetime>)->value
        'datepart' => '!extractDateConverter',
    );

    public function encloseName($name)
    {
        return '`'.$name.'`';
    }

    /**
     * retrieve the list of fields of a table.
     *
     * @param string $tableName  the name of the table
     * @param string $sequence   the sequence used to auto increment the primary key (not supported here)
     * @param string $schemaName the name of the schema (only for PostgreSQL, not supported here)
     *
     * @return array keys are field names and values are jDbFieldProperties objects
     */
    public function getFieldList($tableName, $sequence = '', $schemaName = '')
    {
        $tableName = $this->_conn->prefixTable($tableName);
        $results = array();

        // get FULL table information (to get comment for label form)
        $rs = $this->_conn->query('SHOW FULL FIELDS FROM `'.$tableName.'`');

        while ($line = $rs->fetch()) {
            $field = new FieldProperties();

            if (preg_match('/^(\w+)\s*(\((\d+)\))?.*$/', $line->Type, $m)) {
                $field->type = strtolower($m[1]);
                if ($field->type == 'varchar' && isset($m[3])) {
                    $field->length = intval($m[3]);
                }
            } else {
                $field->type = $line->Type;
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

            $field->name = $line->Field;
            $field->notNull = ($line->Null == 'NO');
            $field->primary = ($line->Key == 'PRI');
            $field->autoIncrement = ($line->Extra == 'auto_increment');
            $field->hasDefault = ($line->Default != '' || !($line->Default == null && $field->notNull));
            // use Mysql comment on dao and form
            if (isset($line->Comment) && $line->Comment != '') {
                $field->comment = $line->Comment;
            }
            // to fix a bug in php 5.2.5 or mysql 5.0.51
            if ($field->notNull && $line->Default === null && !$field->autoIncrement) {
                $field->default = '';
            } else {
                $field->default = $line->Default;
            }
            $results[$line->Field] = $field;
        }

        return $results;
    }

    public function execSQLScript($file)
    {
        $prefix = $this->_conn->getTablePrefix();
        $sqlQueries = str_replace('%%PREFIX%%', $prefix, file_get_contents($file));
        $queries = $this->parseSQLScript($sqlQueries);
        foreach ($queries as $query) {
            $this->_conn->exec($query);
        }

        return count($queries);
    }

    /**
     * @param mixed $script
     */
    protected function parseSQLScript($script)
    {
        $distinctDelimiters = array(';');
        if (preg_match_all("/DELIMITER ([^\n]*)/i", $script, $d, PREG_SET_ORDER)) {
            $delimiters = $d[1];
            $distinctDelimiters = array_unique(array_merge($distinctDelimiters, $delimiters));
        }
        $preg = '';
        foreach ($distinctDelimiters as $dd) {
            $preg .= '|'.preg_quote($dd);
        }

        $tokens = preg_split('!(\'|"|\\\\|`|DELIMITER |#|/\\*|\\*/|\\-\\-(?=\s)|'."\n".$preg.')!i', $script, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        $currentDelimiter = ';';
        $context = 0;
        $queries = array();
        $query = '';
        $previousToken = '';
        foreach ($tokens as $k => $token) {
            switch ($context) {
                // 0 : statement
                case 0:
                    $previousToken = $token;
                    switch ($token) {
                        case $currentDelimiter:
                            if (preg_replace('/\\s/', '', $query) != '') {
                                $queries[] = trim($query);
                            }
                            $query = '';

                            break;
                        case '\'':
                            $context = 1;
                            $previousToken = '';
                            $query .= $token;

                            break;
                        case '"':
                            $context = 2;
                            $previousToken = '';
                            $query .= $token;

                            break;
                        case '`':
                            $context = 3;
                            $query .= $token;
                            $previousToken = '';

                            break;
                        case 'DELIMITER ':
                            $context = 6;

                            break;
                        case '#':
                        case '--':
                            $context = 4;

                            break;
                        case '/*':
                            $context = 5;

                            break;
                        case "\n":
                        default:
                            $query .= $token;
                    }

                    break;
                // 1 : string '
                case 1:
                    if ($token == "'") {
                        if ($previousToken != '\\' && $previousToken != "'") {
                            if (isset($tokens[$k + 1])) {
                                if ($tokens[$k + 1] != "'") {
                                    $context = 0;
                                }
                            } else {
                                $context = 0;
                            }
                        }
                    }
                    $previousToken = $token;
                    $query .= $token;

                    break;
                // 2 : string "
                case 2:
                    if ($token == '"') {
                        if ($previousToken != '\\' && $previousToken != '"') {
                            if (isset($tokens[$k + 1])) {
                                if ($tokens[$k + 1] != '"') {
                                    $context = 0;
                                }
                            } else {
                                $context = 0;
                            }
                        }
                    }
                    $previousToken = $token;
                    $query .= $token;

                    break;
                // 3 : name with `
                case 3:
                    if ($token == '`') {
                        if ($previousToken != '\\' && $previousToken != '`') {
                            if (isset($tokens[$k + 1])) {
                                if ($tokens[$k + 1] != '`') {
                                    $context = 0;
                                }
                            } else {
                                $context = 0;
                            }
                        }
                    }
                    $previousToken = $token;
                    $query .= $token;

                    break;
                // 4 : comment single line
                case 4:
                    if ($token == "\n") {
                        //$query.=$token;
                        $context = 0;
                    }

                    break;
                // 5 : comment multi line
                case 5:
                    if ($token == '*/') {
                        $context = 0;
                    }

                    break;
                // 6 : delimiter definition
                case 6:
                    $currentDelimiter = $token;
                    $context = 0;

                    break;
            }
        }
        if (preg_replace('/\\s/', '', $query) != '') {
            $queries[] = trim($query);
        }

        return $queries;
    }
}
