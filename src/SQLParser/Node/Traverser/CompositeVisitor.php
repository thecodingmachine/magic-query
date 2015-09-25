<?php
namespace SQLParser\Node\Traverser;


use SQLParser\Node\NodeInterface;

/**
 * A visitor that dispatches visits to other visitors.
 */
class CompositeVisitor implements VisitorInterface
{
    /**
     * @var VisitorInterface[]
     */
    private $visitors = array();

    /**
     * @var VisitorInterface[]
     */
    private $reverseVisitors = array();

    /**
     * Registers a new visitor with this traverser.
     *
     * @param VisitorInterface $visitor
     */
    public function addVisitor(VisitorInterface $visitor) {
        $this->visitors[] = $visitor;
        array_unshift($this->reverseVisitors, $visitor);
    }


    /**
     * Called on every node when the traverser enters the node.
     * The enterNode() method can return a changed node, or null if nothing is changed.
     * The enterNode() method can also return the value NodeTraverser::DONT_TRAVERSE_CHILDREN,
     * which instructs the traverser to skip all children of the current node.
     *
     * @param NodeInterface $node
     * @return NodeInterface|string|null
     */
    public function enterNode(NodeInterface $node)
    {
        foreach ($this->reverseVisitors as $visitor) {
            $result = $visitor->enterNode($node);
            if ($result instanceof NodeInterface) {
                $node = $result;
            } elseif ($result == NodeTraverser::DONT_TRAVERSE_CHILDREN) {
                return NodeTraverser::DONT_TRAVERSE_CHILDREN;
            } elseif ($result !== null) {
                throw new TraverserException('Unexpected return value for enterNode. Return value should be a NodeInterface instance or the NodeTraverser::DONT_TRAVERSE_CHILDREN constant or null.');
            }
        }
        return $node;
    }

    /**
     * Called on every node when the traverser leaves the node.
     * The leaveNode() method can return a changed node, or null if nothing is changed.
     * The leaveNode() method can also return the value NodeTraverser::REMOVE_NODE,
     * which instructs the traverser to remove the current node.
     *
     * @param NodeInterface $node
     */
    public function leaveNode(NodeInterface $node)
    {
        foreach ($this->visitors as $visitor) {
            $result = $visitor->leaveNode($node);
            if ($result instanceof NodeInterface) {
                $node = $result;
            } elseif ($result == NodeTraverser::REMOVE_NODE) {
                return NodeTraverser::REMOVE_NODE;
            } elseif ($result !== null) {
                throw new TraverserException('Unexpected return value for leaveNode. Return value should be a NodeInterface instance or the NodeTraverser::REMOVE_NODE constant or null.');
            }
        }
        return $node;
    }
}
