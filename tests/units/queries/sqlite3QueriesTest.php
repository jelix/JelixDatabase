<?php
/**
 * @author      Laurent Jouanneau
 * @copyright   2006-2020 Laurent Jouanneau
 * @link        https://jelix.org
 * @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
 */

require_once(__DIR__.'/queriesTestAbstract.php');

use \Jelix\Database\Connection;

class sqlite3QueriesTest extends queriesTestAbstract
{
    protected $connectionInstanceName =  '\\Jelix\\Database\\Connector\\SQLite3\\Connection';
    protected $recordSetClassName = '\\Jelix\\Database\\Connector\\SQLite3\\ResultSet';
    protected $returnFloatType = 'float';
    protected $returnIntType = 'int';

    protected static $connectionSqlite = null;

    protected function getConnection()
    {
        if (self::$connectionSqlite === null) {
            $parameters = new \Jelix\Database\AccessParameters(array(
                'driver'=>'sqlite3',
                "database"=>"/src/tests/tests/units/tests.sqlite3"
            ), array('charset'=>'UTF-8'));

            self::$connectionSqlite = Connection::create($parameters);
        }
        return self::$connectionSqlite;
    }


    function testSelectRowCount(){
        $db = $this->getConnection();

        $db->exec("DELETE FROM products");
        $db->exec("INSERT INTO products (id, name, price) VALUES(1,'bateau', 1.23)");
        $db->exec("INSERT INTO products (id, name, price) VALUES(2,'vélo', 2.34)");
        $db->exec("INSERT INTO products (id, name, price) VALUES(3,'auto', 3.45)");

        $res = $db->query("SELECT count(*) as cnt FROM products");
        $rec = $res->fetch();
        $this->assertNotEquals(false, $rec);
        $this->assertEquals(3, $rec->cnt);
        unset($rec);
        unset($res);

        $res = $db->query("SELECT id, name, price FROM products");
        $all = $res->fetchAll();
        $this->assertEquals(3, count($all));
        unset($res);

        $res = $db->query("SELECT id, name, price FROM products");
        $first = $res->fetch();
        $this->assertNotEquals(false, $first);
        $second = $res->fetch();
        $this->assertNotEquals(false, $second);
        $third = $res->fetch();
        $this->assertNotEquals(false, $third);
        $last = $res->fetch();
        $this->assertFalse($last);
        $last = $res->fetch(); // the sqlite driver of jelix doesn't rewind after reaching the end, contrary to the sqlite3 api of php
        $this->assertFalse($last);

        $this->assertEquals(3, $res->rowCount());
        unset($res);

        $res = $db->query("SELECT id, name, price FROM products");
        $first = $res->fetch();
        $this->assertNotEquals(false, $first);
        $this->assertEquals(3, $res->rowCount());
        $all = $res->fetchAll();
        $this->assertEquals(2, count($all));
        unset($res);
    }
}
