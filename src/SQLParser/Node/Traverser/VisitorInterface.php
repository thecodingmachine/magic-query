<?php

namespace SQLParser\Node\Traverser;

use SQLParser\Node\NodeInterface;

/**
 * Classes implementing the `VisitorInterface` can be used to analyze nodes while they are
 * explored by the `NodeTraverser`.
 */
interface VisitorInterface
{
    /**
     * Called on every node when the traverser enters the node.
     * The enterNode() method can return a changed node, or null if nothing is changed.
     * The enterNode() method can also return the value NodeTraverser::DONT_TRAVERSE_CHILDREN,
     * which instructs the traverser to skip all children of the current node.
     *
     * @param NodeInterface $node
     *
     * @return NodeInterface|string|null
     */
    public function enterNode(NodeInterface $node);

    /**
     * Called on every node when the traverser leaves the node.
     * The leaveNode() method can return a changed node, or null if nothing is changed.
     * The leaveNode() method can also return the value NodeTraverser::REMOVE_NODE,
     * which instructs the traverser to remove the current node.
     *
     * @param NodeInterface $node
     */
    public function leaveNode(NodeInterface $node);
}
