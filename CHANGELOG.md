Changelog
=========

Version 1.2.1
--------------

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
