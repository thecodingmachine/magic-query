<?php

namespace Mouf\Database;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Schema\Schema;
use Mouf\Database\SchemaAnalyzer\SchemaAnalyzer;
use PHPUnit\Framework\TestCase;

class MagicQueryTest extends TestCase
{
    public function testStandardSelect()
    {
        $magicQuery = new MagicQuery(null, new ArrayCache());

        $sql = "SELECT GROUP_CONCAT(id SEPARATOR ', ') AS ids FROM users";
        $this->assertEquals("SELECT GROUP_CONCAT(id SEPARATOR ', ') AS ids FROM users", self::simplifySql($magicQuery->build($sql)));

        $sql = 'SELECT id FROM users WHERE name LIKE :name LIMIT :offset, :limit';
        $this->assertEquals("SELECT id FROM users WHERE name LIKE 'foo'", self::simplifySql($magicQuery->build($sql, ['name' => 'foo'])));

        $sql = 'SELECT id FROM users WHERE name LIKE :name LIMIT 2, :limit';
        $this->assertEquals("SELECT id FROM users WHERE name LIKE 'foo' LIMIT 2, 10", self::simplifySql($magicQuery->build($sql, ['name' => 'foo', 'limit' => 10])));
        // Test cache
        $this->assertEquals("SELECT id FROM users WHERE name LIKE 'bar' LIMIT 2, 10", self::simplifySql($magicQuery->build($sql, ['name' => 'bar', 'limit' => 10])));

        try {
            $exceptionOccurred = false;
            $sql = 'SELECT id FROM users WHERE name LIKE :name LIMIT 2, :limit';
            self::simplifySql($magicQuery->build($sql, ['name' => 'foo']));
        } catch (\Exception $e) {
            // We have no limit provided in the parameters so we test that the script return an exception for this case
            $exceptionOccurred = true;
        }
        $this->assertEquals(true, $exceptionOccurred);

        $sql = 'SELECT id FROM users WHERE name LIKE :name LIMIT :offset, 5';
        $this->assertEquals("SELECT id FROM users WHERE name LIKE 'foo' LIMIT 0, 5", self::simplifySql($magicQuery->build($sql, ['name' => 'foo', 'offset' => 0])));

        $sql = 'SELECT id FROM users WHERE name LIKE :name LIMIT :offset, 5';
        $this->assertEquals("SELECT id FROM users WHERE name LIKE 'foo' LIMIT 5", self::simplifySql($magicQuery->build($sql, ['name' => 'foo'])));

        $sql = 'SELECT id FROM users LIMIT 10';
        $this->assertEquals('SELECT id FROM users LIMIT 10', self::simplifySql($magicQuery->build($sql)));

        $sql = 'SELECT id FROM users LIMIT 10 OFFSET 20';
        $this->assertEquals('SELECT id FROM users LIMIT 20, 10', self::simplifySql($magicQuery->build($sql)));

        $sql = 'SELECT id FROM users WHERE name LIKE :name LIMIT :offset, :limit';
        $this->assertEquals("SELECT id FROM users WHERE name LIKE 'foo' LIMIT 0, 20", self::simplifySql($magicQuery->build($sql, ['name' => 'foo', 'offset' => 0, 'limit' => 20])));

        $sql = 'SELECT id FROM users WHERE name LIKE :name LIMIT 0, 20';
        $this->assertEquals("SELECT id FROM users WHERE name LIKE 'foo' LIMIT 0, 20", self::simplifySql($magicQuery->build($sql, ['name' => 'foo'])));

        $sql = "SELECT DATE_FORMAT(CURDATE(), '%d/%m/%Y') AS current_date FROM users WHERE name LIKE :name";
        $this->assertEquals("SELECT DATE_FORMAT(CURDATE(), '%d/%m/%Y') AS current_date FROM users WHERE name LIKE 'foo'", self::simplifySql($magicQuery->build($sql, ['name' => 'foo'])));

        $sql = 'SELECT YEAR(CURDATE()) AS current_year FROM users WHERE name LIKE :name';
        $this->assertEquals("SELECT YEAR(CURDATE()) AS current_year FROM users WHERE name LIKE 'foo'", self::simplifySql($magicQuery->build($sql, ['name' => 'foo'])));

        $sql = 'SELECT * FROM users';
        $this->assertEquals($sql, self::simplifySql($magicQuery->build($sql)));

        $sql = 'SELECT * FROM users WHERE name LIKE :name';
        $this->assertEquals("SELECT * FROM users WHERE name LIKE 'foo'", self::simplifySql($magicQuery->build($sql, ['name' => 'foo'])));
        $this->assertEquals('SELECT * FROM users', self::simplifySql($magicQuery->build($sql)));

        $sql = 'SELECT SUM(users.age) FROM users WHERE name LIKE :name AND company LIKE :company';
        $this->assertEquals("SELECT SUM(users.age) FROM users WHERE (name LIKE 'foo')", self::simplifySql($magicQuery->build($sql, ['name' => 'foo'])));
        $this->assertEquals("SELECT SUM(users.age) FROM users WHERE (name LIKE 'foo') AND (company LIKE 'bar')", self::simplifySql($magicQuery->build($sql, ['name' => 'foo', 'company' => 'bar'])));

        $sql = 'SELECT * FROM users WHERE status in :status';
        $this->assertEquals("SELECT * FROM users WHERE status IN ('2','4')", self::simplifySql($magicQuery->build($sql, ['status' => [2, 4]])));

        $sql = 'SELECT * FROM users WHERE status in (:status)';
        $this->assertEquals("SELECT * FROM users WHERE status IN ('2','4')", self::simplifySql($magicQuery->build($sql, ['status' => [2, 4]])));

        $sql = 'SELECT * FROM users WHERE status IN :statuses';
        $this->assertEquals('SELECT * FROM users WHERE status IN (\'1\',\'2\')', self::simplifySql($magicQuery->build($sql, ['statuses' => [1, 2]])));

        $sql = 'SELECT * FROM myTable where someField BETWEEN :value1 AND :value2';
        $this->assertEquals("SELECT * FROM myTable WHERE someField BETWEEN '2' AND '4'", self::simplifySql($magicQuery->build($sql, ['value1' => 2, 'value2' => 4])));
        $this->assertEquals("SELECT * FROM myTable WHERE someField >= '2'", self::simplifySql($magicQuery->build($sql, ['value1' => 2])));
        $this->assertEquals("SELECT * FROM myTable WHERE someField <= '4'", self::simplifySql($magicQuery->build($sql, ['value2' => 4])));
        $this->assertEquals('SELECT * FROM myTable', self::simplifySql($magicQuery->build($sql, [])));

        $sql = 'SELECT * FROM myTable where (someField BETWEEN :value1 AND :value2) AND otherField = :value3';
        $this->assertEquals("SELECT * FROM myTable WHERE ((someField BETWEEN '2' AND '4')) AND (otherField = '8')", self::simplifySql($magicQuery->build($sql, ['value1' => 2, 'value2' => 4, 'value3' => 8])));


        // Triggers an "expression"
        // TODO: find why it fails!
        //$sql = 'SELECT * FROM (users) WHERE name LIKE :name';
        //$this->assertEquals("SELECT * FROM users WHERE name LIKE 'foo'", self::simplifySql($magicQuery->build($sql, ['name' => 'foo'])));
        //$this->assertEquals('SELECT * FROM users', self::simplifySql($magicQuery->build($sql)));

        // Triggers a const node
        $sql = 'SELECT id+1 FROM users';
        $this->assertEquals("SELECT id + 1 FROM users", self::simplifySql($magicQuery->build($sql)));

        // Tests parameters with a ! (to force NULL values)
        // Bonus: the = transforms into a IS
        $sql = 'SELECT * FROM users WHERE status = :status!';
        $this->assertEquals('SELECT * FROM users WHERE status IS null', self::simplifySql($magicQuery->build($sql, ['status' => null])));

        // Bonus 2: the <> transforms into a IS NOT NULL
        $sql = 'SELECT * FROM users WHERE status <> :status!';
        $this->assertEquals('SELECT * FROM users WHERE status IS NOT null', self::simplifySql($magicQuery->build($sql, ['status' => null])));

        // Test CASE WHERE
        $sql = "SELECT CASE WHEN status = 'on' THEN '1' WHEN status = 'off' THEN '0' ELSE '-1' END AS my_case FROM users";
        $this->assertEquals("SELECT CASE WHEN status = 'on' THEN '1' WHEN status = 'off' THEN '0' ELSE '-1' END AS my_case FROM users", self::simplifySql($magicQuery->build($sql)));

        // Test CASE WHERE like SWITCH CASE
        $sql = "SELECT CASE status WHEN 'on' THEN '1' WHEN 'off' THEN '0' ELSE '-1' END AS my_case FROM users";
        $this->assertEquals("SELECT CASE status WHEN 'on' THEN '1' WHEN 'off' THEN '0' ELSE '-1' END AS my_case FROM users", self::simplifySql($magicQuery->build($sql)));

        $sql = 'SELECT * FROM users WHERE status IN :statuses!';
        $this->assertEquals('SELECT * FROM users WHERE FALSE', self::simplifySql($magicQuery->build($sql, ['statuses' => []])));

        // Test strings with "
        $sql = 'SELECT * FROM users WHERE status = \'"\'';
        $this->assertEquals('SELECT * FROM users WHERE status = \'"\'', self::simplifySql($magicQuery->build($sql)));

        $sql = 'SELECT 1+2 as toto FROM users';
        $this->assertEquals('SELECT 1 + 2 AS toto FROM users', self::simplifySql($magicQuery->build($sql)));

        $sql = 'SELECT -1 as toto FROM users';
        $this->assertEquals('SELECT -1 AS toto FROM users', self::simplifySql($magicQuery->build($sql)));

        $sql = 'SELECT \'hello\' as toto FROM users';
        $this->assertEquals('SELECT \'hello\' AS toto FROM users', self::simplifySql($magicQuery->build($sql)));

        $sql = "SELECT Substring_index(ee.`data`, ':', -1) as foo FROM users";
        $this->assertEquals("SELECT Substring_index(ee.data, ':', -1) AS foo FROM users", self::simplifySql($magicQuery->build($sql)));
        
        $sql = 'SELECT * FROM users GROUP BY id, login';
        $this->assertEquals('SELECT * FROM users GROUP BY id, login', self::simplifySql($magicQuery->build($sql)));

        $sql = 'SELECT * FROM users WHERE (login LIKE :login)';
        $this->assertEquals('SELECT * FROM users', self::simplifySql($magicQuery->build($sql)));

        $sql = 'SELECT DISTINCT login FROM users';
        $this->assertEquals('SELECT DISTINCT login FROM users', self::simplifySql($magicQuery->build($sql)));

        $sql = 'SELECT * FROM users WHERE create_date > :date';
        $this->assertEquals("SELECT * FROM users WHERE create_date > '2016-01-01'", self::simplifySql($magicQuery->build($sql, [
            'date' => '2016-01-01'
        ])));

        $sql = 'SELECT * FROM users WHERE create_date >= :date';
        $this->assertEquals("SELECT * FROM users WHERE create_date >= '2016-01-01'", self::simplifySql($magicQuery->build($sql, [
            'date' => '2016-01-01'
        ])));

        $sql = 'SELECT * FROM users WHERE create_date < :date';
        $this->assertEquals("SELECT * FROM users WHERE create_date < '2016-01-01'", self::simplifySql($magicQuery->build($sql, [
            'date' => '2016-01-01'
        ])));

        $sql = 'SELECT * FROM users WHERE create_date <= :date';
        $this->assertEquals("SELECT * FROM users WHERE create_date <= '2016-01-01'", self::simplifySql($magicQuery->build($sql, [
            'date' => '2016-01-01'
        ])));

        $sql = 'SELECT * FROM users WHERE id IN (1, 3)';
        $this->assertEquals("SELECT * FROM users WHERE id IN (1, 3)", self::simplifySql($magicQuery->build($sql)));

        $sql = 'SELECT country.* FROM country JOIN users ON country.id = users.country_id GROUP BY country.id ORDER BY COUNT(users.id) DESC';
        $this->assertEquals('SELECT country.* FROM country JOIN users ON (country.id = users.country_id) GROUP BY country.id ORDER BY COUNT(users.id) DESC', self::simplifySql($magicQuery->build($sql)));

        $sql = 'SELECT COUNT(DISTINCT a, b) FROM users';
        $this->assertEquals('SELECT COUNT(DISTINCT a, b) FROM users', self::simplifySql($magicQuery->build($sql)));

        $sql = 'SELECT a FROM users UNION SELECT a FROM users';
        $this->assertEquals('SELECT a FROM users UNION SELECT a FROM users', self::simplifySql($magicQuery->build($sql)));

        $sql = 'SELECT a FROM users u, users u2';
        $this->assertEquals('SELECT a FROM users AS u CROSS JOIN users AS u2', self::simplifySql($magicQuery->build($sql)));

        $sql = 'SELECT a FROM users u WHERE status = (CASE WHEN u.id = 1 THEN u.status_1 ELSE u.status_2 END)';
        $this->assertEquals('SELECT a FROM users AS u WHERE status = (CASE WHEN u.id = 1 THEN u.status_1 ELSE u.status_2 END)', self::simplifySql($magicQuery->build($sql)));

        $sql = 'SELECT 42 as fooalias FROM bar HAVING fooalias = 24';
        $this->assertEquals('SELECT 42 AS fooalias FROM bar HAVING fooalias = 24', self::simplifySql($magicQuery->build($sql)));
    }

