
# Configuration

Pour pouvoir accéder à une base de données, il faut indiquer les
paramètres de connexion.

## profils

Un profil est un ensemble de paramètres pour accéder à une base de données.
Vous pouvez les stocker où vous voulez dans votre application.

Si vous utilisez JelixDatabase avec le framework Jelix, les profils sont stockés
dans un fichier spécifique, `profiles.ini.php`. Lisez la documentation de Jelix
pour savoir comment l'utiliser.

Au final, un profile est un tableau PHP associatif, comme dans ces exemples :


```php

$profile = array(
    'driver'=>'sqlite3',
    "database"=>"/app/tests/units/tests.sqlite3",
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


Dans un profil, vous voyez un certain nombre de paramètres, dont quelques-un
sont utilisables pour tous les connecteurs :

- `driver` indique le connecteur à utiliser. Le nombre et le nom des autres
  paramètres diffèrent en fonction du connecteur utilisé.
- `table_prefix` : permet d'indiquer un préfixe de nom de table. C'est utilisé 
  principalement par les API Schema. Lors de l'écriture manuelle de requêtes, 
  vous pouvez préfixer vos tables grâce à la méthode `prefixTable()` de 
  l'objet connexion du connecteur.

Profil pour MySQL
-----------------

Paramètres possibles :

- `driver` : doit valoir `"mysqli"` (l'API PHP mysqli est utilisée)
- `database` : le nom de la base de données à utiliser
- `host` : le nom du serveur mysql sur lequel se connecter
- `user` et `password` : le login/mot de passe pour la connexion
- `persistent` : indique si la connexion est persistante (`true`) ou pas
  (`false`)
- `force_encoding` : indique s'il faut spécifier le charset utilisé dans
  l'application, de manière à récupérer les données avec le bon charset. En
  effet, certains serveurs sont configurés par exemple par défaut avec une
  connexion en iso-8859-1, même si les données stockées sont en utf-8. Mettez
  à true si vous voyez que vous n'arrivez pas à afficher les données
  correctement.

Il est aussi possible de configurer l'accés en SSL :

- `ssl` : 0 ou 1. Active ou pas la connexion en SSL
- `ssl_key_pem` : chemin vers la clé privée SSL
- `ssl_cert_pem` : chemin vers le certificat SSL
- `ssl_cacert_pem` : chemin vers le certificat de l'autorité de certification

Profil pour Postgresql 
----------------------

Paramètres possibles :

- `driver` : doit valoir `"pgsql"`.
- `database` : le nom de la base de données à utiliser.
- `host` : le nom du serveur postgresql sur lequel se connecter. Si vous le
  mettez à vide, la connexion se fera via une socket unix.
- `port` : indique le port de connexion. N'indiquez pas ce paramètre si
  vous voulez utiliser le port par défaut.
- `user` et `password` : le login/mot de passe pour la connexion. Ne
  mettez pas ces paramètres si vous voulez utiliser le login/mot de passe par
  défaut (indiqués par exemple dans les variables d'environnement du
  système).
- `service` : nom du service Postgresql. Les paramètres `host`,
  `port`, `user` et `password` sont alors ignorés. `database` est optionnel.
- `persistent` : indique si la connexion est persistante (`true`) ou pas
  (`false`)
- `force_encoding` : indique s'il faut spécifier le charset utilisé dans
  l'application, de manière à récupérer les données avec le bon charset. 
  Voir le profil pour Mysql.
- `timeout` : Nombre de secondes autorisées pour l'établissement de la
  connexion au serveur avant de générer un timeout
- `single_transaction` : Toutes les requêtes d'une même page seront
  envoyées au serveur au sein d'un même transaction (entre un `BEGIN;` et un
  `COMMIT;`) (`true`) ou non (`false`). Défaut : `false`.
- `search_path` : indiquer la liste des schémas dans lequel il faut
  chercher les tables, si le schéma par défaut pour la connexion ne
  correspond pas à celui dans lequel l'application va chercher.
- `session_role`: le role à utiliser dans la session. 

Profil pour SQLite 
------------------

Paramètres possibles :

- `driver` : doit valoir "sqlite3".
- `database` : le nom du fichier de base de données à utiliser.
- `persistent` : indique si la connexion est persistante (`true`) ou pas
  (`false`)
- `extensions`: liste des extensions sqlite à charger, séparées par des virgules
- `busytimeout`: un entier pour le paramètre `busytimeout` de Sqlite
  
Pour le fichier de base de données, vous devez indiquer le chemin complet.
Si vous voulez indiquer un autre type de chemin (avec un prefix de "protocole" par exemple), 
vous pouvez alors indiquer une fonction à l'objet `AccessParameters`, qui 
prendra en paramètre le nom du fichier, et devra renvoyer le chemin complet.

Notez que le dossier et le fichier de base de données sqlite doivent avoir les droits de
lecture/écriture adéquats (ceux du serveur web).


Profil pour PDO 
---------------


Il est possible de spécifier PDO pour se connecter sur une base de donnée dans
un profil mysql, postgresql, etc. Il faut alors ajouter le paramètre
`usepdo=on`. Vous devez avoir alors au moins les paramètres suivants, qui sont
quasiement identiques pour les connecteurs JelixDatabase :

- `driver`: le nom du connecteur PDO à utiliser (`"mysql"`, `"pgsql"`...)
- `host`, `user`, `password` (sauf pour sqlite bien entendu)
- `database` : le nom de la base de donnée. Pour sqlite, le chemin complet du fichier.
- `force_encoding` : indique s'il faut spécifier le charset utilisé dans
  l'application, de manière à récupérer les données avec le bon charset.

JelixDatabase construira le DSN correspondant.

Si vous avez besoin de spécifier un DSN avec des paramètres particuliers, il va
falloir utiliser une autre notation, sans utiliser le paramètre `usepdo` :

- `driver` : doit valoir `"pdo"`
- `dsn` : contient les informations de connexion (type de base de données,
  serveur, nom de la base..). Le format doit être celui attendu par PDO.
- `user` et `password` : le login/mot de passe pour la connexion. Ne
  mettre ces paramètres que si nécessaire.
- `force_encoding`

Exemple:

```php
$profile = array(
    'driver'=>'pdo',
    "dsn"=>"mysql:host=localhost;dbname=test",
    'user'=>'jelix',
    'password'=>'Sup3Rp@Ssw0rD!'
); 

```


Profil pour SQLServer 
----------------------


Paramètres possibles :

- `driver` : doit valoir `"sqlsrv"`
- `database` : le nom de la base de données à utiliser
- `host` : le nom du serveur sur lequel se connecter.
- `port` : indique le port de connexion.
- `user` et `password` : le login/mot de passe pour la connexion.
- `force_encoding` : indique s'il faut spécifier le charset utilisé dans
  l'application, de manière à récupérer les données avec le bon charset. même
  explication que pour mysql.


Profil pour Oracle 
------------------

Paramètres possibles :

- `driver` : doit valoir `"oci"`
- `persistent` : indique si la connexion est persistante (`true`) ou pas
  (`false`)
- `user` et `password` : le login/mot de passe pour la connexion.
- `force_encoding` : indique s'il faut spécifier le charset utilisé dans
  l'application, de manière à récupérer les données avec le bon charset. même
  explication que pour mysql.

Pour indiquer le serveur et la base, soit vous indiquez une chaine de connexion :

- `dsn` : la chaine de connexion reconnu par oracle

Soit vous indiquez les paramètres séparément :

- `database` : le nom de la base de données à utiliser
- `host` : le nom du serveur sur lequel se connecter.
- `port` : indique le port de connexion.
