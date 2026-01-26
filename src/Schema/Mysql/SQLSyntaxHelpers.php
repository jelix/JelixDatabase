<?php
/**
 * @author     Laurent Jouanneau
 * @contributor Laurent Jouanneau
 * @contributor Florian Lonqueu-Brochard
 *
 * @copyright  2016-2026 Laurent Jouanneau
 * @copyright  2012 Florian Lonqueu-Brochard
 *
 * @see      https://jelix.org
 * @licence  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */
namespace Jelix\Database\Schema\Mysql;

/**
 * Helps to manipulate SQL syntax content for Mysql
 *
 */
class SQLSyntaxHelpers extends \Jelix\Database\Schema\AbstractSQLSyntaxHelpers
{
    protected const sqlType = 'mysql';

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

    public function encloseName(string $fieldName) : string
    {
        return '`'.$fieldName.'`';
    }

    public function quoteString($value): string
    {
        return "'".str_replace(
            array('\\', "\0", "\n", "\r", "'", '"', "\x1a"),
            array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), $value)
            ."'";
    }

}
