
# The Connection object

## Retrieving a Connection object

To access to a database, you must use an object of type `ConnectionInterface`.

This object needs normalized parameters given by `AccessParameters`. Use this object
with parameters you saw in the previous chapter, and give it to `Connection`.
Parameters will be verified and normalized.

You should use the static method `Connection::create()` to retrieve a connection
object. Example:


```php
use \Jelix\Database\AccessParameters;
use \Jelix\Database\Connection;

$parameters = array(
  'driver'=>'sqlite3',
  "database"=>"/src/tests/tests/units/tests.sqlite3",
);

// verify content of parameters and prepare them for the Connection object.
$accessParameters = new AccessParameters($parameters, array('charset'=>'UTF-8'));

// then you can retrieve a connector
$conn = Connection::create($accessParameters);

```

You can put normalized parameters in a cache, so you don't have to use 
AccessParameters at each HTTP requests. You should then use  the method
`Connection::createWithNormalizedParameters()`. It is useful for framework
that are using JelixDatabase.


```php
use \Jelix\Database\AccessParameters;
use \Jelix\Database\Connection;

$parametersInCache = ...; // may contain a boolean indicating that parameters are in a valid cache


if ($parametersInCache) {
    // retrieve your normalized parameters from cache
    $cachedParameters = ...;
    
}
else {
    // no cache, we put parameters in the cache
    $parameters = array(
      'driver'=>'sqlite3',
      "database"=>"/src/tests/tests/units/tests.sqlite3",
    );
    
    // verify content of parameters and prepare them for the Connection object.
    $accessParameters = new AccessParameters($parameters, array('charset'=>'UTF-8'));

    $cachedParameters = $accessParameters->getNormalizedParameters();
    
    // here put normalized parameters in a cache (dummy method here)
    storeInCache($cachedParameters);
}

// let's use directly normalized parameters to retrieve a connection object
$conn = Connection::createWithNormalizedParameters($cachedParameters);
```

## Launching SQL queries

To construct your SQL queries, you have an important method to use if you are
not using prepared statement: `quote()`.
It escapes all reserved characters of the database, and you should use it for
all data you want to insert in your SQL queries. It avoid security issue like
SQL injection. 

However, the best way to launch secured queries, is to use prepared requests. See
the next section.

Another friend of `quote()` is `quote2()` which can be used
on **binary** column.

```php
  $sql = "INSERT INTO users (name,firstname) VALUES";
  $sql .=" (". $conn->quote("Doe") .",".$conn->quote('john').")";
```

Notice that the `quote()` method add quotes at the begin and the end of the 
given string.

To execute queries, you have two methods: `exec()` and `query()`.


exec
----

`exec` should be use for queries which don't return records, like `UPDATE`,
`INSERT`, `DELETE`... This method only returns the number of
updated/inserted/deleted records. Example:

```php
  $conn->exec("INSERT INTO users (name,firstname) VALUES('dupont','toto')");
```

query
-----

`query` should be used for queries which return records : `SELECT`, stored
procedure. The method returns a `ResultSetInterface` object.

Quick example:

```php
  $rs = $conn->query('SELECT name, firstname FROM users');
  $result = '';
  while ($record = $rs->fetch()) {
     $result .= 'name = '.$record->name.' firstname = '.$record->firstname."\n";
  }
```


limitQuery
----------

You can retrieve only some few records, by using the `limitQuery` method:

```php
  $rs = $conn->limitQuery('SELECT name, firstname FROM users', 5, 10);
  $result = '';
  while ($record = $rs->fetch()) {
     $result .= 'name = '.$record->name.' firstname = '.$record->firstname."\n";
  }
```

The first parameter is the query. The second is the number of the first record
to retrieve. And the third parameter is the count of records to retrieve.


## Prepared statement

You can use prepared statement. It is more secured than creating a SQL request
string from variables.

The API is similar to PDO: a method `prepare()` allow to indicate the query.
The query can have named parameters starting with a `:`. And the method
returns an object `ResultSetInterface`.

```php
    $stmt = $conn->prepare('INSERT INTO `labels_test` (`key`,`lang` ,`label`) 
    VALUES (:k, :lg, :lb)');
```

On the returned object, you can indicate the value of each parameters with the
method `bindParam()` or `bindValue()`. 

