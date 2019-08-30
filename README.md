[![Latest Stable Version](https://poser.pugx.org/mouf/magic-query/v/stable)](https://packagist.org/packages/mouf/magic-query)
[![Latest Unstable Version](https://poser.pugx.org/mouf/magic-query/v/unstable)](https://packagist.org/packages/mouf/magic-query)
[![License](https://poser.pugx.org/mouf/magic-query/license)](https://packagist.org/packages/mouf/magic-query)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/thecodingmachine/magic-query/badges/quality-score.png?b=1.2)](https://scrutinizer-ci.com/g/thecodingmachine/magic-query/?branch=1.2)
[![Build Status](https://travis-ci.org/thecodingmachine/magic-query.svg?branch=1.2)](https://travis-ci.org/thecodingmachine/magic-query)
[![Coverage Status](https://coveralls.io/repos/thecodingmachine/magic-query/badge.svg?branch=1.2)](https://coveralls.io/r/thecodingmachine/magic-query?branch=1.2)

What is Magic-query?
====================

Magic-query is a PHP library that helps you work with complex SQL queries.

It comes with 3 great features:

- [**MagicParameters**: it helps you work with SQL queries that require a variable number of parameters.](#parameters)
- [**MagicJoin**: it writes JOINs for you!](#joins)
- [**MagicTwig**: use Twig templating in your SQL queries](#twig)

Installation
------------

Simply use the composer package:

```json
{
	"require": {
		"mouf/magic-query": "^1.2"
	},
	"minimum-stability": "dev",
	"prefer-stable": true
}
```

<a name="parameters"></a>
Automatically discard unused parameters with MagicParameters
------------------------------------------------------------

Just write the query with all possible parameters.

```php
use Mouf\Database\MagicQuery;

$sql = "SELECT * FROM users WHERE name LIKE :name AND country LIKE :country";

// Get a MagicQuery object.
$magicQuery = new MagicQuery();

// Let's pass only the "name" parameter
$result = $magicQuery->build($sql, [ "name" => "%John%" ]);
// $result = SELECT * FROM users WHERE name LIKE '%John%'
// Did you notice how the bit about the country simply vanished?

// Let's pass no parameter at all!
$result2 = $magicQuery->build($sql, []);
// $result2 = SELECT * FROM users
// The whole WHERE condition disappeared because it is not needed anymore!
```

Curious to know how this work? <a class="btn btn-primary btn-large" href="doc/discard_unused_parameters.md">Check out the parameters guide!</a>

<a name="joins"></a>
Automatically guess JOINs with MagicJoin!
-----------------------------------------

Fed up of writing joins in SQL? Let MagicQuery do the work for you!

Seriously? Yes! All you have to do is:

- Pass a **[Doctrine DBAL connection](http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/)** to MagicQuery's constructor. MagicQuery will analyze your schema.
- In your SQL query, replace the tables with `magicjoin(start_table)`
- For each column of your query, use the complete name ([table_name].[column_name] instead of [column_name] alone)

Let's assume your database schema is:

![Sample database schema](doc/images/schema1.png)

Using MagicJoin, you can write this SQL query:
 
```sql
SELECT users.* FROM MAGICJOIN(users) WHERE groups.name = 'Admins' AND country.name='France';
```

and it will automatically be transformed into this:

```sql
SELECT users.* FROM users 
	LEFT JOIN users_groups ON users.user_id = users_groups.user_id
 	LEFT JOIN groups ON groups.group_id = users_groups.group_id
 	LEFT JOIN country ON country.country_id = users.country_id
WHERE groups.name = 'Admins' AND country.name='France';
```

And the code is so simple!

```php
use Mouf\Database\MagicQuery;

$sql = "SELECT users.* FROM MAGICJOIN(users) WHERE groups.name = 'Admins' AND country.name='France'";

// Get a MagicQuery object.
// $conn is a Doctrine DBAL connection.
$magicQuery = new MagicQuery($conn);

$completeSql = $magicQuery->build($sql);
// $completeSql contains the complete SQL request, with all joins.
```

Want to know more? <a class="btn btn-primary btn-large" href="doc/magic_join.md">Check out the MagicJoin guide!</a>

<a name="twig"></a>
Use Twig templating in your SQL queries!
----------------------------------------

Discarding unused parameters and auto-joining keys is not enough? You have very specific needs? Say hello to 
Twig integration!

Using Twig integration, you can directly add Twig conditions right into your SQL.

```php
use Mouf\Database\MagicQuery;

$sql = "SELECT users.* FROM users {% if isAdmin %} WHERE users.admin = 1 {% endif %}";

$magicQuery = new MagicQuery();
// By default, Twig integration is disabled. You need to enable it.
$magicQuery->setEnableTwig(true);

$completeSql = $magicQuery->build($sql, ['isAdmin' => true]);
// Parameters are passed to the Twig SQL query, and the SQL query is returned.
```

<div class="alert alert-info"><strong>Heads up!</strong> The Twig integration cannot be used to insert parameters
into the SQL query. You should use classic SQL parameters for this. This means that instead if writing 
<code>{{ id }}</code>, you should write <code>:id</code>.</div>

Want to know more? <a class="btn btn-primary btn-large" href="doc/magic_twig.md">Check out the MagicTwig guide!</a>

Is it a MySQL only tool?
------------------------

No. By default, your SQL is parsed and then rewritten using the MySQL dialect, but you use any kind of dialect 
known by [Doctrine DBAL](http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/). Magic-query optionally uses Doctrine DBAL. You can pass a `Connection` object
as the first parameter of the `MagicQuery` constructor. Magic-query will then use the matching dialect. 

For instance:

```php
$config = new \Doctrine\DBAL\Configuration();
$connectionParams = array(
    'url' => 'sqlite:///somedb.sqlite',
);
$conn = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);

$magicQuery = new \Mouf\Database\MagicQuery($conn);
```

Also, if you have no connection to your database configured but you want to generate SQL in some specific dialect, you can
instead set the DBAL database platform used:

```php
$magicQuery->setOutputDialect(new \Doctrine\DBAL\Platforms\PostgreSqlPlatform());
$magicQuery = new \Mouf\Database\MagicQuery();
```


What about performances?
------------------------

MagicQuery does a lot to your query. It will parse it, render it internally as a tree of SQL nodes, etc...
This processing is time consuming. So you should definitely consider using a cache system. MagicQuery is compatible
with Doctrine Cache. You simply have to pass a Doctrine Cache instance has the second parameter of the constructor.
 
```php
use Mouf\Database\MagicQuery;
use Doctrine\Common\Cache\ApcCache;

// $conn is a Doctrine connection
$magicQuery = new MagicQuery($conn, new ApcCache());
```

Any problem?
------------

With MagicQuery, a lot happens to your SQL query. In particular, it is parsed using a modified version
of the php-sql-parser library. If you face any issues with a complex query, it is likely there is a bug
in the parser. Please open [an issue on Github](https://github.com/thecodingmachine/magic-query/issues) and we'll try to fix it.
