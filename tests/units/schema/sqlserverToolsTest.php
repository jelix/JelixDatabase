<?php
/**
 * @author      Laurent Jouanneau
 * @copyright   2020 Laurent Jouanneau
 * @link        https://jelix.org
 * @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
 */
use \Jelix\Database\Connection;

class sqlserverToolsTest extends \Jelix\UnitTests\UnitTestCaseDb
{
    use assertComplexTrait;
    protected static $connectionSqlsrv = null;

    protected function getConnection()
    {
        if (self::$connectionSqlsrv === null) {
            $parameters = new \Jelix\Database\AccessParameters(array(
                'driver'=>'sqlsrv',
                'host'=>'sqlsrv',
                'user'=>'SA',
                'password'=>"JelixPass2020!",
                "database"=>"jelixtests"
            ), array('charset'=>'UTF-8'));

            self::$connectionSqlsrv = Connection::create($parameters->getParameters());
        }
        return self::$connectionSqlsrv;
    }


    function testGetFieldList(){
        /** @var \Jelix\Database\Schema\Sqlserver\SQLTools $tools */
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
        <string property="default" value=""/>
        <integer property="length" value="4" />
    </object>
    <object key="name" class="\\Jelix\\Database\\Schema\\FieldProperties">
        <string property="type" value="varchar" />
        <string property="name" value="name" />
        <boolean property="notNull" value="true" />
        <boolean property="primary" value="false" />
        <boolean property="autoIncrement" value="false" />
        <boolean property="hasDefault" value="true" />
        <string property="default" value=""/>
        <integer property="length" value="150" />
    </object>
    <object key="price" class="\\Jelix\\Database\\Schema\\FieldProperties">
        <string property="type" value="real" />
        <string property="name" value="price" />
        <boolean property="notNull" value="false" />
        <boolean property="primary" value="false" />
        <boolean property="autoIncrement" value="false" />
        <boolean property="hasDefault" value="true" />
        <string property="default" value="0" />
        <integer property="length" value="4" />
    </object>
    <object key="promo" class="\\Jelix\\Database\\Schema\\FieldProperties">
        <string property="type" value="tinyint" />
        <string property="name" value="promo" />
        <boolean property="notNull" value="true" />
        <boolean property="primary" value="false" />
        <boolean property="autoIncrement" value="false" />
        <boolean property="hasDefault" value="true" />
        <string property="default" value="0" />
        <integer property="length" value="1" />
    </object>
</array>';
        $this->assertComplexIdenticalStr($fields, $structure, 'bad results');
    }

}