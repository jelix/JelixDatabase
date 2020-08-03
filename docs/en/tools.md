
# Managing schemas

The interface `SchemaInterface` allow you to manipulate the structure of the database.
It have some methods to create a table, to retrieve the structure of a table,
to add/modify/remove columns etc.

You can retrieve an object of this type by calling the method
`schema()` of a `ConnectionInterface` object.

Example:


```php
use Jelix\Database\Schema\SchemaInterface;
use Jelix\Database\Schema\TableInterface;
use Jelix\Database\Schema\Column;

/** @var SchemaInterface $schema */
$schema = $conn->schema();


// create a table

$columns = [
    new Column('id', 'autoincrement'),
    new Column('label', 'varchar', 255),
];

$productTable = $schema->createTable('products', $columns, 'id');

// reads a table
/** @var TableInterface $productTable */
$productTable = $schema->getTable('products');

// adds a column
$textCol = new Column('description', 'text');
$productTable->addColumn($textCol);

// change a column
$labelCol = $productTable->getColumn('label', true);
$labelCol->length = 150;
$labelCol->notNull = false;
$productTable->alterColumn($labelCol);

```



# Executing an SQL script

If you want to execute a SQL script containing several queries, you can call
the `execSQLScript()` method of the object `SqlToolsInterface`. You retrieve this
object by calling the method `tools()` of a connection object.

```php
  $conn->tools()->execSQLScript('/path/to/a/script.sql');
```

In order to use the table prefix indicated in the profile, it is strongly
recommended to use the `%%PREFIX%%` tag before each name of tables. It will be
replaced by the prefix (or by nothing if there is no prefix).

```sql
UPDATE %%PREFIX%%product.....;
INSERT .....;
```


# Helpers

`Helpers` is a class providing useful methods.

Example:

```php
use Jelix\Database\Helpers;

$helpers = new Helpers($conn);

$record = $helpers->fetchFirst("SELECT name, first_name FROM user");

$liste = $helpers->fetchAll("SELECT name, first_name FROM user");

```