`bindParam()` is to give the reference to a variable, and `bindValue()` is to give 
directly a PHP value. You have to indicate the type of the variable or 
value to both methods, with one of the `PDO::PARAM_*` constants. By default it 
is `PDO::PARAM_STR`, so the value should be a string.

```php
    $bind = $stmt->bindParam('lg', $lang);
    $bind = $stmt->bindParam('k', $key, PDO::PARAM_INT);
    $bind = $stmt->bindValue('lb', 'hello', PDO::PARAM_STR);
```

Then you execute the query:

```php
    $stmt->execute();
```

If the request returns some results (like a SELECT), you can use `$stmt`
like when calling `query()` on a connection object to retrieve results. See
below.

Because you are using a prepared statement, it is possible to reuse the
`$stmt` object to bind other values and to retrieve corresponding results,
without recalling `prepare()`.

```php
    $stmt = $conn->prepare('INSERT INTO `labels_test` (`key`,`lang` ,`label`) VALUES (:k, :lg, :lb)');

    // we insert a first record
    $bind = $stmt->bindParam('lg', $lang);
    $bind = $stmt->bindParam('k', $key, PDO::PARAM_INT);
    $bind = $stmt->bindValue('lb', 'hello', PDO::PARAM_STR);
    $stmt->execute();

    // we insert a second record, by setting only parameters that have different
    // values
    $bind = $stmt->bindValue('k', 'good.bye', PDO::PARAM_INT);
    $bind = $stmt->bindValue('lb', 'Good Bye', PDO::PARAM_STR);
    $stmt->execute();

```

An alternative to `bindParam()` and `bindValue()`, is to give parameter
values directly to the `execute()` method.

```php
    $stmt = $conn->prepare('INSERT INTO `labels_test` (`key`,`lang` ,`label`) 
        VALUES (:k, :lg, :lb)');

    $stmt->execute(array(
        'lg' => $lang,
        'k' => $key,
        'lb' => 'hello'
    ));
```

Note that the `oci` driver does not support parameters given to `execute()`.

# Result Set

`ResultSetInterface` is the type of object you retrieve after a `SELECT` query (via 
`query()`, `limitQuery()` or `prepare()`).

Its `fetch()` method allows to retrieve the records one by one. Records are always
returns as objects.

Its `fetchAll()` method allows to retrieve all records in a PHP array in one shot.

The result set object implements also the `Iterator` interface, so you can use it in
some case, like in a `foreach` statement.

```php
  $rs = $conn->query('SELECT name, firstname FROM users');
  $result = '';
  foreach ($rs as $record) {
     $result .= 'name = '.$record->name.' firstname = '.$record->firstname."\n";
  } 
```

Returned objects are anonymous object (StdClass in PHP).
If you want to have objects which are based on a specific class, you should indicate
it with the `setFetchMode()` method:

```php
  use \Jelix\Database\ConnectionConstInterface;

  class User {
    ...
  }

  $rs = $conn->query('SELECT name, firstname FROM users');

  $rs->setFetchMode(ConnectionConstInterface::FETCH_CLASS, 'User');
  
  $result = '';
  foreach ($rs as $record) {
     $result .= 'name = '.$record->name.' firstname = '.$record->firstname."\n";
  } 
```

# Transactions

JelixDatabase allows you to execute your queries into transactions. 
Of course, the driver and the database should support this feature.

To start a transaction, you should call the `beginTransaction()` method. Then
you execute your queries. Then you can validate the transaction by calling the
`commit()` method, or you can cancel it by calling the `rollback()` method.

```php
   $conn->beginTransaction();

   try {
        $conn->exec(...);
        $conn->query(...);
        //....
        $conn->commit();
    }
    catch (Exception $e) {
        $conn->rollback();
    }

```

# debugging

If you want to log queries into somewhere, you should call `setQueryLogger` on
the connector. The object should implement the `Jelix\Database\Log\QueryLoggerInterface`.

It may store sql queries somewhere, do statistics etc.

The library provides a such object, `Jelix\Database\Log\QueryLogger`, that can
use a `Psr\Log\LoggerInterface` object.

```php

// an object implementing the QueryLoggerInterface interface.
// you can give a Psr\Log\LoggerInterface object to the constructor
$qd = new \Jelix\Database\Log\QueryLogger();

$conn->setQueryLogger($qd);

$conn->query('...');

echo $qd->getTime()."\n";
echo $qd->getExecutedQuery()."\n";
echo $queryLogger->getFormatedMessage()."\n";
var_export($queryLogger->getTrace());

```
