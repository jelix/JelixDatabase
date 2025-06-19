<?php
/**
* @author      Laurent Jouanneau
* @contributor Julien Issler
* @copyright   2007-2025 Laurent Jouanneau, 2010 Julien Issler
* @link        https://jelix.org
* @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
*/
use \Jelix\Database\Connection;
use \Jelix\Database\Schema\Column;
use \Jelix\Database\Schema\UniqueKey;
use \Jelix\Database\Schema\Reference;
use \Jelix\Database\Schema\Index;
use \Jelix\Database\Schema\Postgresql\Table as pgsqlTable;
use \Jelix\Database\Schema\Postgresql\TableName as pgsqlTableName;
use \Jelix\Database\Schema\Postgresql\Schema as pgsqlSchema;


class pgsqlSchemaTest extends \Jelix\UnitTests\UnitTestCaseDb
{

    use assertComplexTrait;

    protected static $connectionPgsql = null;

    protected function getConnection()
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

    protected $countryColumns = array();
    protected $cityColumns = array();

    public function setUp() : void {
        if (!count($this->countryColumns)) {
            $is64bits = ( PHP_INT_SIZE*8 == 64 );
            $this->countryColumns ['country_id'] = '<object class="\\Jelix\\Database\\Schema\\Column" key="country_id">
        <string property="type" value="integer" />
        <string property="name" value="country_id" />
        <boolean property="notNull" value="true"/>
        <boolean property="autoIncrement" value="true"/>
        <string property="default" value="" />
        <boolean property="hasDefault" value="true"/>
        <integer property="length" value="0"/>
        <integer property="precision" value="0"/>
        <integer property="scale" value="0"/>
        <string property="sequence" value="country_country_id_seq" />
        <boolean property="unsigned" value="false" />
        <null property="minLength"/>
        <null property="maxLength"/>'.
                ($is64bits ?
                    '<integer property="minValue" value="-2147483648"/>' :
                    '<double property="minValue" value="-2147483648"/>').
                '<integer property="maxValue" value="2147483647"/>
    </object>';
            $this->countryColumns ['name'] = '<object class="\\Jelix\\Database\\Schema\\Column" key="name">
        <string property="type" value="character" />
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
        <string property="type" value="integer" />
        <string property="name" value="city_id" />
        <boolean property="notNull" value="true"/>
        <boolean property="autoIncrement" value="true"/>
        <string property="default" value="" />
        <boolean property="hasDefault" value="true"/>
        <integer property="length" value="0"/>
        <integer property="precision" value="0"/>
        <integer property="scale" value="0"/>
        <string property="sequence" value="city_city_id_seq" />
        <boolean property="unsigned" value="false" />
        <null property="minLength"/>
        <null property="maxLength"/>'.
                ($is64bits ?
                    '<integer property="minValue" value="-2147483648"/>' :
                    '<double property="minValue" value="-2147483648"/>').
                '<integer property="maxValue" value="2147483647"/>
    </object>';
            $this->cityColumns ['name'] = '<object class="\\Jelix\\Database\\Schema\\Column" key="name">
        <string property="type" value="character" />
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
        <string property="type" value="integer" />
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
        <string property="type" value="character" />
        <string property="name" value="latitude" />
        <boolean property="notNull" value="false"/>
        <boolean property="autoIncrement" value="false"/>
        <null property="default"/>
        <boolean property="hasDefault" value="false"/>
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
        <string property="type" value="character" />
        <string property="name" value="longitude" />
        <boolean property="notNull" value="false"/>
        <boolean property="autoIncrement" value="false"/>
        <null property="default"/>
        <boolean property="hasDefault" value="false"/>
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
        <boolean property="hasDefault" value="false"/>
        <integer property="length" value="0"/>
        <integer property="precision" value="0"/>
        <integer property="scale" value="0"/>
        <boolean property="sequence" value="false" />
        <boolean property="unsigned" value="false" />
        <integer property="minLength" value="0"/>
        <integer property="maxLength" value="0"/>
        <null property="minValue"/>
        <null property="maxValue"/>
    </object>';

            $this->cityColumns ['name2'] = '<object class="\\Jelix\\Database\\Schema\\Column" key="name">
        <string property="type" value="character" />
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
        <boolean property="hasDefault" value="false"/>
        <integer property="length" value="0"/>
        <integer property="precision" value="0"/>
        <integer property="scale" value="0"/>
        <boolean property="sequence" value="false" />
        <boolean property="unsigned" value="false" />
        <integer property="minLength" value="0"/>
        <integer property="maxLength" value="0"/>
        <null property="minValue"/>
        <null property="maxValue"/>
    </object>';
        }
    }

