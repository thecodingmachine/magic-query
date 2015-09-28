<?php

namespace Mouf\Database;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\DBAL\Schema\Schema;
use Mouf\Database\MagicQuery\Twig\SqlTwigEnvironmentFactory;
use Mouf\Database\SchemaAnalyzer\SchemaAnalyzer;

class SqlTwigEnvironmentFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testWithConnection()
    {
        $config = new \Doctrine\DBAL\Configuration();
        // TODO: put this in conf variable
        $connectionParams = array(
            'url' => 'mysql://root:@localhost/',
        );
        $conn = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);

        $twigEnvironment = SqlTwigEnvironmentFactory::getTwigEnvironment($conn);
        $this->doTestTwig($twigEnvironment);
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


    private function doTestTwig(\Twig_Environment $twig) {
        $result = $twig->render("SELECT * FROM toto WHERE id = {{ id }}", ["id"=>null]);
        $this->assertEquals("SELECT * FROM toto WHERE id = null", $result);

        $result = $twig->render("SELECT * FROM toto WHERE id = {{ id }}", [ "id" => "myid" ]);
        $this->assertEquals("SELECT * FROM toto WHERE id = 'myid'", $result);

    }
}
