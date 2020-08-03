JelixDatabase est un système d'accès abstrait aux bases de données. Il
propose une API commune à toutes les bases de données. Pour le moment, les
drivers fournis sont ceux pour :

  * mysql
  * postgresql
  * sqlite
  * oracle (oci)
  * sqlsrv
  * PDO

À noter que bien que JelixDatabase soit une API commune à toutes les bases de données, ce
n'est en aucun cas une classe qui adaptera les requêtes en fonction des bases de
données. Aussi, faites attention à ne pas trop utiliser des spécificités SQL
d'une base de données précise dans votre application, si vous souhaitez que 
l'utilisateur puisse changer de connecteur.

Chapitres :

- [Configuration](configuration.md)
- [Utiliser l'API pour faire des requêtes](requetes.md)
- [Utiliser l'API pour manipuler les schemas](outils.md)

