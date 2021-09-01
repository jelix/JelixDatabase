<?php
/**
* @author      Laurent Jouanneau
* @contributor Julien Issler
* @copyright   2007-2020 Laurent Jouanneau, 2010 Julien Issler
* @link        https://jelix.org
* @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
*/
use \Jelix\Database\Connection;
use \Jelix\Database\Schema\Column;
use \Jelix\Database\Schema\UniqueKey;
use \Jelix\Database\Schema\Reference;
use \Jelix\Database\Schema\Index;
use \Jelix\Database\Schema\Mysql\Table as mysqlTable;
use \Jelix\Database\Schema\Mysql\Schema as mysqlSchema;

class mysqlSchemaTest extends \Jelix\UnitTests\UnitTestCaseDb {

    use assertComplexTrait;

    protected $countryColumns = array();
    protected $cityColumns = array();

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


    public function setUp() : void {
        if (!count($this->countryColumns)) {
            $is64bits = ( PHP_INT_SIZE*8 == 64 );
            $this->countryColumns ['country_id'] = '<object class="\\Jelix\\Database\\Schema\\Column" key="country_id">
        <string property="type" value="int" />
        <string property="name" value="country_id" />
        <boolean property="notNull" value="true"/>
        <boolean property="autoIncrement" value="true"/>
        <string property="default" value="" />
        <boolean property="hasDefault" value="true"/>
        <integer property="length" value="0"/>
        <integer property="precision" value="0"/>
        <integer property="scale" value="0"/>
        <boolean property="sequence" value="false" />
        <boolean property="unsigned" value="false" />
        <null property="minLength"/>
        <null property="maxLength"/>'.
                ($is64bits ?
                    '<integer property="minValue" value="-2147483648"/>' :
                    '<double property="minValue" value="-2147483648"/>').
                '<integer property="maxValue" value="2147483647"/>
    </object>';
            $this->countryColumns ['name'] = '<object class="\\Jelix\\Database\\Schema\\Column" key="name">
        <string property="type" value="varchar" />
        <string property="name" value="name" />
        <boolean property="notNull" value="true"/>
        <boolean property="autoIncrement" value="false"/>
        <string property="default" value="" />
        <boolean property="hasDefault" value="false"/>
        <integer property="length" value="50"/>
        <integer property="precision" value="0"/>
        <integer property="scale" value="0"/>
        <boolean property="sequence" value="false" />
        <boolean property="unsigned" value="false" />
        <integer property="minLength" value="0"/>
        <integer property="maxLength" value="50"/>
        <null property="minValue"/>
        <null property="maxValue"/>
    </object>';


            $this->cityColumns ['city_id'] = '<object class="\\Jelix\\Database\\Schema\\Column" key="city_id">
        <string property="type" value="int" />
        <string property="name" value="city_id" />
        <boolean property="notNull" value="true"/>
        <boolean property="autoIncrement" value="true"/>
        <string property="default" value="" />
        <boolean property="hasDefault" value="true"/>
        <integer property="length" value="0"/>
        <integer property="precision" value="0"/>
        <integer property="scale" value="0"/>
        <boolean property="sequence" value="false" />
        <boolean property="unsigned" value="false" />
        <null property="minLength"/>
        <null property="maxLength"/>'.
                ($is64bits ?
                    '<integer property="minValue" value="-2147483648"/>' :
                    '<double property="minValue" value="-2147483648"/>').
                '<integer property="maxValue" value="2147483647"/>
    </object>';
            $this->cityColumns ['name'] = '<object class="\\Jelix\\Database\\Schema\\Column" key="name">
        <string property="type" value="varchar" />
        <string property="name" value="name" />
        <boolean property="notNull" value="true"/>
        <boolean property="autoIncrement" value="false"/>
        <string property="default" value="" />
        <boolean property="hasDefault" value="false"/>
        <integer property="length" value="50"/>
        <integer property="precision" value="0"/>
        <integer property="scale" value="0"/>
        <boolean property="sequence" value="false" />
        <boolean property="unsigned" value="false" />
        <integer property="minLength" value="0"/>
        <integer property="maxLength" value="50"/>
        <null property="minValue"/>
        <null property="maxValue"/>
    </object>';
            $this->cityColumns ['postcode'] = '<object class="\\Jelix\\Database\\Schema\\Column" key="postcode">
        <string property="type" value="int" />
        <string property="name" value="postcode" />
        <boolean property="notNull" value="false"/>
        <boolean property="autoIncrement" value="false"/>
        <string property="default" value="0" />
        <boolean property="hasDefault" value="true"/>
        <integer property="length" value="0"/>
        <integer property="precision" value="0"/>
        <integer property="scale" value="0"/>
        <boolean property="sequence" value="false" />
        <boolean property="unsigned" value="false" />
        <null property="minLength"/>
        <null property="maxLength"/>'.
                ($is64bits ?
                    '<integer property="minValue" value="-2147483648"/>' :
                    '<double property="minValue" value="-2147483648"/>').
                '<integer property="maxValue" value="2147483647"/>
    </object>';
            $this->cityColumns ['latitude'] = '<object class="\\Jelix\\Database\\Schema\\Column" key="latitude">
        <string property="type" value="varchar" />
        <string property="name" value="latitude" />
        <boolean property="notNull" value="false"/>
        <boolean property="autoIncrement" value="false"/>
        <null property="default"/>
        <boolean property="hasDefault" value="true"/>
        <integer property="length" value="20"/>
        <integer property="precision" value="0"/>
        <integer property="scale" value="0"/>
        <boolean property="sequence" value="false" />
        <boolean property="unsigned" value="false" />
        <integer property="minLength" value="0"/>
        <integer property="maxLength" value="20"/>
        <null property="minValue"/>
        <null property="maxValue"/>
    </object>';
            $this->cityColumns ['longitude'] = '<object class="\\Jelix\\Database\\Schema\\Column" key="longitude">
        <string property="type" value="varchar" />
        <string property="name" value="longitude" />
        <boolean property="notNull" value="false"/>
        <boolean property="autoIncrement" value="false"/>
        <null property="default"/>
        <boolean property="hasDefault" value="true"/>
        <integer property="length" value="20"/>
        <integer property="precision" value="0"/>
        <integer property="scale" value="0"/>
        <boolean property="sequence" value="false" />
        <boolean property="unsigned" value="false" />
        <integer property="minLength" value="0"/>
        <integer property="maxLength" value="20"/>
        <null property="minValue"/>
        <null property="maxValue"/>
    </object>';

            $this->cityColumns ['description'] = '<object class="\\Jelix\\Database\\Schema\\Column" key="description">
        <string property="type" value="text" />
        <string property="name" value="description" />
        <boolean property="notNull" value="false"/>
        <boolean property="autoIncrement" value="false"/>
        <null property="default"/>
        <boolean property="hasDefault" value="true"/>
        <integer property="length" value="0"/>
        <integer property="precision" value="0"/>
        <integer property="scale" value="0"/>
        <boolean property="sequence" value="false" />
        <boolean property="unsigned" value="false" />
        <integer property="minLength" value="0"/>
        <integer property="maxLength" value="65535"/>
        <null property="minValue"/>
        <null property="maxValue"/>
    </object>';

            $this->cityColumns ['name2'] = '<object class="\\Jelix\\Database\\Schema\\Column" key="name">
        <string property="type" value="varchar" />
        <string property="name" value="name" />
        <boolean property="notNull" value="true"/>
        <boolean property="autoIncrement" value="false"/>
        <string property="default" value="" />
        <boolean property="hasDefault" value="false"/>
        <integer property="length" value="150"/>
        <integer property="precision" value="0"/>
        <integer property="scale" value="0"/>
        <boolean property="sequence" value="false" />
        <boolean property="unsigned" value="false" />
        <integer property="minLength" value="0"/>
        <integer property="maxLength" value="150"/>
        <null property="minValue"/>
        <null property="maxValue"/>
    </object>';

            $this->cityColumns ['superdesc'] = '<object class="\\Jelix\\Database\\Schema\\Column" key="superdesc">
        <string property="type" value="text" />
        <string property="name" value="superdesc" />
        <boolean property="notNull" value="false"/>
        <boolean property="autoIncrement" value="false"/>
        <null property="default"/>
        <boolean property="hasDefault" value="true"/>
        <integer property="length" value="0"/>
        <integer property="precision" value="0"/>
        <integer property="scale" value="0"/>
        <boolean property="sequence" value="false" />
        <boolean property="unsigned" value="false" />
        <integer property="minLength" value="0"/>
        <integer property="maxLength" value="65535"/>
        <null property="minValue"/>
        <null property="maxValue"/>
    </object>';
        }
    }

