Use Twig templating in your SQL queries!
----------------------------------------

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

Limitations
-----------

<div class="alert alert-info"><strong>Heads up!</strong> The Twig integration cannot be used to insert parameters
into the SQL query. You should use classic SQL parameters for this. This means that instead if writing 
<code>{{ id }}</code>, you should write <code>:id</code>.</div>

You cannot directly use Twig parameters because Twig transformation is applied before SQL parsing. If parameters
where replaced by Twig before SQL is parsed, the caching of the transformation *SQL => parsed SQL* would become
inefficient.

For this reason, if you try to use `{{ parameter }}` instead of `:parameter` in your SQL query, an exception will
be thrown.

Usage
-----

For most queries, we found out that MagicJoin combined to MagicParameters is enough.
For this reason, MagicTwig is disabled by default. If you want to enable it, you can simply call 
`$magicQuery->setEnableTwig(true)` before calling the `build` method.

MagicTwig can typically be useful when you have parts of a query that needs to be added conditionally, depending 
on provided parameters.
