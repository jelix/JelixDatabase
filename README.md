Lightweight PHP abstraction layer to access and query SQL databases. 

It uses the dedicated PHP API for each database type it supports, not PDO.
The API of connectors and result sets are almost the same as in PDO. There is
an API to manipulate schemas.

It supports Mysql 5.6+, Postgresql 9.6+, Sqlite 3. It supports partially 
(Schema API not fully implemented) SQLServer 2012+ and OCI. There is a connector
using PDO, so you can use other databases (except with the Schema API).

This library has been extracted from the [Jelix](https://jelix.org) framework 1.7,
and has been modernized.

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
  "database"=>"/app/tests/units/tests.sqlite3",
);

// verify content of parameters and prepare them for the Connection object.
$accessParameters = new AccessParameters($parameters, array('charset'=>'UTF-8'));

// then you can retrieve a connector
$db = Connection::create($accessParameters);


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

Full documentation : see the [docs](docs/en/index.md) directory.