    protected $countryNameKey = '<object class="\\Jelix\\Database\\Schema\\UniqueKey" key="name">
                    <string property="name" value="name" />
                    <array property="columns">
                        <string value="name"/>
                    </array>
                </object>';
    protected $countryNameKey2 = '<object class="\\Jelix\\Database\\Schema\\UniqueKey" key="country_name_key">
                    <string property="name" value="country_name_key" />
                    <array property="columns">
                        <string value="name"/>
                    </array>
                </object>';
    protected $city_name_idx = '<object class="\\Jelix\\Database\\Schema\\Index" key="city_name_idx">
                    <string property="name" value="city_name_idx" />
                    <array property="columns">
                        <string value="name"/>
                    </array>
                </object>';
    protected $city_name_postcode_idx = '<object class="\\Jelix\\Database\\Schema\\UniqueKey" key="city_name_postcode_idx">
                    <string property="name" value="city_name_postcode_idx" />
                    <array property="columns">
                        <string value="name"/>
                        <string value="postcode"/>
                    </array>
                </object>';
    protected $city_name_postcode_idx2 = '<object class="\\Jelix\\Database\\Schema\\Index" key="city_name_postcode_idx2">
                    <string property="name" value="city_name_postcode_idx2" />
                    <array property="columns">
                        <string value="name"/>
                        <string value="postcode"/>
                    </array>
                </object>';
    protected $city_coordinates_uniq = '<object class="\\Jelix\\Database\\Schema\\UniqueKey" key="coordinates">
                    <string property="name" value="coordinates" />
                    <array property="columns">
                        <string value="latitude"/>
                        <string value="longitude"/>
                    </array>
                </object>';
    protected $city_country_id_fkey = '<object class="\\Jelix\\Database\\Schema\\Reference" key="city_ibfk_1">
                    <string property="name" value="city_ibfk_1" />
                    <array property="columns">
                        <string value="country_id"/>
                    </array>
                    <string property="fTable" value="country" />
                    <array property="fColumns">
                        <string value="country_id"/>
                    </array>
                </object>';
    protected $city_country_id_fkey2 = '<object class="\\Jelix\\Database\\Schema\\Reference" key="bigcity_ibfk_1">
                    <string property="name" value="bigcity_ibfk_1" />
                    <array property="columns">
                        <string value="country_id"/>
                    </array>
                    <string property="fTable" value="country" />
                    <array property="fColumns">
                        <string value="country_id"/>
                    </array>
                </object>';
    protected $city_country_id_fkey3 = '<object class="\\Jelix\\Database\\Schema\\Reference" key="city_country_id_fkey">
                    <string property="name" value="city_country_id_fkey" />
                    <array property="columns">
                        <string value="country_id"/>
                    </array>
                    <string property="fTable" value="country" />
                    <array property="fColumns">
                        <string value="country_id"/>
                    </array>
                </object>';

