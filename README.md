# Slim API Skeleton

A skeleton project based on Slim Framework 2 to start developing API application instantly.

## NOTE: This is an alpha version and in developmnet process
Contributions are welcomed.

## Implemented
* Application configuration in a single file
* Relational Database support
* Authentication support based on API key with the following user information sources
  * From relational database
* Routing settings in a single file
  * Define an HTTP Method or array of methods
  * Route with parameters
  * Controller class and class method
  * Define conditions to parameters
* Controller classes support
  
## TODO
1. Create a composer project generation configurations
2. Add authentication support based on API key with the following user information sources
  1. From Configuration File
  2. From Big Data/NoSQL records
3. Add authorization support
4. Add logging
5. Add Caching mechanism
6. Auto-doc generator

## Installation
Complete this section

## Usage
Complete this section
### Authentication
The authentication method implemented in this framework is [JWT (JSON Web Tokens)](http://jwt.io/).
A special authentication route has been implemented and is ready to use out of the box.
#### Request
Use the route `/auth` with the method `POST` to authenticate by sending mandatory credentials `username` and `password`, and optional credential `remember_minutes` in the body of the request wrapped in form or JSON formats (both are supported equally).
The request body should be sent as follow:
##### JSON Format

    {
        "username": "john",
        "password": "Snow123",
        "remember_minutes": 1440
    }
    
> Note: JSON format requests must be sent with the `Content-Type: application/json` in the request headers in order to make it work.
    
##### Form Format

    username=john&password=Snow123&remember_minutes=1440
    
##### Request Parameters
 * **`username`** - The username from the `tbl_user` table (See the [Database - User Table](#user-table) section).
 * **`password`** - The username from the `tbl_user` table (See the [Database - User Table](#user-table) section).
 * **`remember_minutes`** - The number of minutes to create a valid token for. This parameter is optional and may not be sent. If this parameter will not be sent, the default value will be used as defined within the ``auth.lifetime`` property located in ``config/parameters.yml`` file.

#### Response
The response depending on the request.
If the authentication route has been requested without different, incorrect or invalid parameters, or without any username-password match in the `tbl_user` table, or user's status is equal or lower than 0, the response will be always 401 as an HTTP status code with the following JSON data in the body:

    {
        "error": true,
        "msg": "Unauthorized access",
        "status": 401
    }

Otherwise, the authentication should succeed with the response status code 200 and response body similar to the following: 

    {
        "error": false,
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ0aW1lIjoxNDM5MDQzMDkxLCJyZW1lbWJlciI6bnVsbCwidXNlciI6eyJpZCI6IjEiLCJ1c2VybmFtZSI6ImVhc3Rlci1lZ2ciLCJyb2xlIjoiQURNSU4iLCJuYW1lIjoiQ29uZ3JhdHMsIE5vdyBZb3UgVW5kZXJzdGFuZCBUaGUgSldUIFByb3RvY29sIiwiZW1haWwiOiJnb29kQGpvYi5jb20iLCJzdGF0dXMiOiIxIiwibGFzdGxvZ2luX3RpbWUiOiIyMDE1LTA4LTA2IDE3OjEwOjA0In19.4YHynX_j2mhXLWGgLTHTf6IgY5HwHBIzl8mUqQa8vUw",
        "status": 200
    }

##### Response Referense
 * **`status`** - The status of the response, mostly is the response status code. Always included in the response.
 * **`error`** - Indicates whether an error has occurred for any possible reason. This will return `true` on error along with `msg` parameter, otherwise will return `false`. Always included in the response.
 * **`msg`** - A string that explains why the error has been occurred. Should always be included if `error` is `true`.
 * **`token`** - A valid JWT authentication token, used for authenticated requests in this API (Usage explained in the [Request - Authenticated Usage](#authenticated-usage) section).

#### Authenticated Usage
**TODO:** Complete this section

## Database 
### User Table

    CREATE TABLE `tbl_user` (
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
