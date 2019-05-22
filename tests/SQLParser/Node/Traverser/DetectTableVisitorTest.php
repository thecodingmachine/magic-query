<?php

namespace SQLParser\Node\Traverser;

use PHPSQLParser\PHPSQLParser;
use PHPUnit\Framework\TestCase;
use SQLParser\Query\StatementFactory;

class DetectTableVisitorTest extends TestCase
{
    public function testStandardSelect()
    {
        $visitor = new DetectTablesVisitor('users');
        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor($visitor);

        $parser = new PHPSQLParser();

        $sql = 'SELECT foo.bar FROM users';
        $parsed = $parser->parse($sql);
        $select = StatementFactory::toObject($parsed);
        $nodeTraverser->walk($select);
        $this->assertCount(1, $visitor->getTables());
        $this->assertContains('foo', $visitor->getTables());
        $visitor->resetVisitor();

        $sql = 'SELECT foo.bar, foo.baz FROM users WHERE toto.tata = 12 GROUP BY yop.goo ORDER BY zap.do';
        $parsed = $parser->parse($sql);
        $select = StatementFactory::toObject($parsed);
        $nodeTraverser->walk($select);
        $this->assertCount(4, $visitor->getTables());
        $this->assertContains('foo', $visitor->getTables());
        $this->assertContains('toto', $visitor->getTables());
        $this->assertContains('yop', $visitor->getTables());
        $this->assertContains('zap', $visitor->getTables());
        $visitor->resetVisitor();
    }

    public function testWrappedSelect()
    {
        $visitor = new DetectTablesVisitor('users');
        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor($visitor);

        $parser = new PHPSQLParser();

        $sql = 'SELECT foo.bar, foo.baz FROM users WHERE toto.tata = (SELECT mii.id FROM mii WHERE mii.yo=42) GROUP BY yop.goo ORDER BY zap.do';
        $parsed = $parser->parse($sql);
        $select = StatementFactory::toObject($parsed);
        $nodeTraverser->walk($select);
        $this->assertCount(4, $visitor->getTables());
        $this->assertContains('foo', $visitor->getTables());
        $this->assertContains('toto', $visitor->getTables());
        $this->assertContains('yop', $visitor->getTables());
        $this->assertContains('zap', $visitor->getTables());
        $visitor->resetVisitor();
    }

    public function testMissingRefTable()
    {
        $visitor = new DetectTablesVisitor('yop');
        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor($visitor);

        $parser = new PHPSQLParser();

        $sql = 'SELECT foo.bar, foo.baz FROM users WHERE tata = (SELECT mii.id FROM mii WHERE mii.yo=42) GROUP BY yop.goo ORDER BY zap.do';
        $parsed = $parser->parse($sql);
        $select = StatementFactory::toObject($parsed);
        $nodeTraverser->walk($select);

        $this->assertEquals('yop', $select->getWhere()->getLeftOperand()->getTable());
    }
}
