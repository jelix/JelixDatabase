<?php
/**
 * @author      Laurent Jouanneau
 * @contributor Julien Issler
 * @copyright   2007-2020 Laurent Jouanneau
 * @copyright   2009 Julien Issler
 * @link        https://jelix.org
 * @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
 */
require_once(__DIR__.'/queriesTestAbstract.php');

use \Jelix\Database\Connection;


class pgsqlQueriesTest extends queriesTestAbstract {

    protected $connectionInstanceName =  '\\Jelix\\Database\\Connector\\Postgresql\\Connection';
    protected $recordSetClassName = '\\Jelix\\Database\\Connector\\Postgresql\\ResultSet';

    protected static $connectionPgsql = null;

    protected function getConnection()
    {
        if (self::$connectionPgsql === null) {
            $parameters = new \Jelix\Database\AccessParameters(array(
                'driver'=>'pgsql',
                'host'=>'pgsql',
                'user'=>'jelix',
                'password'=>"jelixpass",
                "database"=>"jelixtests"
            ), array('charset'=>'UTF-8'));

            self::$connectionPgsql = Connection::create($parameters);
        }
        return self::$connectionPgsql;
    }

    function testVersion() {

        $cnx = $this->getConnection();
        $version = $cnx->getAttribute($cnx::ATTR_CLIENT_VERSION);
        $this->assertNotEquals('', $version);
    }

    public function testArray()
    {
        $db = $this->getConnection();
        $db->exec('DELETE FROM item_array_text');
        $nb = $db->exec("INSERT INTO item_array_text( 
            mytext, mytext2, myintegers, myintegers2, myintegers3) 
            VALUES(
                   '{\"camembert\",\"chevre\"}',
                   '{\"camembert2\",\"che\\\"vre2\"}',
                   '{24,56}',
                   '{65,98,78}',
                   '{{65,98,78},{954,688,1258}}'
                   ) ");

        $this->assertEquals(1, $nb, 'exec insert 1 should return 1');

        $resultSet = $db->query('SELECT mytext, mytext2, myintegers, myintegers2, myintegers3 FROM item_array_text');
        $this->assertNotNull($resultSet, 'a query return null !');
        $this->assertTrue($resultSet instanceof \Jelix\Database\ResultSetInterface, 'resultset is not a ResultSetInterface');

        $list = array();
        //foreach($resultSet as $res){
        while ($res = $resultSet->fetch()) {
            $list[] = $res;
        }

        $this->assertEquals(1, count($list), 'query return bad number of results ('.count($list).')');
        $this->assertEquals('{camembert,chevre}', $list[0]->mytext);
        $this->assertEquals('{camembert2,"che\"vre2"}', $list[0]->mytext2);
        $this->assertEquals('{24,56}', $list[0]->myintegers);
        $this->assertEquals('{65,98,78}', $list[0]->myintegers2);
        $this->assertEquals('{{65,98,78},{954,688,1258}}', $list[0]->myintegers3);

    }

}