    function testTableList() {
        $db = $this->getConnection();
        $db->exec('DROP TABLE IF EXISTS test_prod');
        $db->exec('DROP TABLE IF EXISTS city');
        $db->exec('DROP TABLE IF EXISTS bigcity');
        $db->exec('DROP TABLE IF EXISTS country');
        $schema = $db->schema();

        $goodList = array('labels_test', 'product_test', 'products');

        $list = $schema->getTables();
        $tables = array();
        foreach($list as $table) {
            $tables[] = $table->getName();
        }

        sort($goodList);
        sort($tables);
        $this->assertEquals($goodList, $tables);
    }

    function testTable() {
        $db = $this->getConnection();
        $schema = $db->schema();

        $table = $schema->getTable('product_test');

        $this->assertNotNull($table);

        $this->assertEquals('product_test', $table->getName());

        $pk = $table->getPrimaryKey();
        $this->assertEquals(array('id'), $pk->columns);

        $is64bits = ( PHP_INT_SIZE*8 == 64 );

        $verif='<array>
    <object class="\\Jelix\\Database\\Schema\\Column" key="id">
        <string property="type" value="int" />
        <string property="name" value="id" />
        <boolean property="notNull" value="true"/>
        <boolean property="autoIncrement" value="true"/>
        <string property="default" value="" />
        <boolean property="hasDefault" value="true"/>
        <integer property="length" value="0"/>
        <integer property="precision" value="0"/>
        <integer property="scale" value="0"/>
        <boolean property="sequence" value="false" />
        <boolean property="unsigned" value="false" />
        <null property="minLength"/>
        <null property="maxLength"/>'.
        ($is64bits ? 
         '<integer property="minValue" value="-2147483648"/>' :
         '<double property="minValue" value="-2147483648"/>').
        '<integer property="maxValue" value="2147483647"/>
    </object>
    <object class="\\Jelix\\Database\\Schema\\Column" key="name">
        <string property="type" value="varchar" />
        <string property="name" value="name" />
        <boolean property="notNull" value="true"/>
        <boolean property="autoIncrement" value="false"/>
        <string property="default" value="" />
        <boolean property="hasDefault" value="false"/>
        <integer property="length" value="150"/>
        <integer property="precision" value="0"/>
        <integer property="scale" value="0"/>
        <boolean property="sequence" value="false" />
        <boolean property="unsigned" value="false" />
        <integer property="minLength" value="0"/>
        <integer property="maxLength" value="150"/>
        <null property="minValue"/>
        <null property="maxValue"/>
    </object>
    <object class="\\Jelix\\Database\\Schema\\Column" key="price">
        <string property="type" value="float" />
        <string property="name" value="price" />
        <boolean property="notNull" value="true"/>
        <boolean property="autoIncrement" value="false"/>
        <string property="default" value="" />
        <boolean property="hasDefault" value="false"/>
        <integer property="length" value="0"/>
        <integer property="precision" value="0"/>
        <integer property="scale" value="0"/>
        <boolean property="sequence" value="false" />
        <boolean property="unsigned" value="false" />
        <null property="minLength"/>
        <null property="maxLength"/>
        <null property="minValue"/>
        <null property="maxValue"/>
    </object>
    <object class="\\Jelix\\Database\\Schema\\Column" key="create_date">
        <string property="type" value="datetime" />
        <string property="name" value="create_date" />
        <boolean property="notNull" value="false"/>
        <boolean property="autoIncrement" value="false"/>
        <null property="default" />
        <boolean property="hasDefault" value="true"/>
        <integer property="length" value="0"/>
        <integer property="precision" value="0"/>
        <integer property="scale" value="0"/>
        <boolean property="sequence" value="false" />
        <boolean property="unsigned" value="false" />
        <integer property="minLength" value="19"/>
        <integer property="maxLength" value="19"/>
        <null property="minValue"/>
        <null property="maxValue"/>
    </object>
    <object class="\\Jelix\\Database\\Schema\\Column" key="promo">
        <string property="type" value="boolean" />
        <string property="name" value="promo" />
        <boolean property="notNull" value="true"/>
        <boolean property="autoIncrement" value="false"/>
        <boolean property="default" value="false"/>
        <boolean property="hasDefault" value="true"/>
        <integer property="length" value="0"/>
        <integer property="precision" value="0"/>
        <integer property="scale" value="0"/>
        <boolean property="sequence" value="false" />
        <boolean property="unsigned" value="false" />
        <null property="minLength"/>
        <null property="maxLength"/>
        <integer property="minValue" value="0"/>
        <integer property="maxValue" value="1"/>
    </object>
</array>';

        $this->assertComplexIdenticalStr($table->getColumns(), $verif);

        $verif = '<object class="\\Jelix\\Database\\Schema\\PrimaryKey">
                <string property="name" value="PRIMARY" />
                <array property="columns">
                    <string value="id"/>
                </array>
         </object>';
        $this->assertComplexIdenticalStr($table->getPrimaryKey(), $verif);
        $this->assertEquals(array(), $table->getIndexes());
        $this->assertEquals(array(), $table->getUniqueKeys());
        $this->assertEquals(array(), $table->getReferences());
        $this->assertTrue($table->getColumn('id')->isAutoincrementedColumn());
        $this->assertFalse($table->getColumn('name')->isAutoincrementedColumn());
    }

