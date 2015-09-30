<?php

namespace Mouf\Database;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\DBAL\Schema\Schema;
use Mouf\Database\MagicQuery\Twig\SqlTwigEnvironmentFactory;
use Mouf\Database\SchemaAnalyzer\SchemaAnalyzer;

class SqlTwigEnvironmentFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     */
    public function testIf() {

        $twig = SqlTwigEnvironmentFactory::getTwigEnvironment();
        $sql = $twig->render("SELECT * FROM toto {% if id %}WHERE id = :id{% endif %}", ["id"=>12]);
        $this->assertEquals("SELECT * FROM toto WHERE id = :id", $sql);
    }

    /**
     * @expectedException Twig_Error_Runtime
     */
    public function testException() {

        $twig = SqlTwigEnvironmentFactory::getTwigEnvironment();
        $twig->render("SELECT * FROM toto WHERE id = {{ id }}", ["id"=>"hello"]);
    }
}
