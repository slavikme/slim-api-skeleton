# Slim API Skeleton

A skeleton project based on Slim Framework 2 to start developing API application instantly.

## NOTE: This is an alpha version and in developmnet process
Contributions are welcomed.

## Implemented
* Application configuration in a single file
* Relational Database support
* Authentication support based on API key with the following user information sources
..* From relational database
* Routing settings in a single file
..* Define an HTTP Method or array of methods
..* Route with parameters
..* Controller class and class method
..* Define conditions to parameters
* Controller classes support
  
## TODO
1. Create a composer project generation configurations
2. Add authentication support based on API key with the following user information sources
..1. From Configuration File
..2. From Big Data/NoSQL records
3. Add authorization support
4. Add logging
5. Add Caching mechanism
6. Auto-doc generator

## Installation
Complete this section

## Usage
Complete this section

## Database 
### User Table

    CREATE TABLE `extui_user` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `username` varchar(50) NOT NULL,
        `password` varchar(255) NOT NULL,
        `role` varchar(50) NOT NULL,
        `name` varchar(255) DEFAULT NULL,
        `email` varchar(255) DEFAULT NULL,
        `create_time` datetime DEFAULT NULL,
        `update_time` datetime DEFAULT NULL,
        `status` tinyint(4) NOT NULL DEFAULT '1',
        `lastlogin_time` datetime DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `username_UNIQUE` (`username`)
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
