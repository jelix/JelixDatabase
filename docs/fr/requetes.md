

# L'objet Connection 

## Instancier une connexion

Pour accéder à une base de données, vous devez utiliser un objet de type `ConnectionInterface`.

Cet objet a besoin de paramètres normalisés pour la connexion, donnés par l'objet 
`AccessParameters`. Utilisez cet objet avec les paramètres que vous avez vu 
au chapitre précédent, il les vérifiera et normalisera leurs valeurs.

Vous utiliserez la méthode statique `Connection::create()` pour récupérer
un object de type `Connection`. Exemple :

```php
use \Jelix\Database\AccessParameters;
use \Jelix\Database\Connection;

$parameters = array(
  'driver'=>'sqlite3',
  "database"=>"/src/tests/tests/units/tests.sqlite3",
);

// vérification des parameters et préparation pour l'objet Connection
$accessParameters = new AccessParameters($parameters, array('charset'=>'UTF-8'));

// récupération d'un object de connexion
$conn = Connection::create($accessParameters);

```

Vous pouvez mettre en cache les paramètres normalisés, afin d'éviter d'utiliser
l'objet AccessParameters à chaque requête HTTP, et utiliser alors la méthode 
`Connection::createWithNormalizedParameters()`. Cela est surtout utile pour 
les frameworks qui veulent utiliser JelixDatabase.


```php
use \Jelix\Database\AccessParameters;
use \Jelix\Database\Connection;

$parametersInCache = ...; // peut contenir un boolean indiquant si les parametres sont en cache ou non


if ($parametersInCache) {
    // récupération des paramètres à partir du cache
    $cachedParameters = ...;
    
}
else {
    // pas de cache, on met les paramètres en cache
    $parameters = array(
      'driver'=>'sqlite3',
      "database"=>"/src/tests/tests/units/tests.sqlite3",
    );
    
    $accessParameters = new AccessParameters($parameters, array('charset'=>'UTF-8'));
    // récupération des paramètres normalisés
    $cachedParameters = $accessParameters->getNormalizedParameters();
    
    // que l'on stocke dans un cache (méthode fictive)
    storeInCache($cachedParameters);
}

// on utilise les paramètres normalisés pour récupérer l'objet connexion
$conn = Connection::createWithNormalizedParameters($cachedParameters);

```

## Lancer des requêtes SQL

Pour construire les requêtes, vous avez une méthode importante à connaître si vous
n'utilisez pas les requêtes préparées : `quote()`, qui permet d'échapper
certains caractères dans les valeurs que vous voulez insérer dans vos requêtes.
Elle évite dans une certaine mesure les problèmes comme l'injection SQL.

La meilleur façon de faire reste toutefois de faire des requêtes préparées,
voir la section suivante.

Un autre ami de `quote()` est `quote2()` utilisé sur les colonnes **binaires**
et s'utilise de la même façon que `quote()`.

```php
  $sql = "INSERT INTO users (nom,prenom) VALUES";
  $sql .=" (". $cnx->quote("de l'ombre") .",".$cnx->quote('robert').")";
```

Notez que la méthode `quote()` encadre la valeur avec des quotes.

Pour exécuter des requêtes, il y a principalement deux méthodes, `exec()` et `query()`.


exec
----

`exec` doit être utilisé pour les requêtes qui ne renvoient pas de résultat
`UPDATE`, `INSERT`, `DELETE`... Dette méthode renvoie juste le nombre de lignes
concernées par la requête. Exemple :

```php
  $conn->exec("INSERT INTO users (nom,prenom) VALUES('dupont','toto')");
```

query
-----

`query` est fait pour les requêtes qui renvoient des résultats, vides ou pas
(`SELECT` ou procédures stockées). La méthode renvoie alors un objet
`ResultSetInterface`.

Voici un exemple rapide :

```php
  $rs = $conn->query('SELECT nom, prenom FROM users');
  $result = '';
  while ($record = $rs->fetch()) {
     $result .= 'nom = '.$record->nom.' prenom = '.$record->prenom."\n";
  }
```


limitQuery
----------

Vous pouvez faire des requêtes qui récupèrent un nombre limité
d'enregistrements. Vous utiliserez alors la méthode `limitQuery` :

```php
  $rs = $conn->limitQuery('SELECT nom, prenom FROM users', 5, 10);
  $result = '';
  while ($record = $rs->fetch()) {
     $result .= 'nom = '.$record->nom.' prenom = '.$record->prenom."\n";
  }
```

Le premier paramètre est la requête. Le deuxième est le numéro, dans la liste
des résultats, du premier enregistrement à récupérer. Le troisième paramètre est
le nombre d'enregistrements à récupérer.

## Requêtes préparées

Vous pouvez utiliser des requêtes préparées. C'est d'ailleurs plus sécurisé
que d'injecter directement des valeurs dans une chaîne SQL. Tous les drivers
fournis avec Jelix supportent les requêtes préparées.

