<?php
/**
 * @author      Laurent Jouanneau
 * @copyright   2007-2020 Laurent Jouanneau
 * @link        https://jelix.org
 * @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
 */
use \PHPUnit\Framework\TestCase;
use \Jelix\Database\AbstractConnection;
use Jelix\Database\AbstractResultSet;

abstract class queriesTestAbstract extends \Jelix\UnitTests\UnitTestCaseDb
{
    use assertComplexTrait;

    protected $connectionInstanceName =  '';
    protected $recordSetClassName = '';

    public function testConnection()
    {
        $cnt = $this->getConnection();
        $this->assertInstanceOf($this->connectionInstanceName, $cnt);
    }

    /**
     * @depends testConnection
     */
    public function testEmptyATable()
    {
        $db = $this->getConnection();
        $db->exec('DELETE FROM product_test');

        $rs = $db->query('SELECT count(*) as '.$db->encloseName('N').' FROM product_test');
        if ($r=$rs->fetch()) {
            $this->assertEquals(0, $r->N, "After a DELETE, product_test table should be empty !!");
        } else {
            $this->fail("After a DELETE, product_test table should be empty, but error when try to get record count");
        }
    }


    /**
     * @depends testEmptyATable
     */
    public function testInsert()
    {
        $db = $this->getConnection();
        $nb = $db->exec("INSERT INTO product_test( name, price) VALUES('camembert',2.31) ");
        $this->assertEquals(1, $nb, 'exec insert 1 should return 1');
        $nb = $db->exec("INSERT INTO product_test( name, price) VALUES('yaourt',0.76) ");
        $this->assertEquals(1, $nb, 'exec insert 2 should return 1');
        $nb = $db->exec("INSERT INTO product_test( name, price) VALUES('gloubi-boulga',4.9)");
        $this->assertEquals(1, $nb, 'exec insert 3 should return 1');
    }

    /**
     * @depends testInsert
     */
    public function testSelect()
    {
        $db = $this->getConnection();
        $resultSet = $db->query('SELECT id,name,price FROM product_test');
        $this->assertNotNull($resultSet, 'a query return null !');
        $this->assertTrue($resultSet instanceof \Jelix\Database\ResultSetInterface, 'resultset is not a ResultSetInterface');

        $list = array();
        //foreach($resultSet as $res){
        while ($res = $resultSet->fetch()) {
            $list[] = $res;
        }
        $this->assertEquals(3, count($list), 'query return bad number of results ('.count($list).')');

        $structure = '<array>
    <object>
        <string property="name" value="camembert" />
        <string property="price" value="2.31" />
    </object>
    <object>
        <string property="name" value="yaourt" />
        <string property="price" value="0.76" />
    </object>
    <object>
        <string property="name" value="gloubi-boulga" />
        <string property="price" value="4.9" />
    </object>
</array>';
        $this->assertComplexIdenticalStr($list, $structure, 'bad results');

        $res = $resultSet->fetch();
        $this->assertTrue($res === false || $res === null);
    }

    public function _callbackTest($record, $rs)
    {
        $record->name.='_suffix';
        $record->price+=10;
    }

    /**
     * @depends testSelect
     */
    public function testSelectWithModifier()
    {
        $db = $this->getConnection();
        $resultSet = $db->query('SELECT id, name,price FROM product_test');
        $this->assertNotNull($resultSet, 'a query return null !');
        $this->assertTrue($resultSet instanceof \Jelix\Database\ResultSetInterface, 'resultset is not a ResultSetInterface');

        $resultSet->addModifier(array($this, '_callbackTest'));

        $list = array();
        //foreach($resultSet as $res){
        while ($res = $resultSet->fetch()) {
            $list[] = $res;
        }
        $this->assertEquals(3, count($list), 'query return bad number of results ('.count($list).')');

        $structure = '<array>
    <object>
        <string property="name" value="camembert_suffix" />
        <float property="price" value="12.31" />
    </object>
    <object>
        <string property="name" value="yaourt_suffix" />
        <float property="price" value="10.76" />
    </object>
    <object>
        <string property="name" value="gloubi-boulga_suffix" />
        <float property="price" value="14.9" />
    </object>
</array>';
        $this->assertComplexIdenticalStr($list, $structure, 'bad results');
    }

