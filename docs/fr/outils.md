

# Manipuler les schémas

L'interface `SchemaInterface` permet de manipuler la structure d'une 
base de donnée.
Elle possède ainsi des méthodes pour créer une table, récupérer la structure
d'une table, ajouter/modifier/supprimer des colonnes etc. 

Vous pouvez récupérer un objet de type `SchemaInterface` en appelant la méthode
`schema()` d'un objet `ConnectionInterface`.

Exemple :

```php
use Jelix\Database\Schema\SchemaInterface;
use Jelix\Database\Schema\TableInterface;
use Jelix\Database\Schema\Column;

/** @var SchemaInterface $schema */
$schema = $conn->schema();


// créer une table

$columns = [
    new Column('id', 'autoincrement'),
    new Column('label', 'varchar', 255),
];

$productTable = $schema->createTable('products', $columns, 'id');


// lit une table

/** @var TableInterface $productTable */
$productTable = $schema->getTable('products');

// ajoute une column
$textCol = new Column('description', 'text');
$productTable->addColumn($textCol);

// change a column
$labelCol = $productTable->getColumn('label', true);
$labelCol->length = 150;
$labelCol->notNull = false;
$productTable->alterColumn($labelCol);

```



# Exécuter un script SQL

Il est possible d'exécuter un script SQL contenant plusieurs requêtes SQL. Il
suffit de passer le chemin du fichier du script à la méthode
`execSQLScript()` d'un objet `SqlToolsInterface`, objet que l'on récupère via la
méthode `tools()` d'un objet de connexion.

```php
  $conn->tools()->execSQLScript('/chemin/vers/un/script.sql');
```

Pour tenir compte de la possibilité de préfixer les tables via la configuration
d'un profil, il est fortement recommander de précéder tous les noms de tables
par `%%PREFIX%%`, cela sera remplacé par le préfixe de table (si il y en a
un), avant l'exécution du script.

```sql
UPDATE %%PREFIX%%product.....;
INSERT .....;
```

# Helpers

`Helpers` est une classe fournissant des méthodes utiles. 


Exemple :

```php

use Jelix\Database\Helpers;

$helpers = new Helpers($conn);

$record = $helpers->fetchFirst("SELECT nom, prenom FROM user");

$liste = $helpers->fetchAll("SELECT nom, prenom FROM user");

```