L'API est similaire à PDO : une méthode `prepare()` permet d'indiquer
la requête. Celle-ci peut avoir des paramètres nommés, commençant par `:`.
La méthode renvoi un objet de type `ResultSetInterface`.

```php
    $stmt = $conn->prepare('INSERT INTO `labels_test` (`key`,`lang` ,`label`) 
    VALUES (:k, :lg, :lb)');
```

Sur cet objet `ResultSetInterface`, on peut indiquer la valeur de chaque paramètre
avec la méthode `bindParam()` ou `bindValue()`. 

`bindParam()` permet de donner une référence à une variable, tandis que `bindValue()` 
permet d'indiquer directement une valeur PHP. Vous devez indiquer à ces deux 
methodes, le type de la valeur ou de la variable, avec l'une des constantes 
`PDO::PARAM_*`. Par défaut, c'est `PDO::PARAM_STR`, donc la valeur doit être 
une chaîne.

```php
    $bind = $stmt->bindParam('lg', $lang);
    $bind = $stmt->bindParam('k', $key, PDO::PARAM_INT);
    $bind = $stmt->bindValue('lb', 'hello', PDO::PARAM_STR);
```

Et ensuite, on lance l'execution de la requête :

```php
    $stmt->execute();
```

Si la requête retourne des résultats, comme un `SELECT`, on peut se servir de 
`$stmt` comme un `ResultSetInterface` classique, voir la section suivante.

Il est possible de relancer l'exécution avec d'autres valeurs pour les
paramètres, autant de fois que l'on veut, sans avoir à ré-éxecuter `prepare()`.

```php
    $stmt = $conn->prepare('INSERT INTO `labels_test` (`key`,`lang` ,`label`) VALUES (:k, :lg, :lb)');

    // on insère un premier enregistrement
    $bind = $stmt->bindParam('lg', $lang);
    $bind = $stmt->bindParam('k', $key, PDO::PARAM_INT);
    $bind = $stmt->bindValue('lb', 'hello', PDO::PARAM_STR);
    $stmt->execute();

    // on insère un deuxième enregistrement, en modifiant juste les paramètres
    // qui changent
    $bind = $stmt->bindValue('k', 'good.bye', PDO::PARAM_INT);
    $bind = $stmt->bindValue('lb', 'Good Bye', PDO::PARAM_STR);
    $stmt->execute();

```

Une alternative à l'utilisation de `bindParam()` et `bindValue()`, est de
donner les valeurs directement à la méthode `execute()`.

```php
    $stmt = $conn->prepare('INSERT INTO `labels_test` (`key`,`lang` ,`label`) 
        VALUES (:k, :lg, :lb)');

    $stmt->execute(array(
        'lg' => $lang,
        'k' => $key,
        'lb' => 'hello'
    ));
```

Notez que le driver `oci` ne prend pas en charge les paramètres donnés à `execute()`.

# Liste de résultat

`ResultSetInterface` est le type d'objet que vous récupérez après avoir fait un 
`SELECT` (via `query()`, `limitQuery()` ou `prepare()`).

Sa méthode `fetch()` vous permet de récupérer un à un les enregistrements. À noter
que les enregistrements sont toujours renvoyés sous forme d'objet. 

Sa méthode `fetchAll()` permet de récupérer tous les résultats en même temps
dans un tableau PHP.

L'objet resultset  implémente l'interface `Iterator`. De ce fait, vous pouvez utiliser
cet objet dans certaines boucles, comme les `foreach` :

```php
  $rs = $cnx->query('SELECT nom, prenom FROM users');
  $result = '';
  foreach ($rs as $record) {
     $result .= 'nom = '.$record->nom.' prenom = '.$record->prenom."\n";
  } 
```

Les objets contenant les enregistrements sont des objets "anonymes" (ils n'ont
pas de classe précise). Si vous voulez que ce soient des objets d'une certaine
classe, vous devez l'indiquer via `setFetchMode()` :


```php
  class User {
    ...
  }

  $rs = $conn->query('SELECT nom, prenom FROM users');

  $rs->setFetchMode(jDbConnection::FETCH_CLASS, 'User');

  $result = '';
  foreach ($rs as $record) {
     // $record est ici un objet de type User
     $result .= 'nom = '.$record->nom.' prenom = '.$record->prenom."\n";
  } 
```

Pour le reste des méthodes, voyez [[refapi:jDbResultSet|la documentation de référence]].


# Transactions

JelixDatabase permet de faire des transactions. Bien sûr, il faut que le driver 
utilisé supporte les transactions.

Pour marquer le début d'une transaction, vous appellerez la méthode
`beginTransaction()`. Ensuite vous lancerez les requêtes, Puis après avoir
fait vos requêtes, vous pourrez valider la transaction en appelant la méthode
`commit()`. Pour annuler une transaction, il suffit d'appeler `rollback()`.

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
