<?php

namespace Mouf\Database;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\DBAL\Schema\Schema;
use Mouf\Database\SchemaAnalyzer\SchemaAnalyzer;

class MagicQueryTest extends \PHPUnit_Framework_TestCase
{
    public function testStandardSelect()
    {
        $magicQuery = new MagicQuery();

        $sql = 'SELECT * FROM users';
        $this->assertEquals($sql, self::simplifySql($magicQuery->build($sql)));

        $sql = 'SELECT * FROM users WHERE name LIKE :name';
        $this->assertEquals("SELECT * FROM users WHERE name LIKE 'foo'", self::simplifySql($magicQuery->build($sql, ['name' => 'foo'])));
        $this->assertEquals('SELECT * FROM users', self::simplifySql($magicQuery->build($sql)));

        $sql = 'SELECT SUM(users.age) FROM users WHERE name LIKE :name AND company LIKE :company';
        $this->assertEquals("SELECT SUM(users.age) FROM users WHERE (name LIKE 'foo')", self::simplifySql($magicQuery->build($sql, ['name' => 'foo'])));
        $this->assertEquals("SELECT SUM(users.age) FROM users WHERE (name LIKE 'foo') AND (company LIKE 'bar')", self::simplifySql($magicQuery->build($sql, ['name' => 'foo', 'company' => 'bar'])));

        $sql = 'SELECT * FROM users WHERE status in :status';
        $this->assertEquals("SELECT * FROM users WHERE status IN ('2','4')", self::simplifySql($magicQuery->build($sql, ['status' => [2,4]])));

        // Triggers an "expression"
        // TODO: find why it fails!
        //$sql = 'SELECT * FROM (users) WHERE name LIKE :name';
        //$this->assertEquals("SELECT * FROM users WHERE name LIKE 'foo'", self::simplifySql($magicQuery->build($sql, ['name' => 'foo'])));
        //$this->assertEquals('SELECT * FROM users', self::simplifySql($magicQuery->build($sql)));

        // Triggers a const node
        $sql = 'SELECT id+1 FROM users';
        $this->assertEquals("SELECT id + '1' FROM users", self::simplifySql($magicQuery->build($sql)));


    }

    public function testWithCache() {
        $config = new \Doctrine\DBAL\Configuration();
        $connectionParams = array(
            'url' => 'mysql://root:@localhost/',
        );
        $conn = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);

        $cache = new ArrayCache();

        $magicQuery = new MagicQuery($conn, $cache);

        $sql = 'SELECT * FROM users';
        $this->assertEquals($sql, self::simplifySql($magicQuery->build($sql)));
        $select = $cache->fetch("request_".hash("md4", $sql));
        $this->assertInstanceOf('SQLParser\\Query\\Select', $select);
        $this->assertEquals($sql, self::simplifySql($magicQuery->build($sql)));
    }

    /**
     * @expectedException \Mouf\Database\MagicQueryParserException
     */
    public function testParseError() {
        $magicQuery = new MagicQuery();

        $sql = '';
        $magicQuery->build($sql);
    }

    public function testMagicJoin() {
        $schema = new Schema();
        $role = $schema->createTable("role");
        $role->addColumn("id", "integer", array("unsigned" => true));
        $role->addColumn("label", "string", array("length" => 32));

        $right = $schema->createTable("right");
        $right->addColumn("id", "integer", array("unsigned" => true));
        $right->addColumn("label", "string", array("length" => 32));
        $role_right = $schema->createTable("role_right");

        $role_right->addColumn("role_id", "integer", array("unsigned" => true));
        $role_right->addColumn("right_id", "integer", array("unsigned" => true));
        $role_right->addForeignKeyConstraint($schema->getTable('role'), array("role_id"), array("id"), array("onUpdate" => "CASCADE"));
        $role_right->addForeignKeyConstraint($schema->getTable('right'), array("right_id"), array("id"), array("onUpdate" => "CASCADE"));
        $role_right->setPrimaryKey(["role_id", "right_id"]);

        $schemaAnalyzer = new SchemaAnalyzer(new StubSchemaManager($schema));

        $magicQuery = new MagicQuery(null, null, $schemaAnalyzer);

        $sql = "SELECT role.* FROM magicjoin(role) WHERE right.label = 'my_right'";
        $expectedSql = "SELECT role.* FROM role LEFT JOIN role_right ON (role_right.role_id = role.id) LEFT JOIN right ON (role_right.right_id = right.id) WHERE right.label = 'my_right'";
        $this->assertEquals($expectedSql, self::simplifySql($magicQuery->build($sql)));
    }

    /**
     * @expectedException \Mouf\Database\MagicQueryMissingConnectionException
     */
    public function testMisconfiguration() {
        $magicQuery = new MagicQuery();

        $sql = "SELECT role.* FROM magicjoin(role) WHERE right.label = 'my_right'";
        $magicQuery->build($sql);

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
}
