
# Configuration


To be able to access a database, you need to specify some connection parameters.


## Profiles

A profile is a set of parameter to access to a database. You can store them
where you want into your application.

If you are using JelixDatabase with the Jelix framework, profiles are stored
into a specific files, `profiles.ini.php`. Read the documentation of Jelix to know
how to use it.

Anyway, a profile is at the end a PHP array having access parameters, like in these 
example:

```php

$profile = array(
    'driver'=>'sqlite3',
    "database"=>"/src/tests/tests/units/tests.sqlite3",
);

$profile = array(
    'driver'=>'mysqli',
    "database"=>"jelix",
    'host'=>'localhost',
    'user'=>'jelix',
    'password'=>'Sup3Rp@Ssw0rD!',
    'persistent'=> true,
    'force_encoding'=>true,
); 

```

In a profile, you have several parameters. Their number and name can be different
according to the driver you use, but there are two parameters that can be used for all drivers.

- `driver`: it indicates the connector name. Names and number of parameters depends
  to the connector.
- `table_prefix`: allow to indicate a prefix for all tables. It used mainly
  by the Schema API. This prefix is added (or removed) automatically to the
  table names. When you construct your SQL queries, you should use the `prefixTable()`
  method of the connection object of a driver.
  

MySQL profile
--------------

Possible parameters:

- `driver`: should be `"mysqli"` (it uses the PHP API mysqli)
- `database`: the database name
- `host`: the server name
- `user` et `password`: the login and the password to use for the connection
- `persistent`: boolean saying if the connection should be persistent (`true`) or not (`false`)
- `force_encoding`: says if the current charset should be specified during the
  connection. Try to set it to `true` if you have some encoding issues with your
  retrieved data.

It is also possible to configure an SSL access:

- `ssl`: 0 or 1. Activate or not the SSL connection
- `ssl_key_pem`: path to the private SSL key
- `ssl_cert_pem`: path to the SSL certificat
- `ssl_cacert_pem`: path to the certificat of the certificat authority.

Postgresql profile
------------------

Possible parameters:

- `driver`: should be `"pgsql"`
- `database`: the database name
- `host`: the server name. If you give an empty value, the connection will be 
  set over an unix socket.
- `port`: TCP port to use for the connection. Don't indicate this parameter 
  if you want to use the default port.
- `user` and `password`: the username and the password to use for the connection.
  Don't indicate this parameters if you want to use the default user/password 
  indicated in environment variable in the operating system.
- `service`: the name of the postgresql service (so `host`, `port`, `user` and 
  `password` should not be set; `database` may be set).
- `persistent`: boolean saying if the connection should be persistent (`true`) 
  or not (`false`)
- `force_encoding`: says if the current character set should be specified during 
  the connection. Try to set it to `true` if you have some encoding issues with 
  your retrieved data.
- `timeout`: Number of second allowed before a timeout.
- `single_transaction`: if set to `true`, all queries executed in a same page 
  will be sent in a same transaction (between a `BEGIN;` and a `COMMIT;`). 
  Default: `false`
- `search_path`: the list of schema where table are getting from, if the default 
  schema of the connection doesn't correspond to the schema used by the application.


SQLite profile
---------------

Possible parameters:

- `driver`: should be `"sqlite3"`
- `database`: the database file.
- `persistent`: boolean saying if the connection should be persistent (`true`) or not (`false`)
- `extensions`: list of sqlite extension to load (separated by a coma)
- `busytimeout`: integer for the busytimeout option of Sqlite

For the database file, you should indicate the full path to the file. You can
also indicate any other kind of path (relative, or with a protocol prefix for example),
if you indicate a parser to the `AccessParameters` object, that can return the 
real path from the given path. 

Do not forget that the file and its directory must have read and write permissions for
your webserver user.

PDO profile
------------

You can indicate to use PDO for the connection, by just adding the parameter
`'usepdo' => 'on'`
into a profile.
You should then have at least these parameters:

- `driver`: the PDO driver name
- `host`, `user`, `password` (except for sqlite of course)
- `database`: the database name, or a path for a sqlite database,
- `force_encoding`: says if the current charset should be specified during
  the connection. Try to set it to `true` if you have some encoding issues with
  your retrieved data.

JelixDatabase will build the corresponding DSN.

If you need to specify other parameters into the DSN, you should indicate the
`'pdo'` driver, without using the parameter `usepdo`:

- `driver`: should be `"pdo"`
- `dsn`: contains all parameters for the connection as indicated in
  the PDO documentation on php.net.
- `user` et `password`: the login and the password to use for the connection, if needed.
- `force_encoding`

Example:

```php
$profile = array(
    'driver' => 'pdo',
    'dsn' => "mysql:host=localhost;dbname=test",
    'user' => '...',
    'password' => '...'
);
```

SQLServer profile
-----------------

Possible parameters:

- `driver`: should be `"sqlsrv"`
- `database`: the database name
- `host`: the server name.
- `port`: TCP port to use for the connection.
- `user` and `password`: the username and the password to use for the connection.
- `force_encoding`: says if the current character set should be specified
  during the connection. Try to set it to `true` if you have some encoding
  issues with your retrieved data.


Oracle profile
--------------

Possible parameters:

- `driver`: should be `"oci"`
- `persistent`: boolean saying if the connection should be persistent (`true`) or not (`false`)
- `user` and `password`: the username and the password to use for the connection.
- `force_encoding`: says if the current character set should be specified 
  during the connection. Try to set it to `true` if you have some encoding 
  issues with your retrieved data.

For connection parameters, provide a connection string :

- `dsn`: the connection string

Or separate parameters:

- `database`: the database name
- `host`: the server name.
- `port`: TCP port to use for the connection.

