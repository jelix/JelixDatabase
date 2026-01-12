<?php
/**
* @author      Laurent Jouanneau
* @copyright   2009-2026 Laurent Jouanneau
* @link        https://jelix.org
* @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
*/
use Jelix\Database\Utilities;
use \Jelix\Database\Schema;
use \Jelix\Database\Connection;

class sqlSyntaxTest extends \PHPUnit\Framework\TestCase {

    public static function setUpBeforeClass() : void {
    }

    function testEncloseName(){

        $syntax = new \Jelix\Database\Schema\Mysql\SQLSyntaxHelpers();
        $result = $syntax->encloseName('foo');
        $this->assertEquals('`foo`',$result);

        $syntax= new \Jelix\Database\Schema\Postgresql\SQLSyntaxHelpers();
        $result = $syntax->encloseName('foo');
        $this->assertEquals('"foo"',$result);

        $syntax= new \Jelix\Database\Schema\Sqlite\SQLSyntaxHelpers();
        $result = $syntax->encloseName('foo');
        $this->assertEquals('foo',$result);

        $syntax= new \Jelix\Database\Schema\Sqlserver\SQLSyntaxHelpers();
        $result = $syntax->encloseName('foo');
        $this->assertEquals('[foo]',$result);

        $syntax= new \Jelix\Database\Schema\Oci\SQLSyntaxHelpers();
        $result = $syntax->encloseName('foo');
        $this->assertEquals('foo',$result);

    }

    function testSqlType(){

        $syntax = new \Jelix\Database\Schema\Mysql\SQLSyntaxHelpers();
        $this->assertEquals(Connection::DB_TYPE_MYSQL, $syntax->getSQLType());

        $syntax= new \Jelix\Database\Schema\Postgresql\SQLSyntaxHelpers();
        $this->assertEquals(Connection::DB_TYPE_PGSQL, $syntax->getSQLType());

        $syntax= new \Jelix\Database\Schema\Sqlite\SQLSyntaxHelpers();
        $this->assertEquals(Connection::DB_TYPE_SQLITE, $syntax->getSQLType());

        $syntax= new \Jelix\Database\Schema\Sqlserver\SQLSyntaxHelpers();
        $this->assertEquals(Connection::DB_TYPE_SQLSERVER, $syntax->getSQLType());

        $syntax= new \Jelix\Database\Schema\Oci\SQLSyntaxHelpers();
        $this->assertEquals(Connection::DB_TYPE_ORACLE, $syntax->getSQLType());

    }

    function testFloatToStr() {
        $this->assertEquals('12', Utilities::floatToStr(12));
        $this->assertEquals('12.56', Utilities::floatToStr(12.56));
        $this->assertEquals('12', Utilities::floatToStr("12"));
        $this->assertEquals('12.56', Utilities::floatToStr("12.56"));
        $this->assertEquals('65.78E6', Utilities::floatToStr("65.78E6"));
        $this->assertEquals('65780000', Utilities::floatToStr(65.78E6));
        $this->assertEquals('6.5780E83', Utilities::floatToStr(65.78E82));

        // not very good behavior, but this is the behavior in old stable version of jelix
        $this->assertEquals('65', Utilities::floatToStr("65,650.98"));
        $this->assertEquals('12', Utilities::floatToStr("12,589")); // ',' no allowed as decimal separator
        $this->assertEquals('96', Utilities::floatToStr("96 000,98"));

        // some test to detect if the behavior of PHP change
        $this->assertFalse(is_numeric("65,650.98"));
        $this->assertFalse(is_float("65,650.98"));
        $this->assertFalse(is_integer("65,650.98"));
        $this->assertEquals('65', floatval("65,650.98"));
    }

    function testStringToPhpValue(){
    
        $syntax = new Schema\Mysql\SQLSyntaxHelpers();

        try {
            $syntax->stringToPhpValue('int','5', false);
            $this->fail("stringToPhpValue accepts int !!");
        } catch(Exception $e) {
            $this->assertTrue(true);
        }
        try {
            $syntax->stringToPhpValue( 'string','$foo',false);
            $this->fail("stringToPhpValue accepts string !!");
        } catch(Exception $e) {
            $this->assertTrue(true);
        }

        try {
            $syntax->stringToPhpValue( 'autoincrement','5',false);
            $this->fail("stringToPhpValue accepts autoincrement !!");
        } catch(Exception $e) {
            $this->assertTrue(true);
        }

        // with no checknull
        $result = $syntax->stringToPhpValue( 'integer','5',false);
        $this->assertEquals(5,$result);
        $result = $syntax->stringToPhpValue( 'float','5',false);
        $this->assertEquals(5,$result);
        $result = $syntax->stringToPhpValue( 'varchar','$foo',false);
        $this->assertEquals('$foo',$result);
        $result = $syntax->stringToPhpValue('varchar','$f\'oo', false);
        $this->assertEquals('$f\'oo',$result);
        $result = $syntax->stringToPhpValue('double','5.63', false);
        $this->assertEquals(5.63,$result);
        $result = $syntax->stringToPhpValue('float','5.63', false);
        $this->assertEquals(5.63,$result);
        $result = $syntax->stringToPhpValue('float','983298095.631212', false);
        $this->assertEquals(983298095.631212,$result);
        $result = $syntax->stringToPhpValue('numeric','565465465463', false);
        $this->assertEquals('565465465463',$result);
        $result = $syntax->stringToPhpValue('numeric','565469876543139798641315465463', false);
        $this->assertEquals('565469876543139798641315465463',$result);

        // with checknull 
        $result = $syntax->stringToPhpValue('integer','NULL', true);
        $this->assertNull($result);
        $result = $syntax->stringToPhpValue('varchar','NULL', true);
        $this->assertNull($result);
    }