    /**
     * @expectedException \Mouf\Database\MagicQueryException
     */
    public function testInNullException() {
        $magicQuery = new MagicQuery();

        $sql = 'SELECT * FROM users WHERE status IN :statuses!';
        $magicQuery->build($sql, ['statuses' => NULL]);
    }

    /**
     * @expectedException \Mouf\Database\MagicQueryException
     */
    public function testInvalidSql() {
        $magicQuery = new MagicQuery();

        $sql = 'SELECT * FROM users WHERE date_end => :startDate';
        $this->assertEquals('SELECT * FROM users WHERE date_end => \'2014-06-06\'', self::simplifySql($magicQuery->build($sql, ['startDate' => '2014-06-06'])));
    }

    public function testWithCache()
    {
        global $db_url;
        $connectionParams = array(
            'user' => $GLOBALS['db_username'],
            'password' => $GLOBALS['db_password'],
            'host' => $GLOBALS['db_host'],
            'driver' => $GLOBALS['db_driver'],
        );
        $conn = \Doctrine\DBAL\DriverManager::getConnection($connectionParams);

        $cache = new ArrayCache();

        $magicQuery = new MagicQuery($conn, $cache);

        $sql = 'SELECT * FROM users';
        $this->assertEquals($sql, self::simplifySql($magicQuery->build($sql)));
        $select = $cache->fetch('request_'.hash('md4', $sql));
        $this->assertInstanceOf('SQLParser\\Query\\Select', $select);
        $this->assertEquals($sql, self::simplifySql($magicQuery->build($sql)));
    }