    function testCreateTable() {

        $db = $this->getConnection();
        $schema = $db->schema();
        $db->exec('DROP TABLE IF EXISTS test_prod');
        $db->exec('DROP TABLE IF EXISTS city');
        $db->exec('DROP TABLE IF EXISTS bigcity');
        $db->exec('DROP TABLE IF EXISTS country');

        $columns = array();
        $col = new Column('id', 'integer', 0, false, null, true);
        $col->autoIncrement = true;
        $columns[] = $col;
        $columns[] = new Column('name','string',50);
        $columns[] = new Column('price','double', 0, true, null, false);
        $columns[] = new Column('promo','boolean',0, true, true);
        $columns[] = new Column('product_id','int', 0, false, null, true);

        $schema->createTable('test_prod', $columns, 'id', array('engine'=>'InnoDB'));

        $rs = $db->query('SHOW COLUMNS from test_prod');
        while($l = $rs->fetch()) {
            $list[$l->Field] = $l;
        }

        $mysqlVersion = $db->getAttribute(\PDO::ATTR_SERVER_VERSION);
        if ($mysqlVersion[0] == '5') {
            $intType = 'int(11)';
        }
        else {
            $intType = 'int';
        }

        $obj = '<object>
        <string property="Type" value="'.$intType.'" />
        <string property="Field" value="id" />
        <string property="Null" value="NO" />
        <string property="Extra"  value="auto_increment" />
        <null property="Default" />
        </object>';

        $this->assertComplexIdenticalStr($list['id'], $obj);

        $obj = '<object>
        <string property="Type" value="varchar(50)" />
        <string property="Field" value="name" />
        <string property="Null" value="YES" />
        <string property="Extra"  value="" />
        <null property="Default" />
        </object>';

        $this->assertComplexIdenticalStr($list['name'], $obj);

        $obj = '<object>
        <string property="Type" value="double" />
        <string property="Field" value="price" />
        <string property="Null" value="YES" />
        <string property="Extra"  value="" />
        <null property="Default" />
        </object>';

        $this->assertComplexIdenticalStr($list['price'], $obj);

        $obj = '<object>
        <string property="Type" value="tinyint(1)" />
        <string property="Field" value="promo" />
        <string property="Null" value="YES" />
        <string property="Extra"  value="" />
        <string property="Default" value="1"/>
        </object>';

        $this->assertComplexIdenticalStr($list['promo'], $obj);

        $obj = '<object>
        <string property="Type" value="'.$intType.'" />
        <string property="Field" value="product_id" />
        <string property="Null" value="NO" />
        <string property="Extra"  value="" />
        <null property="Default" />
        </object>';

        $this->assertComplexIdenticalStr($list['product_id'], $obj);


        $table = new mysqlTable('test_prod', $schema);

        $this->assertEquals('test_prod', $table->getName());

        $pk = $table->getPrimaryKey();
        $this->assertEquals(array('id'), $pk->columns);

        $is64bits = ( PHP_INT_SIZE*8 == 64 );

        $verif='<array>
    <object class="\\Jelix\\Database\\Schema\\Column" key="id">
        <string property="type" value="int" />
        <string property="name" value="id" />
        <boolean property="notNull" value="true"/>
        <boolean property="autoIncrement" value="true"/>
        <string property="default" value="" />
        <boolean property="hasDefault" value="true"/>
        <integer property="length" value="0"/>
        <integer property="precision" value="0"/>
        <integer property="scale" value="0"/>
        <boolean property="sequence" value="false" />
        <boolean property="unsigned" value="false" />
        <null property="minLength"/>
        <null property="maxLength"/>'.
            ($is64bits ?
                '<integer property="minValue" value="-2147483648"/>' :
                '<double property="minValue" value="-2147483648"/>').
            '<integer property="maxValue" value="2147483647"/>
    </object>
    <object class="\\Jelix\\Database\\Schema\\Column" key="name">
        <string property="type" value="varchar" />
        <string property="name" value="name" />
        <boolean property="notNull" value="false"/>
        <boolean property="autoIncrement" value="false"/>
        <null property="default" />
        <boolean property="hasDefault" value="true"/>
        <integer property="length" value="50"/>
        <integer property="precision" value="0"/>
        <integer property="scale" value="0"/>
        <boolean property="sequence" value="false" />
        <boolean property="unsigned" value="false" />
        <integer property="minLength" value="0"/>
        <integer property="maxLength" value="50"/>
        <null property="minValue"/>
        <null property="maxValue"/>
    </object>
    <object class="\\Jelix\\Database\\Schema\\Column" key="price">
        <string property="type" value="double" />
        <string property="name" value="price" />
        <boolean property="notNull" value="false"/>
        <boolean property="autoIncrement" value="false"/>
        <null property="default" />
        <boolean property="hasDefault" value="true"/>
        <integer property="length" value="0"/>
        <integer property="precision" value="0"/>
        <integer property="scale" value="0"/>
        <boolean property="sequence" value="false" />
        <boolean property="unsigned" value="false" />
        <null property="minLength"/>
        <null property="maxLength"/>
        <null property="minValue"/>
        <null property="maxValue"/>
    </object>
    <object class="\\Jelix\\Database\\Schema\\Column" key="product_id">
        <string property="type" value="int" />
        <string property="name" value="product_id" />
        <boolean property="notNull" value="true"/>
        <boolean property="autoIncrement" value="false"/>
        <string property="default" value="" />
        <boolean property="hasDefault" value="false"/>
        <integer property="length" value="0"/>
        <integer property="precision" value="0"/>
        <integer property="scale" value="0"/>
        <boolean property="sequence" value="false" />
        <boolean property="unsigned" value="false" />
        <null property="minLength"/>
        <null property="maxLength"/>'.
            ($is64bits ?
                '<integer property="minValue" value="-2147483648"/>' :
                '<double property="minValue" value="-2147483648"/>').
            '<integer property="maxValue" value="2147483647"/>
    </object>
    <object class="\\Jelix\\Database\\Schema\\Column" key="promo">
        <string property="type" value="boolean" />
        <string property="name" value="promo" />
        <boolean property="notNull" value="false"/>
        <boolean property="autoIncrement" value="false"/>
        <boolean property="default" value="true"/>
        <boolean property="hasDefault" value="true"/>
        <integer property="length" value="0"/>
        <integer property="precision" value="0"/>
        <integer property="scale" value="0"/>
        <boolean property="sequence" value="false" />
        <boolean property="unsigned" value="false" />
        <null property="minLength"/>
        <null property="maxLength"/>
        <integer property="minValue" value="0"/>
        <integer property="maxValue" value="1"/>
    </object>
</array>';

        $this->assertComplexIdenticalStr($table->getColumns(), $verif);
    }

