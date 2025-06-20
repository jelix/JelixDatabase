<?php
/**
* @package     testapp
* @subpackage  jelix_tests module
* @author      Laurent Jouanneau
* @contributor
* @copyright   2015-2022 Laurent Jouanneau
* @link        http://www.jelix.org
* @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
*/


class queryparseConnection extends \Jelix\Database\AbstractConnection {
    function __construct($profile) { }
    function parseQuery($sql, $reParam) {
        return $this->findParameters($sql, $reParam);
    }

     public function beginTransaction () {}
     public function commit () {}
     public function rollback () {}
     public function prepare ($query, $driverOptions = []) {}
     public function errorInfo() {}
     public function errorCode() {}
     public function lastInsertId($fromSequence='') {}
     public function getAttribute($id) {}
     public function setAttribute($id, $value) {}
     protected function _autoCommitNotify ($state) {}
     protected function _connect () {}
     protected function _disconnect () {}
     protected function _doQuery ($queryString) {}
     protected function _doExec ($queryString) {}
    protected function _doLimitQuery ($queryString, $offset, $number) {}
    protected function _getSchema () {}

    public function createTableName(string $name): \Jelix\Database\Schema\TableNameInterface
    {
        return new \Jelix\Database\Schema\Sqlite\TableName($name);
    }
}



class queryParseTest  extends \Jelix\UnitTests\UnitTestCaseDb
{

    protected function getConnection() {
        return new queryparseConnection('');
    }

    function testQuestionMarkerInResult() {
        $cn = new queryparseConnection('');

        $res = $cn->parseQuery('select * from c WHERE id= :id and foo= :bar','?');
        $this->assertEquals('select * from c WHERE id= ? and foo= ?', $res[0]);
        $this->assertEquals(array('id','bar'), $res[1]);

        $res = $cn->parseQuery('select * from c WHERE id= :id and foo= :bar and u=:id','?');
        $this->assertEquals('select * from c WHERE id= ? and foo= ? and u=?', $res[0]);
        $this->assertEquals(array('id','bar', 'id'), $res[1]);

        $res = $cn->parseQuery('select * from c WHERE id= :id and t = ":popo" AND foo= :bar','?');
        $this->assertEquals('select * from c WHERE id= ? and t = ":popo" AND foo= ?', $res[0]);
        $this->assertEquals(array('id','bar'), $res[1]);

        $res = $cn->parseQuery('select * from c WHERE id= :id and
                               t = ":po\\"p\'o" AND u=\'\\\' :ui zer\'
                               foo= :bar2','?');
        $this->assertEquals('select * from c WHERE id= ? and
                               t = ":po\\"p\'o" AND u=\'\\\' :ui zer\'
                               foo= ?', $res[0]);
        $this->assertEquals(array('id','bar2'), $res[1]);

    }

    function testNumericalMarkerInResult() {
        $cn = new queryparseConnection('');

        $res = $cn->parseQuery('select * from c WHERE id= :id and foo= :bar','$%');
        $this->assertEquals('select * from c WHERE id= $1 and foo= $2', $res[0]);
        $this->assertEquals(array('id','bar'), $res[1]);

        $res = $cn->parseQuery('select * from c WHERE id= :id and foo= :bar and u=:id','$%');
        $this->assertEquals('select * from c WHERE id= $1 and foo= $2 and u=$1', $res[0]);
        $this->assertEquals(array('id','bar'), $res[1]);

        $res = $cn->parseQuery('select * from c WHERE id= :id and t = ":popo" AND foo= :bar','$%');
        $this->assertEquals('select * from c WHERE id= $1 and t = ":popo" AND foo= $2', $res[0]);
        $this->assertEquals(array('id','bar'), $res[1]);

        $res = $cn->parseQuery('select * from c WHERE id= :id and
                               t = ":po\\"p\'o" AND u=\'\\\' :ui zer\'
                               foo= :bar2','$%');
        $this->assertEquals('select * from c WHERE id= $1 and
                               t = ":po\\"p\'o" AND u=\'\\\' :ui zer\'
                               foo= $2', $res[0]);
        $this->assertEquals(array('id','bar2'), $res[1]);

    }

    function testNumericalMarkerInQuery() {
        $cn = new queryparseConnection('');

        $res = $cn->parseQuery('select * from c WHERE id= $1 and foo= $2','$%');
        $this->assertEquals('select * from c WHERE id= $1 and foo= $2', $res[0]);
        $this->assertEquals(array('1','2'), $res[1]);

        $res = $cn->parseQuery('select * from c WHERE id= $1 and foo= $2 and u=$1','$%');
        $this->assertEquals('select * from c WHERE id= $1 and foo= $2 and u=$1', $res[0]);
        $this->assertEquals(array('1','2'), $res[1]);

        $res = $cn->parseQuery('select * from c WHERE id= $1 and t = ":popo" AND foo= $2','$%');
        $this->assertEquals('select * from c WHERE id= $1 and t = ":popo" AND foo= $2', $res[0]);
        $this->assertEquals(array('1','2'), $res[1]);

        $res = $cn->parseQuery('select * from c WHERE id= $1 and t = "$99" AND foo= $2','$%');
        $this->assertEquals('select * from c WHERE id= $1 and t = "$99" AND foo= $2', $res[0]);
        $this->assertEquals(array('1','2'), $res[1]);
    }

}