    /**
     * @expectedException \Mouf\Database\MagicQueryParserException
     */
    public function testParseError()
    {
        $magicQuery = new MagicQuery();

        $sql = '';
        $magicQuery->build($sql);
    }

    private function getSchema()
    {
        $schema = new Schema();
        $role = $schema->createTable('role');
        $role->addColumn('id', 'integer', array('unsigned' => true));
        $role->addColumn('label', 'string', array('length' => 32));

        $right = $schema->createTable('right');
        $right->addColumn('id', 'integer', array('unsigned' => true));
        $right->addColumn('label', 'string', array('length' => 32));
        $role_right = $schema->createTable('role_right');

        $role_right->addColumn('role_id', 'integer', array('unsigned' => true));
        $role_right->addColumn('right_id', 'integer', array('unsigned' => true));
        $role_right->addForeignKeyConstraint($schema->getTable('role'), array('role_id'), array('id'), array('onUpdate' => 'CASCADE'));
        $role_right->addForeignKeyConstraint($schema->getTable('right'), array('right_id'), array('id'), array('onUpdate' => 'CASCADE'));
        $role_right->setPrimaryKey(['role_id', 'right_id']);
        return $schema;
    }

    public function testMagicJoin()
    {
        $schema = $this->getSchema();

        $schemaAnalyzer = new SchemaAnalyzer(new StubSchemaManager($schema));

        $magicQuery = new MagicQuery(null, null, $schemaAnalyzer);

        $sql = "SELECT role.* FROM magicjoin(role) WHERE right.label = 'my_right'";
        $expectedSql = "SELECT role.* FROM role LEFT JOIN role_right ON (role_right.role_id = role.id) LEFT JOIN right ON (role_right.right_id = right.id) WHERE right.label = 'my_right'";
        $this->assertEquals($expectedSql, self::simplifySql($magicQuery->build($sql)));
    }

