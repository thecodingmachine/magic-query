What is QueryWritter?
=====================

QueryWritter is a PHP library that parses SQL queries, transforms those into an object representation, stores them in a 
dependency injection container, and returns them as string. It is a [Mouf plugin](http://mouf-php.com).

Ok, but why would I use QueryWritter?
-------------------------------------

Because it is **the most effecient way to deal with queries that can have a variable number of parameters**!
Think about a typical datagrid with a bunch of filter (for instance a list of products filtered by name, company, price, ...).
If you have the very common idea to generate the SQL query using no PHP library, your code will look like this:

<div class="alert"><strong>You should not do this!</strong></div>

```php
// People usually write queries like this:
$sql = "SELECT * FROM products p JOIN companies c ON p.company_id = c.id WHERE 1=1 ";
// They keep testing for parameters, and concatenating strings....
if (isset($params['name'])) {
	$sql .= "AND p.name LIKE '".addslashes($params['name'])."%'";
}
if (isset($params['company'])) {
	$sql .= "AND c.name LIKE '".addslashes($params['company'])."%'";
}
// And so on... for each parameter, we have a "if" statement
```



Concatenating SQL queries is dangerous (especially if you forget to protect parameters).
You can always use parameterized SQL queries, but you will still have to concatenate the filters.

To avoid concatenating strings, frameworks and libraries have used different strategies. Building a full ORM (like
Doctrine or Propel) is a good idea, but it makes writing complex queries even more complex. Other frameworks like
Zend are building queries using function calls. These are valid strategies, but you are no more typing SQL queries
directly, and let's face it, it is always useful to use a query directly.

This is where QueryWritter becomes helpful.

How does it work?
-----------------
// TODO: schema... or even better... video!

###1- Write your query
You start by writing your query, **in plain SQL**. No ORM, no special query language (DQL or HQL anyone?), just plain and simple SQL.
This is cool because everybody knows SQL. In your query, you put absolutely all the parameters you can imagine.

###2- Store your query in Mouf
In Mouf UI, go to **DB** > **SQL queries** > **Create SQL query**.
Here, you can **copy and paste your query**. Since this is Mouf, every query is an "instance", and you have to pick
a name for your query. 

Behind the scenes, QueryWritter will parse your query and make sure every piece of the query (each table, each column, each filter...) is transformed 
into an object. But you really don't have to care about that right now.

###3- Test your query
Right from Mouf UI, you can test your query! And lo and behold! Because the query was parsed, **QueryWritter will dynamically 
add parts of the query depending on the parameters you decide to use**.

###4- Use it in your code
If you are not a Mouf user (if you are using Drupal, Symfony, Zend Framework...), you can directly use the query by fetching the instance from Mouf and calling the <code>toSql</code> method, passing 
parameters in... parameter :)

```
$mySelect = Mouf::getMySelectStatement();
$sql = $mySelect->toSql(array("status"=>1, "search"=>"hello"));
```

If you are a Mouf user, you can even directly run the query using the **QueryResult** class that executes
the query directly. Or even better, use the **Evolugrid** module, and display your query result in an HTML
datagrid, directly!