    protected $countryNameKey = '<object class="\\Jelix\\Database\\Schema\\UniqueKey" key="country_name_key">
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
    protected $city_name_postcode_idx = '<object class="\\Jelix\\Database\\Schema\\Index" key="city_name_postcode_idx">
                    <string property="name" value="city_name_postcode_idx" />
                    <array property="columns">
                        <string value="name"/>
                        <string value="postcode"/>
                    </array>
                </object>';
    protected $city_country_id_fkey = '<object class="\\Jelix\\Database\\Schema\\Reference" key="city_country_id_fkey">
                    <string property="name" value="city_country_id_fkey" />
                    <array property="columns">
                        <string value="country_id"/>
                    </array>
                    <string property="fTable" value="country" />
                    <array property="fColumns">
                        <string value="country_id"/>
                    </array>
                </object>';
    protected $city_country_id_fkey2 = '<object class="\\Jelix\\Database\\Schema\\Reference" key="city_country_id_fkey">
                    <string property="name" value="city_country_id_fkey" />
                    <array property="columns">
                        <string value="name"/>
                        <string value="country_id"/>
                    </array>
                    <string property="fTable" value="country" />
                    <array property="fColumns">
                        <string value="name"/>
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

        $goodList = array(
            'public.item_array_text',
            'public.labels_test',
            'public.product_test',
            'public.products',
            'public.products_with_identity',
            'public.generated_column_test',
            'newspaper.article',
        );

        $list = $schema->getTables();
        $tables = array_keys($list);

