<?php
/**
 * @author      Laurent Jouanneau
 * @copyright   2007-2020 Laurent Jouanneau
 * @link        https://jelix.org
 * @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
 */
use \Jelix\Database\Connection;
use \Jelix\Database\Schema\Column;
use \Jelix\Database\Schema\UniqueKey;
use \Jelix\Database\Schema\Reference;
use \Jelix\Database\Schema\Index;
use \Jelix\Database\Schema\Postgresql\Table as pgsqlTable;
use \Jelix\Database\Schema\Postgresql\Schema as pgsqlSchema;


class pgsqlToolsTest extends \Jelix\UnitTests\UnitTestCaseDb
{
    use assertComplexTrait;

    protected static $connectionPgsql = null;

    protected function getConnection()
    {
        if (self::$connectionPgsql === null) {
            $parameters = new \Jelix\Database\AccessParameters(array(
                'driver' => 'pgsql',
                'host' => 'pgsql',
                'user' => 'jelix',
                'password' => "jelixpass",
                "database" => "jelixtests"
            ), array('charset' => 'UTF-8'));

            self::$connectionPgsql = Connection::create($parameters);
        }
        return self::$connectionPgsql;
    }


    function testGetFieldList()
    {
        /** @var \Jelix\Database\Schema\Postgresql\SQLTools $tools */
        $tools = $this->getConnection()->tools();

        $fields = $tools->getFieldList('products');
        $structure = '<array>
    <object key="id" class="\\Jelix\\Database\\Schema\\FieldProperties">
        <string property="type" value="int" />
        <string property="name" value="id" />
        <boolean property="notNull" value="true" />
        <boolean property="primary" value="true" />
        <boolean property="autoIncrement" value="true" />
        <boolean property="hasDefault" value="true" />
        <string property="default" value="" />
        <integer property="length" value="0" />
    </object>
    <object key="name" class="\\Jelix\\Database\\Schema\\FieldProperties">
        <string property="type" value="varchar" />
        <string property="name" value="name" />
        <boolean property="notNull" value="true" />
        <boolean property="primary" value="false" />
        <boolean property="autoIncrement" value="false" />
        <boolean property="hasDefault" value="false" />
        <null property="default" />
        <integer property="length" value="150" />
    </object>
    <object key="price" class="\\Jelix\\Database\\Schema\\FieldProperties">
        <string property="type" value="float" />
        <string property="name" value="price" />
        <boolean property="notNull" value="false" />
        <boolean property="primary" value="false" />
        <boolean property="autoIncrement" value="false" />
        <boolean property="hasDefault" value="true" />
        <string property="default" value="0" />
        <integer property="length" value="0" />
    </object>
    <object key="promo" class="\\Jelix\\Database\\Schema\\FieldProperties">
        <string property="type" value="bool" />
        <string property="name" value="promo" />
        <boolean property="notNull" value="true" />
        <boolean property="primary" value="false" />
        <boolean property="autoIncrement" value="false" />
        <boolean property="hasDefault" value="false" />
        <null property="default" />
        <integer property="length" value="0" />
    </object>
</array>';
        $this->assertComplexIdenticalStr($fields, $structure, 'bad results');
    }


    function getArrayValuesToParse()
    {
        return array(
            array('{}',  array()),
            array('{0}',  array(0)),
            array('{0}',  array(0)),
            array('{0,1,2}',  array(0, 1, 2)),
            array('{{0,1,2},{4,5}}',  array(array(0, 1, 2), array(4, 5))),
            array('{abc}', array('abc')),
            array('{"abc"}',  array('abc')),
            array('{"abc",cdfe}',  array('abc', 'cdfe')),
            array('{"a\"bc",cdfe}',  array('a"bc', 'cdfe')),
            array('{"ab{c","c,",dfe}',  array('ab{c', 'c,', 'dfe')),
        );
    }

    /**
     * @dataProvider getArrayValuesToParse
     * @param $value
     * @param $type
     * @param $expectedArray
     */
    function testParseArrayValue($value, $expectedArray)
    {
        /** @var \Jelix\Database\Schema\Postgresql\SQLTools $tools */
        $tools = $this->getConnection()->tools();
        $result = $tools->decodeArrayValue($value);
        $this->assertEquals($expectedArray, $result);
    }



    function getArrayValuesToSerialize()
    {
        return array(
            array('{}', 'int', array()),
            array('{0}', 'int', array(0)),
            array('{0}', 'int', array(0)),
            array('{0,1,2}', 'int', array(0, 1, 2)),
            array('{0,0,2}', 'int', array(0, "abc", 2)),
            array('{"0","1","2"}', 'text', array(0, 1, 2)),
            array('{{0,1,2},{4,5}}', 'int', array(array(0, 1, 2), array(4, 5))),
            array('{abc}', 'text', array('abc')),
            array('{abc}', 'text', array('abc')),
            array('{abc,cdfe}', 'text', array('abc', 'cdfe')),
            array('{"a\"bc",cdfe}', 'text', array('a"bc', 'cdfe')),
            array('{"ab{c","c,",dfe}', 'text', array('ab{c', 'c,', 'dfe')),
        );
    }

    /**
     * @dataProvider getArrayValuesToSerialize
     * @param $expectedString
     * @param $type
     * @param $value
     */
    function testSerializeArrayValue($expectedString, $type, $value)
    {
        /** @var \Jelix\Database\Schema\Postgresql\SQLTools $tools */
        $tools = $this->getConnection()->tools();
        $result = $tools->encodeArrayValue($value, $type);
        $this->assertEquals($expectedString, $result);
    }
}