    public function testMagicJoinNodeWalker()
    {
        $schema = $this->getSchema();

        $schemaAnalyzer = new SchemaAnalyzer(new StubSchemaManager($schema));

        $magicQuery = new MagicQuery(null, null, $schemaAnalyzer);

        $sql = "SELECT role.* FROM magicjoin(role) WHERE role.label = 'my_role' AND (right.label LIKE '%test%' OR right.label LIKE '%testBis%')";
        $expectedSql = "SELECT role.* FROM role LEFT JOIN role_right ON (role_right.role_id = role.id) LEFT JOIN right ON (role_right.right_id = right.id) WHERE (role.label = 'my_role') AND (((right.label LIKE '%test%') OR (right.label LIKE '%testBis%')))";
        $this->assertEquals($expectedSql, self::simplifySql($magicQuery->build($sql)));
    }

    public function testMagicJoin2()
    {
        $schema = new Schema();
        $role = $schema->createTable('role');
        $role->addColumn('id', 'integer', array('unsigned' => true));
        $role->addColumn('label', 'string', array('length' => 32));

        $right = $schema->createTable('right');
        $right->addColumn('id', 'integer', array('unsigned' => true));
        $right->addColumn('label', 'string', array('length' => 32));
        $role_right = $schema->createTable('role_right');

        $role_right->addColumn('role_id', 'integer', array('unsigned' => true));
        $role_right->addColumn('right_id', 'integer', array('unsigned' => true));
        $role_right->addForeignKeyConstraint($schema->getTable('role'), array('role_id'), array('id'), array('onUpdate' => 'CASCADE'));
        $role_right->addForeignKeyConstraint($schema->getTable('right'), array('right_id'), array('id'), array('onUpdate' => 'CASCADE'));
        $role_right->setPrimaryKey(['role_id', 'right_id']);

        $user = $schema->createTable('user');
        $user->addColumn('id', 'integer', array('unsigned' => true));
        $user->addColumn('login', 'string', array('length' => 32));
        $user->addColumn('role_id', 'integer', array('unsigned' => true));
        $user->addForeignKeyConstraint($schema->getTable('role'), array('role_id'), array('id'), array('onUpdate' => 'CASCADE'));

        $schemaAnalyzer = new SchemaAnalyzer(new StubSchemaManager($schema));

        $magicQuery = new MagicQuery(null, null, $schemaAnalyzer);

        $sql = "SELECT role.* FROM magicjoin(role) WHERE right.label = 'my_right' AND user.login = 'foo'";
        $expectedSql = "SELECT role.* FROM role LEFT JOIN role_right ON (role_right.role_id = role.id) LEFT JOIN right ON (role_right.right_id = right.id) LEFT JOIN user ON (user.role_id = role.id) WHERE (right.label = 'my_right') AND (user.login = 'foo')";
        $this->assertEquals($expectedSql, self::simplifySql($magicQuery->build($sql)));
    }

