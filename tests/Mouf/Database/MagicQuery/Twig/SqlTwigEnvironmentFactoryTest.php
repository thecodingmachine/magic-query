<?php

namespace Mouf\Database;

use Mouf\Database\MagicQuery\Twig\SqlTwigEnvironmentFactory;
use PHPUnit\Framework\TestCase;
use Twig\Error\RuntimeError;

class SqlTwigEnvironmentFactoryTest extends TestCase
{
    public function testIf()
    {
        $twig = SqlTwigEnvironmentFactory::getTwigEnvironment();
        $sql = $twig->render('SELECT * FROM toto {% if id %}WHERE id = :id{% endif %}', ['id' => 12]);
        $this->assertEquals('SELECT * FROM toto WHERE id = :id', $sql);
    }

    public function testException()
    {
        $twig = SqlTwigEnvironmentFactory::getTwigEnvironment();
        $this->expectException(RuntimeError::class);
        $twig->render('SELECT * FROM toto WHERE id = {{ id }}', ['id' => 'hello']);
    }
}