    /**
     * @depends testSelectWithModifier
     */
    public function testFetchClass()
    {
        $db = $this->getConnection();
        $resultSet = $db->query('SELECT id,name,price FROM product_test');
        $this->assertNotNull($resultSet, 'a query return null !');
        $this->assertTrue($resultSet instanceof \Jelix\Database\ResultSetInterface, 'resultset is not a ResultSetInterface');

        $resultSet->setFetchMode(8, 'MyProductContainer');

        $list = array();
        //foreach($resultSet as $res){
        while ($res = $resultSet->fetch()) {
            $list[] = $res;
        }
        $this->assertEquals(3, count($list), 'query return bad number of results ('.count($list).')');

        $structure = '<array>
    <object class="MyProductContainer">
        <string property="name" value="camembert" />
        <string property="price" value="2.31" />
    </object>
    <object class="MyProductContainer">
        <string property="name" value="yaourt" />
        <string property="price" value="0.76" />
    </object>
    <object class="MyProductContainer">
        <string property="name" value="gloubi-boulga" />
        <string property="price" value="4.9" />
    </object>
</array>';
        $this->assertComplexIdenticalStr($list, $structure, 'bad results');
    }

    /**
     * @depends testFetchClass
     */
    public function testFetchInto()
    {
        $db = $this->getConnection();
        $resultSet = $db->query('SELECT id,name,price FROM product_test');
        $this->assertNotNull($resultSet, 'a query return null !');
        $this->assertTrue($resultSet instanceof \Jelix\Database\ResultSetInterface, 'resultset is not a ResultSetInterface');

        $obj = new MyProductContainer();
        $t = $obj->token = time();
        $resultSet->setFetchMode(AbstractConnection::FETCH_INTO, $obj);

        $res = $resultSet->fetch();
        $structure = '<object class="MyProductContainer">
        <string property="name" value="camembert" />
        <string property="price" value="2.31" />
        <integer property="token" value="'.$t.'" />
    </object>';
        $this->assertComplexIdenticalStr($res, $structure, 'bad result');

        $res = $resultSet->fetch();
        $structure = '<object class="MyProductContainer">
        <string property="name" value="yaourt" />
        <string property="price" value="0.76" />
        <integer property="token" value="'.$t.'" />
    </object>';
        $this->assertComplexIdenticalStr($res, $structure, 'bad result');

        $res = $resultSet->fetch();
        $structure = '<object class="MyProductContainer">
        <string property="name" value="gloubi-boulga" />
        <string property="price" value="4.9" />
        <integer property="token" value="'.$t.'" />
    </object>';
        $this->assertComplexIdenticalStr($res, $structure, 'bad result');
        $this->assertFalse(!!$resultSet->fetch());
    }

    /**
     * depends testFetchInto
     */
    public function testTransaction()
    {
        //labels_test is an InnoDb table so transaction are supported

        $cnx = $this->getConnection();
        $cnx->exec('DELETE FROM labels_test');

        $this->assertTableIsEmpty('labels_test');
        $cnx->beginTransaction();

        $cnx->exec('INSERT into '.$cnx->encloseName('labels_test')
            .' ('.$cnx->encloseName('key').','
            .$cnx->encloseName('lang').','
            .$cnx->encloseName('label').") values ( 12, 'fr', 'test1')");

        $this->assertTableIsNotEmpty('labels_test');

        $cnx->rollback();
        $this->assertTableIsEmpty('labels_test');

        $cnx->beginTransaction();

        $cnx->exec('INSERT into '.$cnx->encloseName('labels_test')
            .' ('.$cnx->encloseName('key').','
            .$cnx->encloseName('lang').','
            .$cnx->encloseName('label').") values ( 15, 'en', 'test2')");

        $cnx->commit();
        $this->assertTableIsNotEmpty('labels_test');
    }


