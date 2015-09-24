<?php
namespace SQLParser\Node\Traverser;


use SQLParser\Node\NodeInterface;
use SQLParser\Node\Table;
use SQLParser\Query\Select;

/**
 * This visitor detects magic join selects.
 */
class DetectMagicJoinSelectVisitor implements VisitorInterface
{

    private $lastVisitedSelect;

    private $magicJoinSelects = array();

    /**
     * Removes all detected magic join selects.
     * Useful for reusing the visitor instance on another node traversal.
     */
    public function resetVisitor() {
        $this->magicJoinSelects = array();
    }

    /**
     * Return the list of all Select object that have a MagicJoin table.
     * @return Select[]
     */
    public function getMagicJoinSelects()
    {
        // TODO: throw an exception if the magicjoin table is not the only one
        return $this->magicJoinSelects;
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
        if ($node instanceof Select) {
            $this->lastVisitedSelect = $node;
        } elseif ($node instanceof Table) {
            if (strtolower($node->getTable()) == 'magicjoin') {
                $this->magicJoinSelects[] = $this->lastVisitedSelect;
            }
        }

        return null;
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
        return null;
    }
}