    public function testMagicJoin3()
    {
        $schema = new Schema();
        $role = $schema->createTable('role');
        $role->addColumn('id', 'integer', array('unsigned' => true));
        $role->addColumn('label', 'string', array('length' => 32));

        $right = $schema->createTable('right');
        $right->addColumn('id', 'integer', array('unsigned' => true));
        $right->addColumn('label', 'string', array('length' => 32));
        $role_right = $schema->createTable('role_right');

        $role_right->addColumn('role_id', 'integer', array('unsigned' => true));
        $role_right->addColumn('right_id', 'integer', array('unsigned' => true));
        $role_right->addForeignKeyConstraint($schema->getTable('role'), array('role_id'), array('id'), array('onUpdate' => 'CASCADE'));
        $role_right->addForeignKeyConstraint($schema->getTable('right'), array('right_id'), array('id'), array('onUpdate' => 'CASCADE'));
        $role_right->setPrimaryKey(['role_id', 'right_id']);

        $status = $schema->createTable('status');
        $status->addColumn('id', 'integer', array('unsigned' => true));
        $status->addColumn('name', 'string', array('length' => 32));

        $role->addColumn('status_id', 'integer', array('unsigned' => true));
        $role->addForeignKeyConstraint($schema->getTable('status'), array('status_id'), array('id'), array('onUpdate' => 'CASCADE'));

        $schemaAnalyzer = new SchemaAnalyzer(new StubSchemaManager($schema));

        $magicQuery = new MagicQuery(null, null, $schemaAnalyzer);

        $sql = "SELECT role.* FROM magicjoin(role) WHERE right.label = 'my_right' AND status.name = 'foo'";
        $expectedSql = "SELECT role.* FROM role LEFT JOIN role_right ON (role_right.role_id = role.id) LEFT JOIN right ON (role_right.right_id = right.id) LEFT JOIN status ON (role.status_id = status.id) WHERE (right.label = 'my_right') AND (status.name = 'foo')";
        $this->assertEquals($expectedSql, self::simplifySql($magicQuery->build($sql)));
    }

