Changelog
=========

Version 2.0.0 (not released yet)
--------------------------------

**API CHANGES**:
- new interface `QueryLoggerInterface` to log queries

Connectors and Connection methods do not accept anymore a `Psr\Log\LoggerInterface`.
Instead a new object interface is provided, `Jelix\Database\Log\QueryLoggerInterface`. It brings more flexibility
about what to do between and after queries.

An object `Jelix\Database\Log\QueryLogger` is provided, implementing this
interface, and can use a `Psr\Log\LoggerInterface` object.


1.4.0-pre
---------

- Support of schema names in the API that query and manipulate the structure of the database. It is mainly used 
  internaly into objects of the object of the `Jelix\Database\Schema` namespace.
  - supported only for databases having schemas: Postgresql and SqlServer. Schema names are ignored for other databases.  
  - Onto object implementing `TableInterface` : new method `getTableName()` allowing to access to the schema name, the table name.
  - On connection objects, new methods `createTableName()` and `getDefaultSchemaName()`.
- Support of generated column in the PostgreSQL adapter. 
- Support of Identity column for Postgresql
- Support of JSON fields into tools
- Brings a plugin for JelixProfiles
- Introduce compatibility with application that used jDb API of Jelix 1.8 and lower: classes of JelixDatabase inherit
  from some empty classes or empty interfaces having the name of old implementation, so objects can be passed to 
  functions that have parameters typed with theses classes (`jDbConnection`, `jDbPDOConnection`, `jDbResultSet`, 
  `jDbPDOResultSet`, `jDbParameters`, `jDbTools`, `jDbSchema`, `jDbWidget`). This feature will be removed into the
  next major version of JelixDatabase.

Version 1.3.2
-------------

- Fix Sqlite3 jDb driver: it must not free results if connection is already closed

Version 1.3.1
-------------

- Fix deprecation and warning with PHP 8.2 and 8.3

Version 1.3.0
--------------

- New option `sslmode` for pgsql to encrypt connections
- New option `force_new` for pgsql to force a new connection
- Fix pgsql schema: should list only tables from the search_path
- tests: new docker image
- compatible with PHP 8.3


Version 1.2.4
-------------

* Fix some issues with PHP 8.2

Version 1.2.3
-------------

- Fix AccessParameters: must not generate pdooptions with bad values

Version 1.2.2
-------------

- Fix prepared queries: support of placeholders $1, $2 etc
- Fix some issues with PHP 8.1
- New option for pgsql to set session role

Version 1.2.1
-------------

- new method `ConnectionInterface::close()`
- Fix the support of query parameters given to the `execute` method of mysql and postgresql connectors
- Fix the parsing of query parameters: `::something` should not be read as parameter

Version 1.2.0
--------------

- new method `ConnectionInterface::getConnectionCharset()`
- new methods `ResultSetInterface::fetchAssociative()` and `ResultSetInterface::fetchAllAssociative()`
- new methods `AbstractConnection::getLastQuery()` and `AbstractConnection::getDriverName()` in replacement of some public properties
- new method `ResultSetInterface::free()`
- Add some deprecated methods to be compatible with jDb:
  - `AccessParameters::getParameters()`
  - `AbstractSchema::_prepareSqlColumn()`

Version 1.1.1
-------------

- fix `Mysqli\Connection::execMulti()`: it should not fail silently

Version 1.1.0
-------------

- Fix compatibility with PHP 8. The PDO connector has been rewrite to no
  have classes inheriting from PDO classes, else it would not be possible to
  keep compatibility with PHP 7.
- Pgsql tools : new methods to parse and to generate pgsql array values :
  `SQLTools::decodeArrayValue()` and `SQLTools::encodeArrayValue()`. Call these
  methods after reading values from SQL or before using values into a query. 
  Also new method `Column::isArray()`.
- Mysql schema: fix support of integer display size.
  Some version of Mysql or PHP drivers return the display size
  with the `INT` type, like `INT(11)`. The number should be ignored,
  as it is not a precision value.

Version 1.0.0
-------------

Initial import of the API from the Jelix framework 1.7, with class names changed
(with namespaces), API change in how a connection is instantiated, and no more 
"jelix plugins" for connectors.
