<?php
/**
* @package     testapp
* @subpackage  jelix_tests module
* @author      Laurent Jouanneau
* @contributor
* @copyright   2007 Laurent Jouanneau
* @link        http://www.jelix.org
* @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
*/

use \Jelix\Database\Connection;

class testmysqlDbTools extends \Jelix\Database\Schema\Mysql\SQLTools {
    public function testParseSQLScript($script) {
        return $this->parseSQLScript($script);
    }
}

class jDbTools_MysqlTest extends \Jelix\UnitTests\UnitTestCaseDb {


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


            self::$connectionMysql = Connection::create($parameters->getParameters());
        }
        return self::$connectionMysql;
    }


    function testExecSqlSimpleScript(){
        $tools = new testmysqlDbTools($this->getConnection());

        $this->assertEquals(array("SELECT toto"), $tools->testParseSQLScript("SELECT toto"));
        $this->assertEquals(array("SELECT toto 'sdsd''mpo;ipi'"), $tools->testParseSQLScript("SELECT toto 'sdsd''mpo;ipi';"));
        $this->assertEquals(array("SELECT toto 'sdsd\'mpo;ipi'"), $tools->testParseSQLScript("SELECT toto 'sdsd\'mpo;ipi';"));
        $this->assertEquals(array("SELECT toto 'mpo;ipi'"), $tools->testParseSQLScript("SELECT toto /*'sdsd'*/'mpo;ipi';"));
        $this->assertEquals(array("SELECT toto 'mpo;ipi'"), $tools->testParseSQLScript("SELECT toto /*'sdsd\npo'*/'mpo;ipi';"));
        $this->assertEquals(array('SELECT toto "sdsd""mpo;ipi"'), $tools->testParseSQLScript('SELECT toto "sdsd""mpo;ipi";'));
        $this->assertEquals(array('SELECT toto "sdsd\"mpo;ipi"'), $tools->testParseSQLScript('SELECT toto "sdsd\"mpo;ipi";'));
        $this->assertEquals(array('SELECT toto "mpo;ipi"'), $tools->testParseSQLScript('SELECT toto /*"sdsd"*/"mpo;ipi";'));
        $this->assertEquals(array('SELECT toto "mpo;ipi"'), $tools->testParseSQLScript('SELECT toto /*"sdsd'."\n".'po"*/"mpo;ipi";'));

        $this->assertEquals(array("SELECT ''", 'SELECT toto'), $tools->testParseSQLScript("SELECT ''; SELECT toto"));
        $this->assertEquals(array('SELECT ""', 'SELECT toto'), $tools->testParseSQLScript('SELECT ""; SELECT toto'));
        $this->assertEquals(array('SELECT ``', 'SELECT toto'), $tools->testParseSQLScript('SELECT ``; SELECT toto'));

    }

    function testExecSqlComplexScript(){
        $tools = new testmysqlDbTools($this->getConnection());

        $sql ="  #oiuou y jgj gj, ; io 
    ALTER TABLE `ticket` ADD `component_id` INT NOT NULL AFTER `component` ; #louou ; poip \"
-- uturutut tut ; gdgd ; 
DROP PROCEDURE IF EXISTS updateComponent; -- oiuoiu  poi ; pi ;
UPDATE ticket SET component_id = cid WHERE component = cname;";
        $result=array(
            "ALTER TABLE `ticket` ADD `component_id` INT NOT NULL AFTER `component`",
            "DROP PROCEDURE IF EXISTS updateComponent",
            "UPDATE ticket SET component_id = cid WHERE component = cname"
        );
        $this->assertEquals($result, $tools->testParseSQLScript($sql));


        $sql =" ALTER TABLE `ticket` ADD `component_id` INT NOT NULL AFTER `component` ;
DROP PROCEDURE IF EXISTS updateComponent;
DELIMITER #
CREATE PROCEDURE updateComponent()
BEGIN
DECLARE done INT DEFAULT 0;
DECLARE cid INT;
DECLARE cname VARCHAR(50);
DECLARE compCur CURSOR FOR SELECT id, name FROM product_component;
DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
OPEN compCur;
REPEAT
  FETCH compCur INTO cid, cname;
  if NOT done THEN
    UPDATE ticket SET component_id = cid WHERE component = cname;
    UPDATE ticket_change SET oldintvalue = cid WHERE oldvalue = cid AND field='component';
    UPDATE ticket_change SET newintvalue = cid WHERE newvalue = cid AND field='component';
  END IF;
UNTIL done END REPEAT;
CLOSE compCur;
END #
DELIMITER ;

CALL updateComponent();";
        $result = array("ALTER TABLE `ticket` ADD `component_id` INT NOT NULL AFTER `component`",
                        "DROP PROCEDURE IF EXISTS updateComponent",
                        "CREATE PROCEDURE updateComponent()
BEGIN
DECLARE done INT DEFAULT 0;
DECLARE cid INT;
DECLARE cname VARCHAR(50);
DECLARE compCur CURSOR FOR SELECT id, name FROM product_component;
DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
OPEN compCur;
REPEAT
  FETCH compCur INTO cid, cname;
  if NOT done THEN
    UPDATE ticket SET component_id = cid WHERE component = cname;
    UPDATE ticket_change SET oldintvalue = cid WHERE oldvalue = cid AND field='component';
    UPDATE ticket_change SET newintvalue = cid WHERE newvalue = cid AND field='component';
  END IF;
UNTIL done END REPEAT;
CLOSE compCur;
END", "CALL updateComponent()");
       $this->assertEquals($result, $tools->testParseSQLScript($sql));

    $sql = "-- phpMyAdmin SQL Dump
-- version 3.3.2deb1
-- http://www.phpmyadmin.net
--
-- Serveur: localhost
-- Généré le : Ven 02 Juillet 2010 à 19:52
-- Version du serveur: 5.1.41
-- Version de PHP: 5.3.2-1ubuntu4.2

SET SQL_MODE=\"NO_AUTO_VALUE_ON_ZERO\";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Base de données: `toto`
--";
    $result = array(
            "SET SQL_MODE=\"NO_AUTO_VALUE_ON_ZERO\"",
        );
        $this->assertEquals($result, $tools->testParseSQLScript($sql));
        //echo '<pre>';var_export($tools->testParseSQLScript($sql));echo '</pre>';
    }
/*
    function testTableList() {
        $tools = new testmysqlDbTools($this->getConnection());

        $goodList = array('jacl_group', 'jacl_right_values', 'jacl_right_values_group',
                          'jacl_rights', 'jacl_subject', 'jacl_user_group',
                          'jacl2_group','jacl2_user_group','jacl2_subject','jacl2_subject_group',
                          'jacl2_rights', 'jlx_user', 'myconfig', 'product_test',
                          'product_tags_test', 'labels_test', 'labels1_test', 'products', 'jlx_cache',
                          'jsessions', 'testkvdb', 'towns',
                          'admin_jacl2_group', 'admin_jacl2_rights', 'admin_jacl2_subject',
                          'admin_jacl2_subject_group', 'admin_jacl2_user_group', 'admin_jlx_user');

        $list = $tools->getTableList();
        sort($goodList);
        sort($list);
        $this->assertEquals($goodList, $list);
    }*/
}
