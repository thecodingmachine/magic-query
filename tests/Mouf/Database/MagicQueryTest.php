<?php
namespace Mouf\Database;


class MagicQueryTest extends \PHPUnit_Framework_TestCase
{

    public function testStandardSelect() {
        $magicQuery = new MagicQuery();

        $sql = "SELECT * FROM users";
        $this->assertEquals($sql, self::simplifySql($magicQuery->build($sql)));

        $sql = "SELECT * FROM users WHERE name LIKE :name";
        $this->assertEquals("SELECT * FROM users WHERE name LIKE 'foo'", self::simplifySql($magicQuery->build($sql, ['name'=>'foo'])));
        $this->assertEquals("SELECT * FROM users", self::simplifySql($magicQuery->build($sql)));

        $sql = "SELECT * FROM users WHERE name LIKE :name AND company LIKE :company";
        $this->assertEquals("SELECT * FROM users WHERE (name LIKE 'foo')", self::simplifySql($magicQuery->build($sql, ['name'=>'foo'])));
        $this->assertEquals("SELECT * FROM users WHERE (name LIKE 'foo') AND (company LIKE 'bar')", self::simplifySql($magicQuery->build($sql, ['name'=>'foo', 'company'=>'bar'])));
    }

    /**
     * Removes all artifacts
     */
    private static function simplifySql($sql) {
        $sql = str_replace("\n", " ", $sql);
        $sql = str_replace("\t", " ", $sql);
        $sql = str_replace("`", " ", $sql);
        $sql = str_replace("  ", " ", $sql);
        $sql = str_replace("  ", " ", $sql);
        $sql = str_replace("  ", " ", $sql);
        $sql = str_replace("  ", " ", $sql);
        $sql = str_replace("  ", " ", $sql);
        $sql = str_replace("  ", " ", $sql);
        $sql = str_replace("  ", " ", $sql);
        $sql = str_replace("  ", " ", $sql);
        $sql = str_replace("( ", "(", $sql);
        $sql = str_replace(" )", ")", $sql);

        $sql = trim($sql);
        return $sql;
    }
}
