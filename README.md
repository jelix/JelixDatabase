Lightweight PHP abstraction layer to access and query SQL databases. 

It supports Mysql, Postgresql, Sqlite. Support of Sql Server and OCI are coming.

This library has been extracted from the [Jelix](https://jelix.org) framework 1.7,
and has been modernized.

The extraction is a work in progress. Schema introspection and support of
OCI and Sql server are coming. 

The API of connectors and resultset are almost the same as PDO.

# installation

You can install it from Composer. In your project:

```
composer require "jelix/database"
```

# Usage

Quick start:

```php
use \Jelix\Database\AccessParameters;
use \Jelix\Database\Connection;

// parameters to access to a database. they can come from a configuration file or else..
$parameters = array(
  'driver'=>'sqlite3',
  "database"=>"/src/tests/tests/units/tests.sqlite3",
);

// verify content of parameters and prepare them for the Connection object.
$accessParameters = new AccessParameters($parameters, array('charset'=>'UTF-8'));
$trustedParameters = $accessParameters->getParameters();

// optionally, you can store the content of $trustedParameters in a secured// 
// cache manager , so it is not needed to use AccessParameter at each
// HTTP requests.

// then you can retrieve a connector
$db = Connection::create($trustedParameters);


// let's insert some values
$insertSql = "INSERT INTO ".$db->encloseName('myValues')." (
     ".$db->encloseName('id'). ",
     ".$db->encloseName('value')."
      ) VALUES ";

$value = 'foo';

// insert one value with a classical query
$db->exec($insertSql." (1, ".$db->quote($value).")");

// insert one value with a prepared query
$stmt = $db->prepare($insertSql."(:id, :val)");
$stmt->bindValue('id', 2, \PDO::PARAM_INT);
$myVar = 'bar';
$stmt->bindParam('value', $myVar, \PDO::PARAM_STR);
$stmt->execute();

// retrieve all records
$resultSet = $db->query("SELECT id, value FROM myValues");

// records are always objects
foreach ($resultSet as $record) {
    echo "id=".$record->id."\n";
    echo "value=".$record->value."\n";
}

```