    function testEscapeValue(){
    
        $syntax= new Schema\Mysql\SQLSyntaxHelpers();

        try {
            $syntax->escapeValue('int','5', false);
            $this->fail("escapeValue accepts int !!");
        } catch(Exception $e) {
            $this->assertTrue(true);
        }
        try {
            $syntax->escapeValue( 'string','$foo',false);
            $this->fail("escapeValue accepts string !!");
        } catch(Exception $e) {
            $this->assertTrue(true);
        }

        try {
            $syntax->escapeValue( 'autoincrement','5',false);
            $this->fail("escapeValue accepts autoincrement !!");
        } catch(Exception $e) {
            $this->assertTrue(true);
        }


        // with no checknull
        $result = $syntax->escapeValue( 'integer',5,false);
        $this->assertEquals("5",$result);
        $result = $syntax->escapeValue( 'numeric',598787232098320,false);
        $this->assertEquals("598787232098320",$result);
        $result = $syntax->escapeValue( 'numeric',59878723209832,false);
        $this->assertEquals("59878723209832",$result);
        $result = $syntax->escapeValue( 'numeric',5987872320983,false);
        $this->assertEquals("5987872320983",$result);
        $result = $syntax->escapeValue( 'numeric',598787232098,false);
        $this->assertEquals("598787232098",$result);
        $result = $syntax->escapeValue( 'numeric',59878723209,false);
        $this->assertEquals("59878723209",$result);
        $result = $syntax->escapeValue( 'numeric',5987872320,false);
        $this->assertEquals("5987872320",$result);
        $result = $syntax->escapeValue( 'integer',598787232,false);
        $this->assertEquals("598787232",$result);
        $result = $syntax->escapeValue( 'integer',59878723,false);
        $this->assertEquals("59878723",$result);
        $result = $syntax->escapeValue( 'integer',5987872,false);
        $this->assertEquals("5987872",$result);
        $result = $syntax->escapeValue( 'numeric',598787232098320909,false);
        $this->assertEquals("598787232098320909",$result);
        $result = $syntax->escapeValue( 'numeric',5987872320983209098,false);
        $this->assertEquals("5987872320983209098",$result);
        $result = $syntax->escapeValue( 'numeric',59878723209832090982,false);
        $this->assertEquals("59878723209832087552",$result);
        //$result = $syntax->escapeValue( 'numeric',5987872320983209098238723,false);
        //$this->assertEquals("5987872320983209098238723",$result);
        
        $result = $syntax->escapeValue( 'float',5,false);
        $this->assertEquals("5",$result);
        $result = $syntax->escapeValue( 'varchar','$foo',false);
        $this->assertEquals('\'$foo\'',$result);
        $result = $syntax->escapeValue('varchar','$f\'oo', false);
        $this->assertEquals('\'$f\\\'oo\'',$result);
        $result = $syntax->escapeValue('double',5.63, false);
        $this->assertEquals('5.63',$result);
        $result = $syntax->escapeValue('float', 98084345.637655464, false);
        $this->assertEquals('98084345.6376554', substr($result, 0, 16));
        $result = $syntax->escapeValue('decimal',98084345.637655464, false);
        $this->assertEquals('98084345.6376554',substr($result, 0, 16));
        $result = $syntax->escapeValue('numeric','565465465463', false);
        $this->assertEquals('565465465463',$result);
        $result = $syntax->escapeValue('numeric','565469876543139798641315465463', false);
        $this->assertEquals('565469876543139798641315465463',$result);

        // with checknull 
        $result = $syntax->escapeValue('integer',5, true);
        $this->assertEquals('5',$result);
        $result = $syntax->escapeValue('integer',null, true);
        $this->assertEquals('NULL',$result);
        $result = $syntax->escapeValue('varchar',null, true);
        $this->assertEquals('NULL',$result);
    }



}

