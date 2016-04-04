<?php

namespace SQLParser\Node;

use SQLParser\Node\Traverser\VisitorInterface;
use SQLParser\SqlRenderInterface;
use Mouf\MoufManager;
use Mouf\MoufInstanceDescriptor;

/**
 * This base interface for anything that can represent a SQL expression part.
 *
 * @author David Négrier <d.negrier@thecodingmachine.com>
 */
interface NodeInterface extends SqlRenderInterface
{
    /**
     * Returns a Mouf instance descriptor describing this object.
     *
     * @param MoufManager $moufManager
     *
     * @return MoufInstanceDescriptor
     */
    public function toInstanceDescriptor(MoufManager $moufManager);

    /**
     * Walks the tree of nodes, calling the visitor passed in parameter.
     *
     * @param VisitorInterface $visitor
     *
     * @return NodeInterface|null|string Can return null if nothing is to be done or a node that should replace this node, or NodeTraverser::REMOVE_NODE to remove the node
     */
    public function walk(VisitorInterface $visitor);
}
