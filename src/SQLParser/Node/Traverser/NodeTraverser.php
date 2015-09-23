<?php
namespace SQLParser\Node\Traverser;
use SQLParser\Node\NodeInterface;

/**
 * This class can be used to traverse the tree of nodes.
 */
class NodeTraverser
{
    const DONT_TRAVERSE_CHILDREN = "DONT_TRAVERSE_CHILDREN";
    const REMOVE_NODE = "REMOVE_NODE";

    private $visitor;


    public function __construct()
    {
        $this->visitor = new CompositeVisitor();
    }


    /**
     * Registers a new visitor with this traverser.
     *
     * @param VisitorInterface $visitor
     */
    public function addVisitor(VisitorInterface $visitor) {
        $this->visitor->addVisitor($visitor);
    }



    /**
     * Starts traversing the tree, calling each visitor on each node.
     *
     * @param NodeInterface $node
     */
    public function walk(NodeInterface $node) {
        $node->walk($this->visitor);
    }
}