    public function testMatchAgainst()
    {
        $magicQuery = new MagicQuery();

        $sql = "
            SELECT MATCH(column) AGAINST(:searchTerm IN BOOLEAN MODE) AS rang FROM table
            WHERE MATCH(column) AGAINST(:searchTerm IN BOOLEAN MODE)
            ORDER BY MATCH(column) AGAINST(:searchTerm IN BOOLEAN MODE) DESC
        ";
        $expectedSql = "SELECT MATCH(column) AGAINST('searchString' IN BOOLEAN MODE) AS rang FROM table WHERE MATCH(column) AGAINST('searchString' IN BOOLEAN MODE) ORDER BY MATCH(column) AGAINST('searchString' IN BOOLEAN MODE) DESC";
        
        $params["searchTerm"] = "searchString";
        
        $this->assertEquals($expectedSql, self::simplifySql($magicQuery->build($sql, $params)));
    }

    /**
     * @expectedException \Mouf\Database\MagicQueryMissingConnectionException
     */
    public function testMisconfiguration()
    {
        $magicQuery = new MagicQuery();

        $sql = "SELECT role.* FROM magicjoin(role) WHERE right.label = 'my_right'";
        $magicQuery->build($sql);
    }

    /**
     *
     */
    public function testTwig()
    {
        $magicQuery = new MagicQuery();
        $magicQuery->setEnableTwig(true);

        $sql = "SELECT * FROM toto {% if id %}WHERE status = 'on'{% endif %}";
        $this->assertEquals("SELECT * FROM toto WHERE status = 'on'", $this->simplifySql($magicQuery->build($sql, ['id' => 12])));
        $this->assertEquals('SELECT * FROM toto', $this->simplifySql($magicQuery->build($sql, ['id' => null])));
    }