    /**
     * @depends testCreateTable
     */
    function testReferences() {
        $db = $this->getConnection();
        $schema = $db->schema();

        $table = $schema->getTable('test_prod');

        $reference = new Reference();
        $reference->name = "product_id_fkey";
        $reference->columns = array('product_id');
        $reference->fTable = 'product_test';
        $reference->fColumns = array('id');
        $table->addReference($reference);

        $table = new mysqlTable('test_prod', $schema);
        $references = $table->getReferences();
        $this->assertTrue(isset($references["product_id_fkey"]));
        $ref = $references["product_id_fkey"];
        $this->assertEquals("product_id_fkey", $ref->name);
        $this->assertEquals(array('product_id'), $ref->columns);
        $this->assertEquals('product_test', $ref->fTable);
        $this->assertEquals(array('id'), $ref->fColumns);
        $this->assertEquals('', $ref->onUpdate);
        $this->assertEquals('', $ref->onDelete);
    }

    /**
     * @depends testReferences
     */
    function testDropTableOld() {

        $db = $this->getConnection();
        $schema = $db->schema();

        $table = $schema->getTable('test_prod');
        $schema->dropTable($table);

        $dbname = $db->profile['database'];
        $rs = $db->query ('SHOW TABLES FROM '.$db->encloseName($dbname));

        $col_name = 'Tables_in_'.$dbname;
        $found = false;
        while ($line = $rs->fetch ()){
            if ($line->$col_name == 'test_prod')
                $found=true;
        }
        $this->assertFalse($found);
    }


