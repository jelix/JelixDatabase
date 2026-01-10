<?php
/**
* @author      Laurent Jouanneau
* @copyright   2009-2020 Laurent Jouanneau
* @link        https://jelix.org
* @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
*/
use Jelix\Database\Utilities;
use \Jelix\Database\Schema;
use \Jelix\Database\Connection;

class toolsTest extends \Jelix\UnitTests\UnitTestCaseDb {

    protected static $connectionMysql = null;

    protected function getConnection()
    {
        if (self::$connectionMysql === null) {
            $parameters = new \Jelix\Database\AccessParameters(array(
                'driver'=>'mysqli',
                'host'=>'mysql',
                'user'=>'jelix',
                'password'=>"jelixpass",
                "database"=>"jelixtests"
            ), array('charset'=>'UTF-8'));


            self::$connectionMysql = Connection::create($parameters);
        }
        return self::$connectionMysql;
    }


    protected static $connectionPgsql = null;

    protected function getPgConnection()
    {
        if (self::$connectionPgsql === null) {
            $parameters = new \Jelix\Database\AccessParameters(array(
                'driver'=>'pgsql',
                'host'=>'pgsql',
                'user'=>'jelix',
                'password'=>"jelixpass",
                "database"=>"jelixtests",
                'search_path'=>"public,newspaper"
            ), array('charset'=>'UTF-8'));

            self::$connectionPgsql = Connection::create($parameters);
        }
        return self::$connectionPgsql;
    }

    public static function setUpBeforeClass() : void {
    }

