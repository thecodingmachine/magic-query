<?php

namespace SQLParser\Node;

use Doctrine\DBAL\Connection;
use Mouf\MoufManager;
use Mouf\MoufInstanceDescriptor;
use SQLParser\Node\Traverser\NodeTraverser;
use SQLParser\Node\Traverser\VisitorInterface;

/**
 * This class represents a CASE ... END statement.
 *
 * @author David Négrier <d.negrier@thecodingmachine.com>
 */
class CaseOperation implements NodeInterface
{
    private $operation;

    public function getOperation()
    {
        return $this->operation;
    }

    /**
     * Sets the operation.
     *
     * @Important
     *
     * @param NodeInterface|NodeInterface[]|string $operation
     */
    public function setOperation($operation)
    {
        $this->operation = $operation;
    }

    /**
     * Returns a Mouf instance descriptor describing this object.
     *
     * @param MoufManager $moufManager
     *
     * @return MoufInstanceDescriptor
     */
    public function toInstanceDescriptor(MoufManager $moufManager)
    {
        $instanceDescriptor = $moufManager->createInstance(get_called_class());
        $instanceDescriptor->getProperty('operation')->setValue(NodeFactory::nodeToInstanceDescriptor($this->operation, $moufManager));

        return $instanceDescriptor;
    }

    /**
     * Renders the object as a SQL string.
     *
     * @param Connection $dbConnection
     * @param array      $parameters
     * @param number     $indent
     * @param int        $conditionsMode
     *
     * @return string
     */
    public function toSql(array $parameters = array(), Connection $dbConnection = null, $indent = 0, $conditionsMode = self::CONDITION_APPLY, bool $extrapolateParameters = true)
    {
        $sql = 'CASE '.NodeFactory::toSql($this->operation, $dbConnection, $parameters, ' ', false, $indent, $conditionsMode).' END';

        return $sql;
    }

    /**
     * Walks the tree of nodes, calling the visitor passed in parameter.
     *
     * @param VisitorInterface $visitor
     */
    public function walk(VisitorInterface $visitor)
    {
        $node = $this;
        $result = $visitor->enterNode($node);
        if ($result instanceof NodeInterface) {
            $node = $result;
        }
        if ($result !== NodeTraverser::DONT_TRAVERSE_CHILDREN) {
            $result2 = $this->operation->walk($visitor);
            if ($result2 === NodeTraverser::REMOVE_NODE) {
                return NodeTraverser::REMOVE_NODE;
            } elseif ($result2 instanceof NodeInterface) {
                $this->operation = $result2;
            }
        }

        return $visitor->leaveNode($node);
    }
}
