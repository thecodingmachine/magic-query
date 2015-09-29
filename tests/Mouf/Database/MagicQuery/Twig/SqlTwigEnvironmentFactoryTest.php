<?php

namespace Mouf\Database;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\DBAL\Schema\Schema;
use Mouf\Database\MagicQuery\Twig\SqlTwigEnvironmentFactory;
use Mouf\Database\SchemaAnalyzer\SchemaAnalyzer;

class SqlTwigEnvironmentFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function getTwigWithConnection()
    {
        global $db_url;
        $config = new \Doctrine\DBAL\Configuration();
        // TODO: put this in conf variable
        $connectionParams = array(
            'url' => $db_url,
        );
        $conn = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);

        $twigEnvironment = SqlTwigEnvironmentFactory::getTwigEnvironment($conn);
        return $twigEnvironment;
    }

    /**
     * We need to run this test in a separate process because Twig seems to be enable to register several escapers
     * with the same name.
     *
     * @runInSeparateProcess
     */
    /*public function testWithoutConnection()
    {
        $twigEnvironment = SqlTwigEnvironmentFactory::getTwigEnvironment();
        $this->doTestTwig($twigEnvironment);
    }*/

    /**
     *
     */
    public function testIf() {

        $twig = $this->getTwigWithConnection();
        $sql = $twig->render("SELECT * FROM toto {% if id %}WHERE id = :id{% endif %}", ["id"=>12]);
        $this->assertEquals("SELECT * FROM toto WHERE id = :id", $sql);
    }

    /**
     * @expectedException Twig_Error_Runtime
     */
    public function testException() {

        $twig = $this->getTwigWithConnection();
        $twig->render("SELECT * FROM toto WHERE id = {{ id }}", ["id"=>null]);
    }
}