    /**
     * depends testTransaction
     */
    public function testPreparedQueries()
    {
        $cnx = $this->getConnection();
        $cnx->exec('DELETE FROM labels_test');
        //INSERT
        $stmt = $cnx->prepare('INSERT INTO '.$cnx->encloseName('labels_test')
            .' ('.$cnx->encloseName('key').','
            .$cnx->encloseName('lang').','
            .$cnx->encloseName('label').') VALUES (:k, :lg, :lb)');
        $this->assertInstanceOf($this->recordSetClassName, $stmt);

        $key = 11;
        $lang = 'fr';
        $label = "France";

        if (get_class($this) == "mysqlQueriesTest") {
            // we want to test deprecated values for types used by the mysql connector
            $bind = $stmt->bindParam('lg', $lang, 's');
            $bind = $stmt->bindParam('k', $key, 'i');
            $bind = $stmt->bindParam('lb', $label, 's');
        } else {
            $bind = $stmt->bindParam('lg', $lang, \PDO::PARAM_STR);
            $bind = $stmt->bindParam('k', $key, \PDO::PARAM_INT);
            $bind = $stmt->bindParam('lb', $label, \PDO::PARAM_STR);
        }
        $stmt->execute();

        $key = 15;
        $lang = 'fr';
        $label = "test";
        $bind = $stmt->bindParam('lb', $label, \PDO::PARAM_STR);
        $bind = $stmt->bindParam('k', $key, \PDO::PARAM_INT);
        $bind = $stmt->bindParam('lg', $lang, \PDO::PARAM_STR);
        $this->assertTrue($stmt->execute());

        $bind = $stmt->bindValue('k', 22, \PDO::PARAM_INT);
        $bind = $stmt->bindValue('lg', 'en', \PDO::PARAM_STR);
        $bind = $stmt->bindValue('lb', 'test2', \PDO::PARAM_STR);
        $this->assertTrue($stmt->execute());

        $this->assertTableHasNRecords('labels_test', 3);
        $stmt = null;

        //SELECT
        $stmt2 = $cnx->prepare('SELECT '.$cnx->encloseName('key').','
            .$cnx->encloseName('lang').','
            .$cnx->encloseName('label').' FROM '.$cnx->encloseName('labels_test')
            .' WHERE lang = :la ORDER BY '.$cnx->encloseName('key').' asc');
        $this->assertInstanceOf($this->recordSetClassName, $stmt2);

        $lang = 'fr';
        $bind = $stmt2->bindParam('la', $lang, \PDO::PARAM_STR);
        $this->assertTrue($bind);

        $this->assertTrue($stmt2->execute());
        //$this->assertEquals(2, $stmt2->rowCount());

        $result = $stmt2->fetch();
        $this->assertNotFalse($result);
        $this->assertNotNull($result);
        $this->assertEquals('11', $result->key);
        $this->assertEquals('fr', $result->lang);
        $this->assertEquals('France', $result->label);
    }


    /**
     * depends testPreparedQueries
     */
    /*function testTools(){

        $tools = $this->getConnection()->tools();
        $fields = $tools->getFieldList('products');
        $structure = '<array>
    <object key="id" class="jDbFieldProperties">
        <string property="type" value="int" />
        <string property="name" value="id" />
        <boolean property="notNull" value="true" />
        <boolean property="primary" value="true" />
        <boolean property="autoIncrement" value="true" />
        <boolean property="hasDefault" value="false" />
        <null property="default" />
        <integer property="length" value="0" />
    </object>
    <object key="name" class="jDbFieldProperties">
        <string property="type" value="varchar" />
        <string property="name" value="name" />
        <boolean property="notNull" value="true" />
        <boolean property="primary" value="false" />
        <boolean property="autoIncrement" value="false" />
        <boolean property="hasDefault" value="false" />
        <string property="default" value="" />
        <integer property="length" value="150" />
    </object>
    <object key="price" class="jDbFieldProperties">
        <string property="type" value="float" />
        <string property="name" value="price" />
        <boolean property="notNull" value="false" />
        <boolean property="primary" value="false" />
        <boolean property="autoIncrement" value="false" />
        <boolean property="hasDefault" value="true" />
        <string property="default" value="0" />
        <integer property="length" value="0" />
    </object>
    <object key="promo" class="jDbFieldProperties">
        <string property="type" value="tinyint" />
        <string property="name" value="promo" />
        <boolean property="notNull" value="true" />
        <boolean property="primary" value="false" />
        <boolean property="autoIncrement" value="false" />
        <boolean property="hasDefault" value="false" />
        <string property="default" value="" />
        <integer property="length" value="0" />
    </object>
</array>';
        $this->assertComplexIdenticalStr($fields, $structure, 'bad results');
    }*/


}


class MyProductContainer
{
    public $id;
    public $name;
    public $price;

    public $token;
}