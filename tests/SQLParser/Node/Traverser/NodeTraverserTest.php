<?php
namespace SQLParser\Node\Traverser;

use SQLParser\Query\StatementFactory;
use SQLParser\SQLParser;

class NodeTraverserTest extends \PHPUnit_Framework_TestCase
{
    public function testStandardSelect()
    {
        $magicJoinDetector = new DetectMagicJoinSelectVisitor();
        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor($magicJoinDetector);

        $parser = new SQLParser();

        $sql = 'SELECT * FROM users';
        $parsed = $parser->parse($sql);
        $select = StatementFactory::toObject($parsed);
        $nodeTraverser->walk($select);
        $this->assertCount(0, $magicJoinDetector->getMagicJoinSelects());
        $magicJoinDetector->resetVisitor();

        $sql = 'SELECT * FROM magicjoin';
        $parsed = $parser->parse($sql);
        $select = StatementFactory::toObject($parsed);
        $nodeTraverser->walk($select);
        $this->assertCount(1, $magicJoinDetector->getMagicJoinSelects());
        $magicJoinDetector->resetVisitor();

        $sql = 'SELECT SUM(users.age) FROM users WHERE name LIKE :name AND company LIKE :company';
        $parsed = $parser->parse($sql);
        $select = StatementFactory::toObject($parsed);
        $nodeTraverser->walk($select);
        $this->assertCount(0, $magicJoinDetector->getMagicJoinSelects());
        $magicJoinDetector->resetVisitor();

        $sql = 'SELECT * FROM users WHERE status in :status';
        $parsed = $parser->parse($sql);
        $select = StatementFactory::toObject($parsed);
        $nodeTraverser->walk($select);
        $this->assertCount(0, $magicJoinDetector->getMagicJoinSelects());
        $magicJoinDetector->resetVisitor();

        // Triggers a const node
        $sql = 'SELECT id+1 FROM users';
        $parsed = $parser->parse($sql);
        $select = StatementFactory::toObject($parsed);
        $nodeTraverser->walk($select);
        $this->assertCount(0, $magicJoinDetector->getMagicJoinSelects());
        $magicJoinDetector->resetVisitor();

    }

}
