<?php
namespace SQLParser\Node\Traverser;


use SQLParser\Node\ColRef;
use SQLParser\Node\NodeInterface;
use SQLParser\Query\Select;

/**
 * This visitor is in charge of detecting references to tables in columns.
 * It will throw an exception if a column does not specify a table name.
 */
class DetectTablesVisitor implements VisitorInterface
{

    private $isSelectVisited = false;

    private $tables = array();

    /**
     * Removes all detected magic join selects.
     * Useful for reusing the visitor instance on another node traversal.
     */
    public function resetVisitor() {
        $this->tables = array();
        $this->isSelectVisited = false;
    }

    /**
     * Return the list of tables referenced in the Select.
     * @return Select[]
     */
    public function getTables()
    {
        return $this->tables;
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
            if ($this->isSelectVisited) {
                return NodeTraverser::DONT_TRAVERSE_CHILDREN;
            } else {
                $this->isSelectVisited = true;
            }
        } elseif ($node instanceof ColRef) {
            if (empty($node->getTable())) {
                $e = new MissingTableRefException("All column references should be in the form 'table.column'. Table part is missing for column '".$node->getColumn()."'");
                $e->setMissingTableColRef($node);
                throw $e;
            }
            $this->tables[$node->getTable()] = $node->getTable();
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