        sort($goodList);
        sort($tables);
        $this->assertEquals($goodList, $tables);
    }

    function testTable() {
        $db = $this->getConnection();
        $schema = $db->schema();

        $artTable = $schema->getTable('article');
        $this->assertNull($artTable); // because it is not into the default schema, public
        $artTable = $schema->getTable('newspaper.article');
        $this->assertNotNull($artTable);


        $table = $schema->getTable('product_test');

        $this->assertNotNull($table);

        $this->assertEquals('product_test', $table->getName());
        $this->assertEquals('public.product_test', $table->getTableName()->getFullName());

        $pk = $table->getPrimaryKey();
        $this->assertEquals(array('id'), $pk->columns);

        $is64bits = ( PHP_INT_SIZE*8 == 64 );

        $verif='<array>
    <object class="\\Jelix\\Database\\Schema\\Column" key="id">
        <string property="type" value="integer" />
        <string property="name" value="id" />
        <boolean property="notNull" value="true"/>
        <boolean property="autoIncrement" value="true"/>
        <string property="default" value="" />
        <boolean property="hasDefault" value="true"/>
        <integer property="length" value="0"/>
        <integer property="precision" value="0"/>
        <integer property="scale" value="0"/>
        <string property="sequence" value="product_test_id_seq" />
        <boolean property="unsigned" value="false" />
        <null property="minLength"/>
        <null property="maxLength"/>'.
            ($is64bits ?
                '<integer property="minValue" value="-2147483648"/>' :
                '<double property="minValue" value="-2147483648"/>').
            '<integer property="maxValue" value="2147483647"/>
    </object>
    <object class="\\Jelix\\Database\\Schema\\Column" key="name">
        <string property="type" value="character" />
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
        <string property="type" value="real" />
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
        <string property="type" value="time" />
        <string property="name" value="create_date" />
        <boolean property="notNull" value="false"/>
        <boolean property="autoIncrement" value="false"/>
        <null property="default" />
        <boolean property="hasDefault" value="false"/>
        <integer property="length" value="0"/>
        <integer property="precision" value="0"/>
        <integer property="scale" value="0"/>
        <boolean property="sequence" value="false" />
        <boolean property="unsigned" value="false" />
        <integer property="minLength" value="8"/>
        <integer property="maxLength" value="8"/>
        <null property="minValue"/>
        <null property="maxValue"/>
    </object>
    <object class="\\Jelix\\Database\\Schema\\Column" key="promo">
        <string property="type" value="boolean" />
        <string property="name" value="promo" />
        <boolean property="notNull" value="true"/>
        <boolean property="autoIncrement" value="false"/>
        <boolean property="default" value="false" />
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
                <string property="name" value="product_test_pkey" />
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
    function testTableArray() {
        $db = $this->getConnection();
        $schema = $db->schema();

        $this->assertEquals(
            [ 'text', 0, 0, 0, '[]'],
            $db->tools()->parseSQLType('text[]'));


        $table = $schema->getTable('item_array_text');

        $this->assertNotNull($table);

        $this->assertEquals('item_array_text', $table->getName());

        $pk = $table->getPrimaryKey();
        $this->assertEquals(array('id'), $pk->columns);

        $is64bits = ( PHP_INT_SIZE*8 == 64 );

        $verif='<array>
    <object class="\\Jelix\\Database\\Schema\\Column" key="id">
        <string property="type" value="integer" />
        <string property="name" value="id" />
        <boolean property="notNull" value="true"/>
        <boolean property="autoIncrement" value="true"/>
        <string property="default" value="" />
        <boolean property="hasDefault" value="true"/>
        <integer property="length" value="0"/>
        <integer property="precision" value="0"/>
        <integer property="scale" value="0"/>
        <string property="sequence" value="item_array_text_id_seq" />
        <boolean property="unsigned" value="false" />
        <integer property="arrayDims" value="0" />
        <null property="minLength"/>
        <null property="maxLength"/>'.
            ($is64bits ?
                '<integer property="minValue" value="-2147483648"/>' :
                '<double property="minValue" value="-2147483648"/>').
            '<integer property="maxValue" value="2147483647"/>
    </object>
    <object class="\\Jelix\\Database\\Schema\\Column" key="mytext">
        <string property="type" value="text" />
        <string property="name" value="mytext" />
        <boolean property="notNull" value="true"/>
        <boolean property="autoIncrement" value="false"/>
        <string property="default" value="" />
        <boolean property="hasDefault" value="false"/>
        <integer property="length" value="0"/>
        <integer property="precision" value="0"/>
        <integer property="scale" value="0"/>
        <boolean property="sequence" value="false" />
        <boolean property="unsigned" value="false" />
        <integer property="minLength" value="0"/>
        <integer property="maxLength" value="0"/>
        <null property="minValue"/>
        <null property="maxValue"/>
        <integer property="arrayDims" value="1" />
    </object>
    <object class="\\Jelix\\Database\\Schema\\Column" key="mytext2">
        <string property="type" value="text" />
        <string property="name" value="mytext2" />
        <boolean property="notNull" value="true"/>
        <boolean property="autoIncrement" value="false"/>
        <string property="default" value="" />
        <boolean property="hasDefault" value="false"/>
        <integer property="length" value="0"/>
        <integer property="precision" value="0"/>
        <integer property="scale" value="0"/>
        <boolean property="sequence" value="false" />
        <boolean property="unsigned" value="false" />
        <integer property="minLength" value="0"/>
        <integer property="maxLength" value="0"/>
        <null property="minValue"/>
        <null property="maxValue"/>
        <integer property="arrayDims" value="1" />
    </object>
<object class="\\Jelix\\Database\\Schema\\Column" key="myintegers">
        <string property="type" value="integer" />
        <string property="name" value="myintegers" />
        <boolean property="notNull" value="true"/>
        <boolean property="autoIncrement" value="false"/>
        <string property="default" value="" />
        <boolean property="hasDefault" value="false"/>
        <integer property="length" value="0"/>
        <integer property="precision" value="0"/>
        <integer property="scale" value="0"/>
        <boolean property="sequence" value="false" />
        <boolean property="unsigned" value="false" />'.
               ($is64bits ?
                   '<integer property="minValue" value="-2147483648"/>' :
                   '<double property="minValue" value="-2147483648"/>').
               '<integer property="maxValue" value="2147483647"/>
        <null property="minLength"/>
        <null property="maxLength"/>
        <integer property="arrayDims" value="1" />
    </object>
<object class="\\Jelix\\Database\\Schema\\Column" key="myintegers2">
        <string property="type" value="integer" />
        <string property="name" value="myintegers2" />
        <boolean property="notNull" value="true"/>
        <boolean property="autoIncrement" value="false"/>
        <string property="default" value="" />
        <boolean property="hasDefault" value="false"/>
        <integer property="length" value="0"/>
        <integer property="precision" value="0"/>
        <integer property="scale" value="0"/>
        <boolean property="sequence" value="false" />
        <boolean property="unsigned" value="false" />'.
               ($is64bits ?
                   '<integer property="minValue" value="-2147483648"/>' :
                   '<double property="minValue" value="-2147483648"/>').
               '<integer property="maxValue" value="2147483647"/>
        <null property="minLength"/>
        <null property="maxLength"/>
        <integer property="arrayDims" value="1" />
    </object>

<object class="\\Jelix\\Database\\Schema\\Column" key="myintegers3">
        <string property="type" value="integer" />
        <string property="name" value="myintegers3" />
        <boolean property="notNull" value="true"/>
        <boolean property="autoIncrement" value="false"/>
        <string property="default" value="" />
        <boolean property="hasDefault" value="false"/>
        <integer property="length" value="0"/>
        <integer property="precision" value="0"/>
        <integer property="scale" value="0"/>
        <boolean property="sequence" value="false" />
        <boolean property="unsigned" value="false" />'.
               ($is64bits ?
                   '<integer property="minValue" value="-2147483648"/>' :
                   '<double property="minValue" value="-2147483648"/>').
               '<integer property="maxValue" value="2147483647"/>
        <null property="minLength"/>
        <null property="maxLength"/>
        <integer property="arrayDims" value="2" />
    </object>

    
</array>';

        $this->assertComplexIdenticalStr($table->getColumns(), $verif);

        $verif = '<object class="\\Jelix\\Database\\Schema\\PrimaryKey">
                <string property="name" value="item_array_text_pkey" />
                <array property="columns">
                    <string value="id"/>
                </array>
         </object>';
        $this->assertComplexIdenticalStr($table->getPrimaryKey(), $verif);
        $this->assertEquals(array(), $table->getIndexes());
        $this->assertEquals(array(), $table->getUniqueKeys());
        $this->assertEquals(array(), $table->getReferences());
        $this->assertTrue($table->getColumn('id')->isAutoincrementedColumn());
        $this->assertFalse($table->getColumn('myintegers2')->isAutoincrementedColumn());
    }

    function testTableHavingIdentity() {

        $db = $this->getConnection();
        $schema = $db->schema();

        $table = $schema->getTable('products_with_identity');

        $this->assertNotNull($table);

        $this->assertEquals('products_with_identity', $table->getName());

        $pk = $table->getPrimaryKey();
        $this->assertEquals(array('id'), $pk->columns);

        $is64bits = ( PHP_INT_SIZE*8 == 64 );

        $verif='<array>
    <object class="\\Jelix\\Database\\Schema\\Column" key="id">
        <string property="type" value="integer" />
        <string property="name" value="id" />
        <boolean property="notNull" value="true"/>
        <boolean property="autoIncrement" value="true"/>
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
    <object class="\\Jelix\\Database\\Schema\\Column" key="name">
        <string property="type" value="character" />
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
        <string property="type" value="real" />
        <string property="name" value="price" />
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
        <null property="maxLength"/>
        <null property="minValue"/>
        <null property="maxValue"/>
    </object>
    <object class="\\Jelix\\Database\\Schema\\Column" key="promo">
        <string property="type" value="boolean" />
        <string property="name" value="promo" />
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
        <integer property="minValue" value="0"/>
        <integer property="maxValue" value="1"/>
    </object>
</array>';

        $this->assertComplexIdenticalStr($table->getColumns(), $verif);

        $verif = '<object class="\\Jelix\\Database\\Schema\\PrimaryKey">
                <string property="name" value="products_with_identity_pkey" />
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
        $this->assertFalse($table->getColumn('name')->generated);
    }


    function testGeneratedColumn()
    {
        $db = $this->getConnection();
        $schema = $db->schema();

        $table = $schema->getTable('generated_column_test');

        $this->assertNotNull($table);

        $this->assertEquals('generated_column_test', $table->getName());

        $pk = $table->getPrimaryKey();
        $this->assertEquals(array('id'), $pk->columns);
        $this->assertTrue($table->getColumn('total')->generated);

        // insert test value
        $stmt = $db->prepare('INSERT INTO generated_column_test (description, amount, change) VALUES(:d, :a, :c)');

        $stmt->bindValue('d','candy');
        $stmt->bindValue('a', 2);
        $stmt->bindValue('c',1.02);

        $stmt->execute();

        $rs = $db->query('SELECT id, description, amount, change, total FROM generated_column_test');

        $record = $rs->fetch();

        $this->assertEquals($record->id, 1);
        $this->assertEquals($record->description, 'candy');
        $this->assertEquals($record->amount, 2);
        $this->assertEquals($record->change, 1.02);
        $this->assertEquals($record->total, 2.04);
    }

    function testCreateTable()
    {
        $db = $this->getConnection();
        $db->exec('DROP TABLE IF EXISTS test_prod');
        $db->exec('DROP TABLE IF EXISTS city');
        $db->exec('DROP TABLE IF EXISTS bigcity');
        $db->exec('DROP TABLE IF EXISTS country');

        $schema = $db->schema();


        $columns = array();
        $col = new Column('id', 'int', 0, false, null, true);
        $col->autoIncrement = true;
        $columns[] = $col;
        $columns[] = new Column('name', 'string', 50);
        $columns[] = new Column('price', 'double',0,  true, null, false);
        $columns[] = new Column('promo', 'boolean', 0, true, true);
        $columns[] = new Column('product_id', 'int', 0, false, null, true);

        $schema->createTable('test_prod', $columns, 'id');

        $table = new pgsqlTable(new pgsqlTableName('test_prod'), $schema);

        $this->assertEquals('test_prod', $table->getName());

        $pk = $table->getPrimaryKey();
        $this->assertEquals(array('id'), $pk->columns);

        $is64bits = ( PHP_INT_SIZE*8 == 64 );

        $verif='<array>
    <object class="\\Jelix\\Database\\Schema\\Column" key="id">
        <string property="type" value="integer" />
        <string property="name" value="id" />
        <boolean property="notNull" value="true"/>
        <boolean property="autoIncrement" value="true"/>
        <string property="default" value="" />
        <boolean property="hasDefault" value="true"/>
        <integer property="length" value="0"/>
        <integer property="precision" value="0"/>
        <integer property="scale" value="0"/>
        <string property="sequence" value="test_prod_id_seq" />
        <boolean property="unsigned" value="false" />
        <null property="minLength"/>
        <null property="maxLength"/>'.
            ($is64bits ?
                '<integer property="minValue" value="-2147483648"/>' :
                '<double property="minValue" value="-2147483648"/>').
            '<integer property="maxValue" value="2147483647"/>
    </object>
    <object class="\\Jelix\\Database\\Schema\\Column" key="name">
        <string property="type" value="character" />
        <string property="name" value="name" />
        <boolean property="notNull" value="false"/>
        <boolean property="autoIncrement" value="false"/>
        <null property="default" />
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
    </object>
    <object class="\\Jelix\\Database\\Schema\\Column" key="price">
        <string property="type" value="double" />
        <string property="name" value="price" />
        <boolean property="notNull" value="false"/>
        <boolean property="autoIncrement" value="false"/>
        <null property="default" />
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
    <object class="\\Jelix\\Database\\Schema\\Column" key="product_id">
        <string property="type" value="integer" />
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

    function testCreateSchemaTable()
    {
        $db = $this->getConnection();
        $db->exec('DROP TABLE IF EXISTS newspaper.test_article');
        $schema = $db->schema();

        $columns = array();
        $col = new Column('id', 'int', 0, false, null, true);
        $col->autoIncrement = true;
        $columns[] = $col;
        $columns[] = new Column('title', 'string', 50);

        $schema->createTable('newspaper.test_article', $columns, 'id');

        $table = new pgsqlTable(new pgsqlTableName('newspaper.test_article'), $schema);

        $this->assertEquals('test_article', $table->getName());
        $this->assertEquals('newspaper.test_article', $table->getTableName()->getFullName());

        $pk = $table->getPrimaryKey();
        $this->assertEquals(array('id'), $pk->columns);

        $is64bits = ( PHP_INT_SIZE*8 == 64 );

        $verif='<array>
    <object class="\\Jelix\\Database\\Schema\\Column" key="id">
        <string property="type" value="integer" />
        <string property="name" value="id" />
        <boolean property="notNull" value="true"/>
        <boolean property="autoIncrement" value="true"/>
        <string property="default" value="" />
        <boolean property="hasDefault" value="true"/>
        <integer property="length" value="0"/>
        <integer property="precision" value="0"/>
        <integer property="scale" value="0"/>
        <string property="sequence" value="test_article_id_seq" />
        <boolean property="unsigned" value="false" />
        <null property="minLength"/>
        <null property="maxLength"/>'.
            ($is64bits ?
                '<integer property="minValue" value="-2147483648"/>' :
                '<double property="minValue" value="-2147483648"/>').
            '<integer property="maxValue" value="2147483647"/>
    </object>
    <object class="\\Jelix\\Database\\Schema\\Column" key="title">
        <string property="type" value="character" />
        <string property="name" value="title" />
        <boolean property="notNull" value="false"/>
        <boolean property="autoIncrement" value="false"/>
        <null property="default" />
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
    </object>
</array>';

        $this->assertComplexIdenticalStr($table->getColumns(), $verif);
        $db->exec('DROP TABLE IF EXISTS newspaper.test_article');
    }

    public function testGetTablesAndConstraintsIndexes() {
        $db = $this->getConnection();
        $db->exec('DROP TABLE IF EXISTS city');
        $db->exec('DROP TABLE IF EXISTS bigcity');
        $db->exec('DROP TABLE IF EXISTS country');

        $db->exec('CREATE TABLE country (
    country_id serial PRIMARY KEY,
    name varchar(50) not null,
    UNIQUE(name)
)');
        $db->exec('CREATE TABLE city (
    city_id serial PRIMARY KEY,
    country_id integer NOT NULL,
    name  varchar(50) not null,
    postcode integer DEFAULT 0,
    latitude varchar(20),
    longitude varchar(20),
    CONSTRAINT coordinates UNIQUE(latitude, longitude),
    FOREIGN KEY (country_id) REFERENCES country (country_id))');

        $db->exec('CREATE INDEX city_name_idx ON city (name)');
        $db->exec('CREATE UNIQUE INDEX city_name_postcode_idx ON city (name, postcode)');

        $schema = new pgsqlSchema($db);
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

        $this->assertComplexIdenticalStr($city->getIndexes(),
            '<array>'.$this->city_name_idx.
            $this->city_name_postcode_idx.'</array>'
        );
        $this->assertComplexIdenticalStr($city->getUniqueKeys(),
            '<array>
            </array>'
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
        $schema = new pgsqlSchema($db);

        $schema->renameTable('city', 'bigcity');

        $goodList = array(
            'article',
            'country',
            'bigcity',
            'generated_column_test',
            'item_array_text',
            'labels_test',
            'product_test',
            'products',
            'products_with_identity',
            'test_prod',
        );

        $list = $schema->getTables();
        $tables = array();
        foreach($list as $table) {
            $tables[] = $table->getName();
        }

        sort($goodList);
        sort($tables);
        $this->assertEquals($goodList, $tables);
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
            '<array>'.$this->city_name_idx.
            $this->city_name_postcode_idx.'</array>'
        );
        $this->assertComplexIdenticalStr($city->getUniqueKeys(),
            '<array>
            </array>'
        );
        $this->assertComplexIdenticalStr($city->getReferences(),
            '<array>'.$this->city_country_id_fkey.'</array>'
        );
    }

    /**
     * @depends testRenameTable
     */
    public function testAddColumn() {
        $db = $this->getConnection();
        $schema = new pgsqlSchema($db);
        $city = $schema->getTable('bigcity');
        $col = new Column('description', 'text');
        $city->addColumn($col);

        $schema = new pgsqlSchema($db);
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
            '<array>'.$this->city_name_idx.
            $this->city_name_postcode_idx.'</array>'
        );
        $this->assertComplexIdenticalStr($city->getUniqueKeys(),
            '<array>
            </array>'
        );
        $this->assertComplexIdenticalStr($city->getReferences(),
            '<array>'.$this->city_country_id_fkey.'</array>'
        );
    }

    /**
     * @depends testAddColumn
     */
    public function testAlterColumn() {
        $db = $this->getConnection();
        $schema = new pgsqlSchema($db);
        $city = $schema->getTable('bigcity');

        $name = $city->getColumn('name', true);
        $name->length = 150;

        $desc = $city->getColumn('description', true);
        $desc->name = 'superdesc';

        $city->alterColumn($name);
        $city->alterColumn($desc, 'description');

        $schema = new pgsqlSchema($db);
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
            '<array>'.$this->city_name_idx.
            $this->city_name_postcode_idx.'</array>'
        );
        $this->assertComplexIdenticalStr($city->getUniqueKeys(),
            '<array>
            </array>'
        );
        $this->assertComplexIdenticalStr($city->getReferences(),
            '<array>'.$this->city_country_id_fkey.'</array>'
        );
    }

    /**
     * @depends testAlterColumn
     */
    public function testDropColumn() {
        $db = $this->getConnection();
        $schema = new pgsqlSchema($db);
        $city = $schema->getTable('bigcity');
        $city->dropColumn('superdesc');

        $schema = new pgsqlSchema($db);
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
            '<array>'.$this->city_name_idx.
            $this->city_name_postcode_idx.'</array>'
        );
        $this->assertComplexIdenticalStr($city->getUniqueKeys(),
            '<array>
            </array>'
        );
        $this->assertComplexIdenticalStr($city->getReferences(),
            '<array>'.$this->city_country_id_fkey.'</array>'
        );
    }

    /**
     * @depends testDropColumn
     */
    public function testDropTable() {
        $db = $this->getConnection();
        $schema = new pgsqlSchema($db);

        $schema->dropTable('bigcity');
        $schema->dropTable($schema->getTable('country'));
        $goodList = array(
            'generated_column_test',
            'article',
            'labels_test',
            'product_test',
            'products',
            'test_prod',
            'item_array_text',
            'products_with_identity',
        );

        $list = $schema->getTables();
        $tables = array();
        foreach($list as $table) {
            $tables[] = $table->getName();
        }

        sort($goodList);
        sort($tables);
        $this->assertEquals($goodList, $tables);
        $this->assertNull($schema->getTable('bigcity'));
        $this->assertNull($schema->getTable('country'));
    }


    /**
     * @depends testDropTable
     */
    public function testCreateTableAndAddDropPrimaryKey() {
        $db = $this->getConnection();
        $schema = new pgsqlSchema($db);

        $columns = array();
        $columns[] = new Column('country_id', 'serial');
        $columns[] = new Column('name', 'varchar', 50, false, null, true);

        $country = $schema->createTable('country', $columns, 'country_id');

        $pk = $country->getPrimaryKey();
        $this->assertEquals(array('country_id'), $pk->columns);

        $country->dropPrimaryKey();
        $this->assertFalse($country->getPrimaryKey());

        $schema = new pgsqlSchema($db);
        $country = $schema->getTable('country');
        $this->assertFalse($country->getPrimaryKey());

        $schema = new pgsqlSchema($db);
        $country = $schema->getTable('country');
        $country->setPrimaryKey($pk);
        $pk = $country->getPrimaryKey();
        $this->assertEquals(array('country_id'), $pk->columns);

        $schema = new pgsqlSchema($db);
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
        $schema = new pgsqlSchema($db);

        $columns = array();
        $columns[] = new Column('country_id', 'serial');
        $columns[] = new Column('name', 'varchar', 50, false, null, true);
        $country = $schema->createTable('country', $columns, 'country_id');

        $columns = array();
        $columns[] = new Column('city_id', 'serial');
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
        $schema = new pgsqlSchema($db);
        $city = $schema->getTable('city');
        $index = new Index('city_name_idx', '', array('name'));
        $city->addIndex($index);
        $index = new Index('city_name_postcode_idx', '', array('name', 'postcode'));
        $index->isUnique = true;
        $city->addIndex($index);

        $this->assertComplexIdenticalStr($city->getIndexes(),
            '<array>'.$this->city_name_idx.
            $this->city_name_postcode_idx.'</array>'
        );

        $schema = new pgsqlSchema($db); // reload all
        $city = $schema->getTable('city');
        $this->assertComplexIdenticalStr($city->getIndexes(),
            '<array>'.$this->city_name_idx.
            $this->city_name_postcode_idx.'</array>'
        );
    }

    /**
     * @depends testAddIndex
     */
    public function testDropIndex() {
        $db = $this->getConnection();
        $schema = new pgsqlSchema($db); // reload all
        $city = $schema->getTable('city');
        $this->assertComplexIdenticalStr($city->getIndexes(),
            '<array>'.$this->city_name_idx.
            $this->city_name_postcode_idx.'</array>'
        );

        $city->dropIndex('city_name_idx');
        $this->assertNull($city->getIndex('city_name_idx'));
        $this->assertComplexIdenticalStr($city->getIndexes(),
            '<array>'.$this->city_name_postcode_idx.'</array>'
        );

        $schema = new pgsqlSchema($db); // reload all
        $city = $schema->getTable('city');
        $this->assertNull($city->getIndex('city_name_idx'));
        $this->assertComplexIdenticalStr($city->getIndexes(),
            '<array>'.$this->city_name_postcode_idx.'</array>'
        );
    }

    /**
     * @depends testDropIndex
     */
    public function testAddUniqueKey() {
        $db = $this->getConnection();
        $schema = new pgsqlSchema($db); // reload all
        $country = $schema->getTable('country');

        $key = new UniqueKey('country_name_key', array('name'));
        $country->addUniqueKey($key);
        $this->assertComplexIdenticalStr($country->getUniqueKeys(),
            '<array>'.$this->countryNameKey.'</array>'
        );

        $schema = new pgsqlSchema($db); // reload all
        $country = $schema->getTable('country');
        $this->assertComplexIdenticalStr($country->getUniqueKeys(),
            '<array>'.$this->countryNameKey.'</array>'
        );
    }

    /**
     * @depends testAddUniqueKey
     */
    public function testDropUniqueKey() {
        $db = $this->getConnection();
        $schema = new pgsqlSchema($db); // reload all
        $country = $schema->getTable('country');
        $country->dropUniqueKey('country_name_key');

        $this->assertEquals(array(), $country->getUniqueKeys());
        $schema = new pgsqlSchema($db); // reload all
        $country = $schema->getTable('country');
        $this->assertEquals(array(), $country->getUniqueKeys());
    }


    /**
     * @depends testDropUniqueKey
     */
    public function testAddReference() {
        $db = $this->getConnection();
        $schema = new pgsqlSchema($db); // reload all
        $city = $schema->getTable('city');

        $key = new Reference('city_country_id_fkey', array('country_id'),
            'country', array('country_id'));
        $city->addReference($key);
        $this->assertComplexIdenticalStr($city->getReferences(),
            '<array>'.$this->city_country_id_fkey.'</array>'
        );

        $schema = new pgsqlSchema($db); // reload all
        $city = $schema->getTable('city');
        $this->assertComplexIdenticalStr($city->getReferences(),
            '<array>'.$this->city_country_id_fkey.'</array>'
        );
    }

    /**
     * @depends testAddReference
     */
    public function testDropReference() {
        $db = $this->getConnection();
        $schema = new pgsqlSchema($db); // reload all
        $city = $schema->getTable('city');
        $city->dropReference('city_country_id_fkey');

        $this->assertEquals(array(), $city->getReferences());
        $schema = new pgsqlSchema($db); // reload all
        $city = $schema->getTable('city');
        $this->assertEquals(array(), $city->getReferences());
    }

    public function testEqualColumn()
    {
        $col1 = new Column(
            'id',
            'integer',
            0,
            true,
            '',
            true
        );
        $col1->nativeType = 'integer';
        $col1->autoIncrement = true;
        $col1->sequence = 'community_users_id_seq';

        $col2 = new Column(
            'id',
            'autoincrement',
            0,
            true,
            '',
            true
        );
        $col2->nativeType = 'serial';
        $col2->autoIncrement = true;
        $col2->sequence = false;

        $this->assertTrue($col1->isEqualTo($col2));
    }
}

