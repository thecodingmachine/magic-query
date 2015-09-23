<?php
namespace SQLParser\Node\Traverser;


use SQLParser\Node\NodeInterface;
use SQLParser\Query\Select;

/**
 * This visitor detects magic join selects.
 */
class DetectMagicJoinSelectVisitor implements VisitorInterface
{

    private $lastVisitedSelect;

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
        if ($node instanceof Select)

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