    public function testGetTablesAndConstraintsIndexes() {
        $db = $this->getConnection();
        $db->exec('DROP TABLE IF EXISTS city');
        $db->exec('DROP TABLE IF EXISTS bigcity');
        $db->exec('DROP TABLE IF EXISTS country');

        $db->exec('CREATE TABLE country (
    country_id INTEGER AUTO_INCREMENT PRIMARY KEY,
    name varchar(50) not null,
    UNIQUE(name)
)');
        $db->exec('CREATE TABLE city (
    city_id INTEGER AUTO_INCREMENT PRIMARY KEY,
    country_id integer NOT NULL,
    name  varchar(50) not null,
    postcode integer DEFAULT 0,
    latitude varchar(20),
    longitude varchar(20),
    CONSTRAINT coordinates UNIQUE(latitude, longitude),
    FOREIGN KEY (country_id) REFERENCES country (country_id))');

        $db->exec('CREATE INDEX city_name_idx ON city (name)');
        $db->exec('CREATE UNIQUE INDEX city_name_postcode_idx ON city (name, postcode)');

        $schema = new mysqlSchema($db);
        $country = $schema->getTable('country');
        $city = $schema->getTable('city');
        $this->assertEquals('country', $country->getName());
        $this->assertEquals('city', $city->getName());


        $pk = $country->getPrimaryKey();
        $this->assertEquals(array('country_id'), $pk->columns);
        $pk = $city->getPrimaryKey();
        $this->assertEquals(array('city_id'), $pk->columns);

        $this->assertTrue($country->getColumn('country_id')->isAutoincrementedColumn());
        $this->assertFalse($country->getColumn('name')->isAutoincrementedColumn());
        $this->assertTrue($city->getColumn('city_id')->isAutoincrementedColumn());
        $this->assertFalse($city->getColumn('country_id')->isAutoincrementedColumn());
        $this->assertFalse($city->getColumn('name')->isAutoincrementedColumn());

        $columns='<array>'.$this->countryColumns ['country_id'].
            $this->countryColumns ['name']. '</array>';
        $this->assertComplexIdenticalStr($country->getColumns(), $columns);

        $columns='<array>'.$this->cityColumns ['city_id'].
            $this->cityColumns ['name'].
            $this->cityColumns ['postcode'].
            $this->cityColumns ['latitude'].
            $this->cityColumns ['longitude'].'</array>';
        $this->assertComplexIdenticalStr($city->getColumns(), $columns);

        $this->assertEquals(array(), $country->getIndexes());
        $this->assertComplexIdenticalStr($country->getUniqueKeys(),
            '<array>'.$this->countryNameKey.'</array>'
        );
        $this->assertEquals(array(), $country->getReferences());

        $this->assertEquals(1, count($city->getIndexes()));
        $this->assertEquals(2, count($city->getUniqueKeys()));
        $this->assertEquals(1, count($city->getReferences()));
        $this->assertComplexIdenticalStr($city->getIndexes(),
            '<array>'.$this->city_name_idx.'</array>'
        );
        $this->assertComplexIdenticalStr($city->getUniqueKeys(),
            '<array>'.$this->city_coordinates_uniq.$this->city_name_postcode_idx.'</array>'
        );
        $this->assertComplexIdenticalStr($city->getReferences(),
            '<array>'.$this->city_country_id_fkey.'</array>'
        );

    }

    /**
     * @depends testGetTablesAndConstraintsIndexes
     */
    public function testRenameTable() {
        $db = $this->getConnection();
        $schema = new mysqlSchema($db);

        $schema->renameTable('city', 'bigcity');

        $list = $schema->getTables();
        $tables = array();
        foreach($list as $table) {
            $tables[] = $table->getName();
        }

        $this->assertTrue(in_array('bigcity', $tables));
        $this->assertFalse(in_array('city', $tables));

        $city = $schema->getTable('bigcity');

        $pk = $city->getPrimaryKey();
        $this->assertEquals(array('city_id'), $pk->columns);

        $columns='<array>'.$this->cityColumns ['city_id'].
            $this->cityColumns ['name'].
            $this->cityColumns ['postcode'].
            $this->cityColumns ['latitude'].
            $this->cityColumns ['longitude'].'</array>';
        $this->assertComplexIdenticalStr($city->getColumns(), $columns);

        $this->assertComplexIdenticalStr($city->getIndexes(),
            '<array>'.$this->city_name_idx.'</array>'
        );
        $this->assertComplexIdenticalStr($city->getUniqueKeys(),
            '<array>'.$this->city_coordinates_uniq.$this->city_name_postcode_idx.'</array>'
        );
        $this->assertComplexIdenticalStr($city->getReferences(),
            '<array>'.$this->city_country_id_fkey2.'</array>'
        );
    }

    /**
     * @depends testRenameTable
     */
    public function testAddColumn() {
        $db = $this->getConnection();
        $schema = new mysqlSchema($db);
        $city = $schema->getTable('bigcity');
        $col = new Column('description', 'text');
        $city->addColumn($col);

        $schema = new mysqlSchema($db);
        $city = $schema->getTable('bigcity');

        $pk = $city->getPrimaryKey();
        $this->assertEquals(array('city_id'), $pk->columns);

        $columns='<array>'.$this->cityColumns ['city_id'].
            $this->cityColumns ['name'].
            $this->cityColumns ['postcode'].
            $this->cityColumns ['latitude'].
            $this->cityColumns ['longitude'].
            $this->cityColumns ['description'].
            '</array>';
        $this->assertComplexIdenticalStr($city->getColumns(), $columns);

        $this->assertComplexIdenticalStr($city->getIndexes(),
            '<array>'.$this->city_name_idx.'</array>'
        );
        $this->assertComplexIdenticalStr($city->getUniqueKeys(),
            '<array>'.$this->city_coordinates_uniq.$this->city_name_postcode_idx.'</array>'
        );
        $this->assertComplexIdenticalStr($city->getReferences(),
            '<array>'.$this->city_country_id_fkey2.'</array>'
        );
    }

