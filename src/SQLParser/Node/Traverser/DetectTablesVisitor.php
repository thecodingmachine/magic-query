<?php
namespace SQLParser\Node\Traverser;


use SQLParser\Node\ColRef;
use SQLParser\Node\NodeInterface;
use SQLParser\Query\Select;

/**
 * This visitor is in charge of detecting references to tables in columns.
 * Also, it will modify all references to tables that have no table specified by adding the $defaultTable
 */
class DetectTablesVisitor implements VisitorInterface
{

    private $isSelectVisited = false;

    private $tables = array();

    private $defaultTable;

    /**
     * Removes all detected magic join selects.
     * Useful for reusing the visitor instance on another node traversal.
     */
    public function resetVisitor() {
        $this->tables = array();
        $this->isSelectVisited = false;
    }

    /**
     * @param string $defaultTable Sets the default table that will be used if no table is specified in a colref.
     */
    public function __construct($defaultTable)
    {
        $this->defaultTable = $defaultTable;
    }



    /**
     * Return the list of tables referenced in the Select.
     * @return string[] The key and the value are the table name.
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
                $node->setTable($this->defaultTable);
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
