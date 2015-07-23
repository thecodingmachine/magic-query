[![Latest Unstable Version](https://poser.pugx.org/mouf/magic-query/v/unstable)](https://packagist.org/packages/mouf/magic-query)
[![License](https://poser.pugx.org/mouf/magic-query/license)](https://packagist.org/packages/mouf/magic-query)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/thecodingmachine/magic-query/badges/quality-score.png?b=1.0)](https://scrutinizer-ci.com/g/thecodingmachine/magic-query/?branch=1.0)
[![Build Status](https://travis-ci.org/thecodingmachine/magic-query.svg?branch=1.0)](https://travis-ci.org/thecodingmachine/magic-query)
[![Coverage Status](https://coveralls.io/repos/thecodingmachine/magic-query/badge.svg?branch=1.0)](https://coveralls.io/r/thecodingmachine/magic-query?branch=1.0)

What is Magic-query?
====================

Magic-query is a PHP library that helps you work with complex queries that require
a variable number of parameters.

How does it work?
-----------------

Easy! You write the query with all possible parameters.

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

Installation
------------

Simply use the composer package:

```json
{
	"require": {
		"mouf/magic-query": "~1.0"
	},
	"minimum-stability": "dev",
	"prefer-stable": true
}
```

Why should I care?
------------------

Because it is **the most efficient way to deal with queries that can have a variable number of parameters**!
Think about a typical datagrid with a bunch of filter (for instance a list of products filtered by name, company, price, ...).
If you have the very common idea to generate the SQL query using no PHP library, your code will look like this:

###Without Magic-query
<div class="alert"><strong>You should not do this!</strong></div>

```php
// People usually write queries like this:
$sql = "SELECT * FROM products p JOIN companies c ON p.company_id = c.id WHERE 1=1 ";
// They keep testing for parameters, and concatenating strings....
if (isset($params['name'])) {
	$sql .= "AND (p.name LIKE '".addslashes($params['name'])."%' OR p.altname LIKE '".addslashes($params['name'])."%')";
}
if (isset($params['company'])) {
	$sql .= "AND c.name LIKE '".addslashes($params['company'])."%'";
}
if (isset($params['country'])) {
	$sql .= "AND c.country LIKE '".addslashes($params['country'])."%'";
}
// And so on... for each parameter, we have a "if" statement
```

Concatenating SQL queries is **dangerous** (especially if you forget to protect parameters).
You can always use parametrized SQL queries, but you will still have to concatenate the filters.

###With Magic-Query

```php
// One query with all parameters
$sql = "SELECT * FROM products p JOIN companies c ON p.company_id = c.id WHERE 
	(p.name LIKE :name OR p.altname LIKE :name)
	AND c.name LIKE :company
	AND c.country LIKE :country";

$magicQuery = new MagicQuery();
$sql = $magicQuery->build($sql, $params);
```

###Other alternatives

To avoid concatenating strings, frameworks and libraries have used different strategies. Using a full ORM (like
Doctrine or Propel) is a good idea, but it makes writing complex queries even more complex. Other frameworks like
Zend are building queries using function calls. These are valid strategies, but you are no more typing SQL queries
directly, and let's face it, it is always useful to use a query directly.

How does it work under the hood?
--------------------------------

A lot happens to your SQL query. It is actually parsed (thanks to a modified
version of the php-sql-parser library) and then changed into a tree.
The magic happens on the tree where the node containing unused parameters
are simply discarded. When it's done, the tree is changed back to SQL and
"shazam!", your SQL query is purged of useless parameters!

Is it a MySQL only tool?
------------------------

No. By default, your SQL is parsed and then rewritten using the MySQL dialect, but you use any kind of dialect 
known by Doctrine DBAL. Magic-query optionally uses Doctrine DBAL. You can pass a `Connection` object
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

Any problem?
------------

As we said, a lot happen to your SQL query. In particular, it is parsed using a modified version
of the php-sql-parser library. If you face any issues with a complex query, it is likely there is a bug
in the parser. Please open [an issue on Github](https://github.com/thecodingmachine/magic-query/issues) and we'll try to fix it.