    /**
     * @depends testAddColumn
     */
    public function testAlterColumn() {
        $db = $this->getConnection();
        $schema = new mysqlSchema($db);
        $city = $schema->getTable('bigcity');

        $name = $city->getColumn('name', true);
        $name->length = 150;

        $desc = $city->getColumn('description', true);
        $desc->name = 'superdesc';

        $city->alterColumn($name);
        $city->alterColumn($desc, 'description');

        $schema = new mysqlSchema($db);
        $city = $schema->getTable('bigcity');
        $pk = $city->getPrimaryKey();
        $this->assertEquals(array('city_id'), $pk->columns);

        $this->assertNull($city->getColumn('description'));

        $columns='<array>'.$this->cityColumns ['city_id'].
            $this->cityColumns ['name2'].
            $this->cityColumns ['postcode'].
            $this->cityColumns ['latitude'].
            $this->cityColumns ['longitude'].
            $this->cityColumns ['superdesc'].
            '</array>';
        $this->assertComplexIdenticalStr($city->getColumns(), $columns);

        $this->assertComplexIdenticalStr($city->getIndexes(),
            '<array>'.$this->city_name_idx.'</array>'
        );
        $this->assertComplexIdenticalStr($city->getUniqueKeys(),
            '<array>'.$this->city_coordinates_uniq.$this->city_name_postcode_idx.'</array>'
        );
        $this->assertComplexIdenticalStr($city->getReferences(),
            '<array>'.$this->city_country_id_fkey2.'</array>'
        );
    }

    /**
     * @depends testAlterColumn
     */
    public function testDropColumn() {
        $db = $this->getConnection();
        $schema = new mysqlSchema($db);
        $city = $schema->getTable('bigcity');
        $city->dropColumn('superdesc');

        $schema = new mysqlSchema($db);
        $city = $schema->getTable('bigcity');
        $pk = $city->getPrimaryKey();
        $this->assertEquals(array('city_id'), $pk->columns);

        $this->assertNull($city->getColumn('superdesc'));
        $columns='<array>'.$this->cityColumns ['city_id'].
            $this->cityColumns ['name2'].
            $this->cityColumns ['postcode'].
            $this->cityColumns ['latitude'].
            $this->cityColumns ['longitude'].
            '</array>';
        $this->assertComplexIdenticalStr($city->getColumns(), $columns);

        $this->assertComplexIdenticalStr($city->getIndexes(),
            '<array>'.$this->city_name_idx.'</array>'
        );
        $this->assertComplexIdenticalStr($city->getUniqueKeys(),
            '<array>'.$this->city_coordinates_uniq.$this->city_name_postcode_idx.'</array>'
        );
        $this->assertComplexIdenticalStr($city->getReferences(),
            '<array>'.$this->city_country_id_fkey2.'</array>'
        );
    }

    /**
     * @depends testDropColumn
     */
    public function testDropTable() {
        $db = $this->getConnection();
        $schema = new mysqlSchema($db);

        $schema->dropTable('bigcity');
        $schema->dropTable($schema->getTable('country'));

        $list = $schema->getTables();
        $tables = array();
        foreach($list as $table) {
            $tables[] = $table->getName();
        }

        $this->assertFalse(in_array('bigcity', $tables));
        $this->assertFalse(in_array('country', $tables));
        $this->assertNull($schema->getTable('bigcity'));
        $this->assertNull($schema->getTable('country'));
    }


    /**
     * @depends testDropTable
     */
    public function testCreateTableAndAddDropPrimaryKey() {
        $db = $this->getConnection();
        $schema = new mysqlSchema($db);

        $columns = array();
        $id = new Column('country_id', 'INTEGER');
        // don't set autoincrement as it is not allowed on non primary/unique key
        // and then it will fail when we will remove the PK constraint
        //$id->autoIncrement = true;
        $columns[] = $id;
        $columns[] = new Column('name', 'varchar', 50, false, null, true);

        $country = $schema->createTable('country', $columns, 'country_id');

        $pk = $country->getPrimaryKey();
        $this->assertEquals(array('country_id'), $pk->columns);

        $country->dropPrimaryKey();
        $this->assertFalse($country->getPrimaryKey());

        $schema = new mysqlSchema($db);
        $country = $schema->getTable('country');
        $this->assertFalse($country->getPrimaryKey());

        $schema = new mysqlSchema($db);
        $country = $schema->getTable('country');
        $country->setPrimaryKey($pk);
        $pk = $country->getPrimaryKey();
        $this->assertEquals(array('country_id'), $pk->columns);

        $schema = new mysqlSchema($db);
        $country = $schema->getTable('country');
        $pk = $country->getPrimaryKey();
        $this->assertEquals(array('country_id'), $pk->columns);
    }