    /**
     * Removes all artifacts.
     */
    private static function simplifySql($sql)
    {
        $sql = str_replace("\n", ' ', $sql);
        $sql = str_replace("\t", ' ', $sql);
        $sql = str_replace('`', '', $sql);
        $sql = str_replace('  ', ' ', $sql);
        $sql = str_replace('  ', ' ', $sql);
        $sql = str_replace('  ', ' ', $sql);
        $sql = str_replace('  ', ' ', $sql);
        $sql = str_replace('  ', ' ', $sql);
        $sql = str_replace('  ', ' ', $sql);
        $sql = str_replace('  ', ' ', $sql);
        $sql = str_replace('  ', ' ', $sql);
        $sql = str_replace('( ', '(', $sql);
        $sql = str_replace(' )', ')', $sql);
        $sql = str_replace(' . ', '.', $sql);

        $sql = trim($sql);

        return $sql;
    }

    public function testBuildPreparedStatement()
    {
        $magicQuery = new MagicQuery(null, new ArrayCache());

        $sql = 'SELECT id FROM users WHERE name LIKE :name LIMIT :offset, 2';
        $this->assertEquals("SELECT id FROM users WHERE name LIKE :name LIMIT :offset, 2", self::simplifySql($magicQuery->buildPreparedStatement($sql, ['name' => 'foo', 'offset' => 10])));
        // Test cache
        $this->assertEquals("SELECT id FROM users WHERE name LIKE :name LIMIT :offset, 2", self::simplifySql($magicQuery->buildPreparedStatement($sql, ['name' => 'bar', 'offset' => 10])));
        $this->assertEquals("SELECT id FROM users WHERE name LIKE :name LIMIT 2", self::simplifySql($magicQuery->buildPreparedStatement($sql, ['name' => 'bar'])));

        $sql = 'SELECT id FROM users WHERE status IN (:status)';
        $this->assertEquals("SELECT id FROM users WHERE status IN (:status)", self::simplifySql($magicQuery->buildPreparedStatement($sql, ['status' => [1,2]])));
        $this->assertEquals("SELECT id FROM users", self::simplifySql($magicQuery->buildPreparedStatement($sql, ['status' => null])));

        // Let's check that MagicQuery is cleverly adding parenthesis if the user forgot those in the "IN" statement.
        $sql = 'SELECT id FROM users WHERE status IN :status';
        $this->assertEquals("SELECT id FROM users WHERE status IN (:status)", self::simplifySql($magicQuery->buildPreparedStatement($sql, ['status' => [1,2]])));
    }

    public function testSetOutputDialect()
    {
        $magicQuery = new MagicQuery(null, new ArrayCache());
        $magicQuery->setOutputDialect(new PostgreSqlPlatform());

        $sql = 'SELECT id FROM users';
        $this->assertEquals('SELECT "id" FROM "users"', self::simplifySql($magicQuery->buildPreparedStatement($sql)));

        $magicQuery->setOutputDialect(null);

        $sql = 'SELECT id FROM users';
        $this->assertEquals('SELECT id FROM users', self::simplifySql($magicQuery->buildPreparedStatement($sql)));

    }
}