    function testParseCreateTable()
    {
        $tools = new Schema\Mysql\SQLTools($this->getConnection());
        $sql = "CREATE TABLE city (
                city_id INTEGER  PRIMARY KEY AUTOINCREMENT,
                country_id integer NOT NULL,
                name  varchar(50) not null,
                postcode integer DEFAULT 0,
                latitude varchar(20), longitude varchar(20),
                CONSTRAINT coordinates
                 UNIQUE(latitude, longitude),
                FOREIGN KEY (country_id) REFERENCES country (country_id))
                ";
        $result = $tools->parseCREATETABLE($sql);
        $this->assertTrue($result !== false);
        $this->assertEquals('city', $result['name']);
        $this->assertEquals('', $result['options']);
        $this->assertFalse($result['temporary']);
        $this->assertFalse($result['ifnotexists']);
        $this->assertEquals(array(
            'city_id INTEGER PRIMARY KEY AUTOINCREMENT',
            'country_id integer NOT NULL',
            'name varchar(50) not null',
            'postcode integer DEFAULT 0',
            'latitude varchar(20)',
            'longitude varchar(20)',
        ), $result['columns']);
        $this->assertEquals(array(
            'CONSTRAINT coordinates UNIQUE(latitude, longitude)',
            'FOREIGN KEY (country_id) REFERENCES country (country_id)',
        ), $result['constraints']);

    }

    function testParseCreateTemporaryTable() {
        $tools = new Schema\Mysql\SQLTools($this->getConnection());
        $sql = "CREATE TEMPORARY 
                TABLE IF NOT
                EXISTS city (
                city_id INTEGER  PRIMARY KEY AUTOINCREMENT,
                country_id integer NOT NULL,
                name  varchar(50) not null,
                postcode integer DEFAULT 0,
                latitude varchar(20), longitude varchar(20),
                CONSTRAINT coordinates
                 UNIQUE(latitude, longitude),
                FOREIGN KEY (country_id) REFERENCES country (country_id)
                ) BIDULE ";
        $result = $tools->parseCREATETABLE($sql);
        $this->assertTrue($result !== false);
        $this->assertEquals('city', $result['name']);
        $this->assertEquals('BIDULE', $result['options']);
        $this->assertTrue($result['temporary']);
        $this->assertTrue($result['ifnotexists']);
        $this->assertEquals(array(
            'city_id INTEGER PRIMARY KEY AUTOINCREMENT',
            'country_id integer NOT NULL',
            'name varchar(50) not null',
            'postcode integer DEFAULT 0',
            'latitude varchar(20)',
            'longitude varchar(20)',
        ), $result['columns']);
        $this->assertEquals(array(
            'CONSTRAINT coordinates UNIQUE(latitude, longitude)',
            'FOREIGN KEY (country_id) REFERENCES country (country_id)',
        ), $result['constraints']);
    }

    function testParseCreateSchemaTable()
    {
        $tools = new Schema\Postgresql\SQLTools($this->getPgConnection());
        $sql = "CREATE TABLE \"newspaper\".\"test_article\" (
                art_id INTEGER  PRIMARY KEY AUTOINCREMENT,
                name  varchar(50) not null)
                ";
        $result = $tools->parseCREATETABLE($sql);
        $this->assertTrue($result !== false);
        $this->assertEquals('test_article', $result['name']);
        $this->assertInstanceOf(Schema\Postgresql\TableName::class, $result['tableName']);
        $this->assertEquals('', $result['options']);
        $this->assertEquals('newspaper.test_article', $result['tableName']->getFullName());
        $this->assertFalse($result['temporary']);
        $this->assertFalse($result['ifnotexists']);
    }

    function testInsertDataEmptyTableBefore() {

        $cnt = $this->getConnection();
        $tools = $cnt->tools();

        $columns = array('key', 'keyalias', 'lang', 'label');

        // insert a record
        $records = array(
            array('key'=>5, 'keyalias'=>'dummy', 'lang'=>'en', 'label'=>'this is dummy'),
        );
        $this->insertRecordsIntoTable('labels_test',
            $columns,
            $records,
            true);

        // now insert new data, dummy record should not exist
        $count = $tools->insertBulkData('labels_test',
            $columns,
            array(
                array(8, 'foo.label1', 'fr', 'un label1'),
                array(8, 'foo.label2', 'en', 'a label1'),
                array(10, 'foo.label3', 'fr', 'super label')
            ),
            array('key', 'lang'),
            $tools::IBD_EMPTY_TABLE_BEFORE
        );
        $this->assertEquals(3, $count);

        $records = array(
            array('key' => 8, 'keyalias'=>'foo.label1', 'lang'=>'fr', 'label'=>'un label1'),
            array('key' => 8, 'keyalias'=>'foo.label2', 'lang'=>'en', 'label'=>'a label1'),
            array('key' => 10, 'keyalias'=>'foo.label3', 'lang'=>'fr', 'label'=>'super label')
        );
        $this->assertTableContainsRecords('labels_test', $records, true);
        $this->assertTableHasNRecords('labels_test', 3);


        $tools->insertBulkData('labels_test',
            $columns,
            array(
            ),
            array('key', 'lang'),
            $tools::IBD_EMPTY_TABLE_BEFORE
        );
        $this->assertTableIsEmpty('labels_test');
    }

    function testInsertDataOnlyEmptyTableWhereTableIsNotEmpty() {
        $cnt = $this->getConnection();;
        $tools = $cnt->tools();

        $columns = array('key', 'keyalias', 'lang', 'label');

        // insert a record
        $records = array(
            array('key'=>5, 'keyalias'=>'dummy', 'lang'=>'en', 'label'=>'this is dummy'),
        );
        $this->insertRecordsIntoTable('labels_test',
            $columns,
            $records,
            true);

        // now insert new data, given records should not exist
        $count = $tools->insertBulkData('labels_test',
            $columns,
            array(
                array(8, 'foo.label1', 'fr', 'un label1'),
                array(8, 'foo.label2', 'en', 'a label1'),
                array(10, 'foo.label3', 'fr', 'super label')
            ),
            array('key', 'lang'),
            $tools::IBD_INSERT_ONLY_IF_TABLE_IS_EMPTY
        );
        $this->assertEquals(0, $count);
        $this->assertTableContainsRecords('labels_test', $records, true);
        $this->assertTableHasNRecords('labels_test', 1);
    }

    function testInsertDataOnlyEmptyTableWhereTableIsEmpty() {
        $cnt = $this->getConnection();
        $tools = $cnt->tools();

        $this->emptyTable('labels_test');

        $columns = array('key', 'keyalias', 'lang', 'label');

        $count = $tools->insertBulkData('labels_test',
            $columns,
            array(
                array(8, 'foo.label1', 'fr', 'un label1'),
                array(8, 'foo.label2', 'en', 'a label1'),
                array(10, 'foo.label3', 'fr', 'super label')
            ),
            array('key', 'lang'),
            $tools::IBD_INSERT_ONLY_IF_TABLE_IS_EMPTY
        );
        $this->assertEquals(3, $count);

        $records = array(
            array('key' => 8, 'keyalias'=>'foo.label1', 'lang'=>'fr', 'label'=>'un label1'),
            array('key' => 8, 'keyalias'=>'foo.label2', 'lang'=>'en', 'label'=>'a label1'),
            array('key' => 10, 'keyalias'=>'foo.label3', 'lang'=>'fr', 'label'=>'super label')
        );
        $this->assertTableContainsRecords('labels_test', $records, true);
        $this->assertTableHasNRecords('labels_test', 3);
    }

    function testInsertDataIgnoreIfExistNoExistingRecords() {
        $cnt = $this->getConnection();
        $tools = $cnt->tools();

        $columns = array('key', 'keyalias', 'lang', 'label');

        $records = array(
            array('key'=>5, 'keyalias'=>'dummy', 'lang'=>'en', 'label'=>'this is dummy'),
        );
        $this->insertRecordsIntoTable('labels_test',
            $columns,
            $records,
            true);

        $count = $tools->insertBulkData('labels_test',
            $columns,
            array(
                array(5, 'foo.label1', 'fr', 'un label1'),
                array(8, 'foo.label2', 'en', 'a label1'),
                array(10, 'foo.label3', 'fr', 'super label')
            ),
            array('key', 'lang'),
            $tools::IBD_IGNORE_IF_EXIST
        );
        $this->assertEquals(3, $count);

        $records = array(
            array('key'=>5, 'keyalias'=>'dummy', 'lang'=>'en', 'label'=>'this is dummy'),
            array('key' => 5, 'keyalias'=>'foo.label1', 'lang'=>'fr', 'label'=>'un label1'),
            array('key' => 8, 'keyalias'=>'foo.label2', 'lang'=>'en', 'label'=>'a label1'),
            array('key' => 10, 'keyalias'=>'foo.label3', 'lang'=>'fr', 'label'=>'super label')
        );
        $this->assertTableContainsRecords('labels_test', $records, true);
        $this->assertTableHasNRecords('labels_test', 4);
    }

    function testInsertDataIgnoreIfExistExistingRecords() {
        $cnt = $this->getConnection();
        $tools = $cnt->tools();

        $columns = array('key', 'keyalias', 'lang', 'label');

        $records = array(
            array('key'=>5, 'keyalias'=>'dummy', 'lang'=>'en', 'label'=>'this is dummy'),
        );
        $this->insertRecordsIntoTable('labels_test',
            $columns,
            $records,
            true);

        $count = $tools->insertBulkData('labels_test',
            $columns,
            array(
                array(5, 'foo.label1', 'en', 'un label1'),
                array(8, 'foo.label2', 'en', 'a label1'),
                array(10, 'foo.label3', 'fr', 'super label')
            ),
            array('key', 'lang'),
            $tools::IBD_IGNORE_IF_EXIST
        );
        $this->assertEquals(2, $count);

        $records = array(
            array('key'=>5, 'keyalias'=>'dummy', 'lang'=>'en', 'label'=>'this is dummy'),
            array('key' => 8, 'keyalias'=>'foo.label2', 'lang'=>'en', 'label'=>'a label1'),
            array('key' => 10, 'keyalias'=>'foo.label3', 'lang'=>'fr', 'label'=>'super label')
        );
        $this->assertTableContainsRecords('labels_test', $records, true);
        $this->assertTableHasNRecords('labels_test', 3);
    }


    function testInsertDataUpdateIfExistNoExistingRecords() {
        $cnt = $this->getConnection();
        $tools = $cnt->tools();

        $columns = array('key', 'keyalias', 'lang', 'label');

        $records = array(
            array('key'=>5, 'keyalias'=>'dummy', 'lang'=>'en', 'label'=>'this is dummy'),
        );
        $this->insertRecordsIntoTable('labels_test',
            $columns,
            $records,
            true);

        $count = $tools->insertBulkData('labels_test',
            $columns,
            array(
                array(5, 'foo.label1', 'fr', 'un label1'),
                array(8, 'foo.label2', 'en', 'a label1'),
                array(10, 'foo.label3', 'fr', 'super label')
            ),
            array('key', 'lang'),
            $tools::IBD_UPDATE_IF_EXIST
        );
        $this->assertEquals(3, $count);

        $records = array(
            array('key'=>5, 'keyalias'=>'dummy', 'lang'=>'en', 'label'=>'this is dummy'),
            array('key' => 5, 'keyalias'=>'foo.label1', 'lang'=>'fr', 'label'=>'un label1'),
            array('key' => 8, 'keyalias'=>'foo.label2', 'lang'=>'en', 'label'=>'a label1'),
            array('key' => 10, 'keyalias'=>'foo.label3', 'lang'=>'fr', 'label'=>'super label')
        );
        $this->assertTableContainsRecords('labels_test', $records, true);
        $this->assertTableHasNRecords('labels_test', 4);
    }

    function testInsertDataUpdateIfExistExistingRecords() {
        $cnt = $this->getConnection();
        $tools = $cnt->tools();

        $columns = array('key', 'keyalias', 'lang', 'label');

        $records = array(
            array('key'=>5, 'keyalias'=>'dummy', 'lang'=>'en', 'label'=>'this is dummy'),
        );
        $this->insertRecordsIntoTable('labels_test',
            $columns,
            $records,
            true);

        $count = $tools->insertBulkData('labels_test',
            $columns,
            array(
                array(5, 'foo.label1', 'en', 'un label1'),
                array(8, 'foo.label2', 'en', 'a label1'),
                array(10, 'foo.label3', 'fr', 'super label')
            ),
            array('key', 'lang'),
            $tools::IBD_UPDATE_IF_EXIST
        );
        $this->assertEquals(3, $count);

        $records = array(
            array('key'=>5, 'keyalias'=>'foo.label1', 'lang'=>'en', 'label'=>'un label1'),
            array('key' => 8, 'keyalias'=>'foo.label2', 'lang'=>'en', 'label'=>'a label1'),
            array('key' => 10, 'keyalias'=>'foo.label3', 'lang'=>'fr', 'label'=>'super label')
        );
        $this->assertTableContainsRecords('labels_test', $records, true);
        $this->assertTableHasNRecords('labels_test', 3);
    }
}

