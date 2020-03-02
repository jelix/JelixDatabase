Testing JelixDatabase
======================

A docker configuration is provided to launch tests into a container.
You must install Docker on your machine first. Then you should execute
the run-docker script.

To launch containers the first time:

```
./run-docker build
```

Then:

```
./run-docker 
# or to not run as daemon:
./run-docker up
```

Credentials to access to postgresql:

- host: `localhost`
- port: 8521
- database: `jelixtests`
- user: `jelix`
- password: `jelixpass`

Credentials to access to mysql:

- host: `localhost`
- port: 8522
- database: `jelixtests`
- user: `jelix`
- password: `jelixpass`

To stop containers:

```
./run-docker stop 
```

Before stopping containers, you may have to close connections to the postgresql
and mysql database if you are using Pgadmin for example. 

You can execute some commands into the php container, by using this command:

```
./app-ctl <command>
```

Available commands:

* `reset`: to reinitialize database contents. Use it before to launch unit tests the first time. 
* `composer-update` and `composer-install`: to update PHP packages 
* `shell` and `shellroot` : to enter into the php container

To launch tests: execute `./app-ctl unit-tests`.
