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

    protected $returnFloatType = 'string';
    protected $returnIntType = 'string';

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
        <'.$this->returnFloatType.' property="price" value="2.31" />
    </object>
    <object>
        <string property="name" value="yaourt" />
        <'.$this->returnFloatType.' property="price" value="0.76" />
    </object>
    <object>
        <string property="name" value="gloubi-boulga" />
        <'.$this->returnFloatType.' property="price" value="4.9" />
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
        <'.$this->returnFloatType.' property="price" value="2.31" />
    </object>
    <object class="MyProductContainer">
        <string property="name" value="yaourt" />
        <'.$this->returnFloatType.' property="price" value="0.76" />
    </object>
    <object class="MyProductContainer">
        <string property="name" value="gloubi-boulga" />
        <'.$this->returnFloatType.' property="price" value="4.9" />
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
        <'.$this->returnFloatType.' property="price" value="2.31" />
        <integer property="token" value="'.$t.'" />
    </object>';
        $this->assertComplexIdenticalStr($res, $structure, 'bad result');

        $res = $resultSet->fetch();
        $structure = '<object class="MyProductContainer">
        <string property="name" value="yaourt" />
        <'.$this->returnFloatType.' property="price" value="0.76" />
        <integer property="token" value="'.$t.'" />
    </object>';
        $this->assertComplexIdenticalStr($res, $structure, 'bad result');

        $res = $resultSet->fetch();
        $structure = '<object class="MyProductContainer">
        <string property="name" value="gloubi-boulga" />
        <'.$this->returnFloatType.' property="price" value="4.9" />
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
            .$cnx->encloseName('keyalias').','
            .$cnx->encloseName('lang').','
            .$cnx->encloseName('label').') VALUES (:k, :ka, :lg, :lb)');
        $this->assertInstanceOf($this->recordSetClassName, $stmt);

        $key = 11;
        $lang = 'fr';
        $keyalias= 'alias11';
        $label = "France";

        if (get_class($this) == "mysqlQueriesTest") {
            // we want to test deprecated values for types used by the mysql connector
            $bind = $stmt->bindParam('lg', $lang, 's');
            $bind = $stmt->bindParam('k', $key, 'i');
            $bind = $stmt->bindParam('ka', $keyalias, 'i');
            $bind = $stmt->bindParam('lb', $label, 's');
        } else {
            $bind = $stmt->bindParam('lg', $lang, \PDO::PARAM_STR);
            $bind = $stmt->bindParam('k', $key, \PDO::PARAM_INT);
            $bind = $stmt->bindParam('ka', $keyalias, \PDO::PARAM_STR);
            $bind = $stmt->bindParam('lb', $label, \PDO::PARAM_STR);
        }
        $this->assertTrue($stmt->execute());

        $key = 15;
        $lang = 'fr';
        $keyalias= 'alias15';
        $label = "test";
        $bind = $stmt->bindParam('lb', $label, \PDO::PARAM_STR);
        $bind = $stmt->bindParam('k', $key, \PDO::PARAM_INT);
        $bind = $stmt->bindParam('ka', $keyalias, \PDO::PARAM_STR);
        $bind = $stmt->bindParam('lg', $lang, \PDO::PARAM_STR);
        $this->assertTrue($stmt->execute());

        $bind = $stmt->bindValue('k', 22, \PDO::PARAM_INT);
        $bind = $stmt->bindValue('lg', 'en', \PDO::PARAM_STR);
        $bind = $stmt->bindValue('ka', 'alias22', \PDO::PARAM_STR);
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


    protected $dataLabel = array(
        [ 'key' => 1, 'label'=> 'label1', 'lang'=> ''],
        [ 'key' => 2, 'label'=> 'label2', 'lang'=> ''],
        [ 'key' => 3, 'label'=> 'label3', 'lang'=> ''],
        [ 'key' => 4, 'label'=> 'label4', 'lang'=> ''],
        [ 'key' => 5, 'label'=> 'label5', 'lang'=> ''],
        [ 'key' => 6, 'label'=> 'label6', 'lang'=> ''],
        [ 'key' => 7, 'label'=> 'label7', 'lang'=> ''],
        [ 'key' => 8, 'label'=> 'label8', 'lang'=> ''],
        [ 'key' => 9, 'label'=> 'label9', 'lang'=> ''],
        [ 'key' => 10, 'label'=> 'label10', 'lang'=> ''],
    );

    protected function fillLabelTest() {
        $cnx = $this->getConnection();
        $cnx->exec('DELETE FROM labels_test');

        $this->assertTableIsEmpty('labels_test');

        $stmt = $cnx->prepare('INSERT INTO '.$cnx->encloseName('labels_test')
            .' ('.$cnx->encloseName('key').','
            .$cnx->encloseName('lang').','
            .$cnx->encloseName('keyalias').','
            .$cnx->encloseName('label').') VALUES (:k, :lg, :ka, :lb)');

        foreach ($this->dataLabel as $rec) {
            $bind = $stmt->bindValue('k', $rec['key'], \PDO::PARAM_INT);
            $bind = $stmt->bindValue('ka', 'alias'.$rec['key'], \PDO::PARAM_STR);
            $bind = $stmt->bindValue('lg', $rec['lang'], \PDO::PARAM_STR);
            $bind = $stmt->bindValue('lb', $rec['label'], \PDO::PARAM_STR);
            $this->assertTrue($stmt->execute());
        }
        $this->assertTableHasNRecords('labels_test', 10);
    }



    /**
     * depends testPreparedQueries
     */
    public function testLimitQuery()
    {
        $this->fillLabelTest();

        $cnx = $this->getConnection();
        $sql = 'SELECT '.$cnx->encloseName('key').','.$cnx->encloseName('label')
            .' FROM '.$cnx->encloseName('labels_test')
            .' ORDER BY '.$cnx->encloseName('key').' asc';

        $rs = $cnx->limitQuery($sql, 0, 5);
        $list = $rs->fetchAll();
        $this->assertEquals(5, count($list));
        $this->assertEquals(1, $list[0]->key);
        $this->assertEquals('label1', $list[0]->label);
        $this->assertEquals(2, $list[1]->key);
        $this->assertEquals('label2', $list[1]->label);
        $this->assertEquals(3, $list[2]->key);
        $this->assertEquals('label3', $list[2]->label);
        $this->assertEquals(4, $list[3]->key);
        $this->assertEquals('label4', $list[3]->label);
        $this->assertEquals(5, $list[4]->key);
        $this->assertEquals('label5', $list[4]->label);

        $rs = $cnx->limitQuery($sql, 4, 3);
        $list = $rs->fetchAll();
        $this->assertEquals(3, count($list));
        $this->assertEquals(5, $list[0]->key);
        $this->assertEquals('label5', $list[0]->label);
        $this->assertEquals(6, $list[1]->key);
        $this->assertEquals('label6', $list[1]->label);
        $this->assertEquals(7, $list[2]->key);
        $this->assertEquals('label7', $list[2]->label);

        $rs = $cnx->limitQuery($sql, 8, 6);
        $list = $rs->fetchAll();
        $this->assertEquals(2, count($list));
        $this->assertEquals(9, $list[0]->key);
        $this->assertEquals('label9', $list[0]->label);
        $this->assertEquals(10, $list[1]->key);
        $this->assertEquals('label10', $list[1]->label);

        $rs = $cnx->limitQuery($sql, 5, 0);
        $list = $rs->fetchAll();
        $this->assertEquals(0, count($list));
    }
}


class MyProductContainer
{
    public $id;
    public $name;
    public $price;

    public $token;
}