    /**
     * @depends testCreateTableAndAddDropPrimaryKey
     */
    public function testCreateTables() {
        $db = $this->getConnection();
        $db->exec('DROP TABLE IF EXISTS city');
        $db->exec('DROP TABLE IF EXISTS bigcity');
        $db->exec('DROP TABLE IF EXISTS country');
        $schema = new mysqlSchema($db);

        $columns = array();
        $id = new Column('country_id', 'INTEGER');
        $id->autoIncrement = true;
        $columns[] = $id;
        $columns[] = new Column('name', 'varchar', 50, false, null, true);
        $country = $schema->createTable('country', $columns, 'country_id');

        $columns = array();
        $id = new Column('city_id', 'INTEGER');
        $id->autoIncrement = true;
        $columns[] = $id;
        $columns[] = new Column('country_id', 'integer', 0, false, null, true);
        $columns[] = new Column('name', 'varchar', 50, false, null, true);
        $columns[] = new Column('postcode', 'integer', 0, true, 0);
        $columns[] = new Column('latitude', 'varchar', 20);
        $columns[] = new Column('longitude', 'varchar', 20);
        $city = $schema->createTable('city', $columns, 'city_id');


        $pk = $country->getPrimaryKey();
        $this->assertEquals(array('country_id'), $pk->columns);
        $pk = $city->getPrimaryKey();
        $this->assertEquals(array('city_id'), $pk->columns);

        $columns='<array>'.$this->countryColumns ['country_id'].
            $this->countryColumns ['name']. '</array>';
        $this->assertComplexIdenticalStr($country->getColumns(), $columns);

        $columns='<array>'.$this->cityColumns ['city_id'].
            $this->cityColumns ['name'].
            $this->cityColumns ['postcode'].
            $this->cityColumns ['latitude'].
            $this->cityColumns ['longitude'].'</array>';
        $this->assertComplexIdenticalStr($city->getColumns(), $columns);

        $this->assertEquals(array(), $country->getIndexes());
        $this->assertEquals(array(), $country->getUniqueKeys());
        $this->assertEquals(array(), $country->getReferences());


        $this->assertEquals(array(), $city->getIndexes());
        $this->assertEquals(array(), $city->getUniqueKeys());
        $this->assertEquals(array(), $city->getReferences());
    }

    /**
     * @depends testCreateTables
     */
    public function testAddIndex() {
        $db = $this->getConnection();
        $schema = new mysqlSchema($db);
        $city = $schema->getTable('city');
        $index = new Index('city_name_idx', '', array('name'));
        $city->addIndex($index);
        $index = new Index('city_name_postcode_idx2', '', array('name', 'postcode'));
        $index->isUnique = true;
        $city->addIndex($index);

        $this->assertComplexIdenticalStr($city->getIndexes(),
            '<array>'.$this->city_name_idx.
            $this->city_name_postcode_idx2.'</array>'
        );

        $schema = new mysqlSchema($db); // reload all
        $city = $schema->getTable('city');
        $this->assertComplexIdenticalStr($city->getIndexes(),
            '<array>'.$this->city_name_idx.
            $this->city_name_postcode_idx2.'</array>'
        );
    }

    /**
     * @depends testAddIndex
     */
    public function testDropIndex() {
        $db = $this->getConnection();
        $schema = new mysqlSchema($db); // reload all
        $city = $schema->getTable('city');
        $this->assertComplexIdenticalStr($city->getIndexes(),
            '<array>'.$this->city_name_idx.
            $this->city_name_postcode_idx2.'</array>'
        );

        $city->dropIndex('city_name_idx');
        $this->assertNull($city->getIndex('city_name_idx'));
        $this->assertComplexIdenticalStr($city->getIndexes(),
            '<array>'.$this->city_name_postcode_idx2.'</array>'
        );

        $schema = new mysqlSchema($db); // reload all
        $city = $schema->getTable('city');
        $this->assertNull($city->getIndex('city_name_idx'));
        $this->assertComplexIdenticalStr($city->getIndexes(),
            '<array>'.$this->city_name_postcode_idx2.'</array>'
        );
    }

    /**
     * @depends testDropIndex
     */
    public function testAddUniqueKey() {
        $db = $this->getConnection();
        $schema = new mysqlSchema($db); // reload all
        $country = $schema->getTable('country');

        $key = new UniqueKey('country_name_key', array('name'));
        $country->addUniqueKey($key);
        $this->assertComplexIdenticalStr($country->getUniqueKeys(),
            '<array>'.$this->countryNameKey2.'</array>'
        );

        $schema = new mysqlSchema($db); // reload all
        $country = $schema->getTable('country');
        $this->assertComplexIdenticalStr($country->getUniqueKeys(),
            '<array>'.$this->countryNameKey2.'</array>'
        );
    }

    /**
     * @depends testAddUniqueKey
     */
    public function testDropUniqueKey() {
        $db = $this->getConnection();
        $schema = new mysqlSchema($db); // reload all
        $country = $schema->getTable('country');
        $country->dropUniqueKey('country_name_key');

        $this->assertEquals(array(), $country->getUniqueKeys());
        $schema = new mysqlSchema($db); // reload all
        $country = $schema->getTable('country');
        $this->assertEquals(array(), $country->getUniqueKeys());
    }


    /**
     * @depends testDropUniqueKey
     */
    public function testAddReference() {
        $db = $this->getConnection();
        $schema = new mysqlSchema($db); // reload all
        $city = $schema->getTable('city');

        $key = new Reference('city_country_id_fkey', array('country_id'),
            'country', array('country_id'));
        $city->addReference($key);
        $this->assertComplexIdenticalStr($city->getReferences(),
            '<array>'.$this->city_country_id_fkey3.'</array>'
        );

        $schema = new mysqlSchema($db); // reload all
        $city = $schema->getTable('city');
        $this->assertComplexIdenticalStr($city->getReferences(),
            '<array>'.$this->city_country_id_fkey3.'</array>'
        );
    }

    /**
     * @depends testAddReference
     */
    public function testDropReference() {
        $db = $this->getConnection();
        $schema = new mysqlSchema($db); // reload all
        $city = $schema->getTable('city');
        $city->dropReference('city_country_id_fkey');

        $this->assertEquals(array(), $city->getReferences());
        $schema = new mysqlSchema($db); // reload all
        $city = $schema->getTable('city');
        $this->assertEquals(array(), $city->getReferences());
    }



}

