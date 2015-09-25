<?php

namespace SQLParser\Node;

use Doctrine\DBAL\Connection;
use Mouf\MoufManager;
use Mouf\MoufInstanceDescriptor;
use SQLParser\Node\Traverser\NodeTraverser;
use SQLParser\Node\Traverser\VisitorInterface;

/**
 * This class represents an operator with many operators (AND, OR...) in an SQL expression.
 *
 * @author David Négrier <d.negrier@thecodingmachine.com>
 */
abstract class AbstractManyInstancesOperator implements NodeInterface
{
    private $operands;

    public function getOperands()
    {
        return $this->operands;
    }

    /**
     * Sets the operands.
     *
     * @Important
     * //@param array<array<NodeInterface>> $operands
     *
     * @param array<NodeInterface> $operands
     */
    public function setOperands($operands)
    {
        if (!is_array($operands)) {
            $operands = array($operands);
        }
        $this->operands = $operands;
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
        $instanceDescriptor->getProperty('operands')->setValue(NodeFactory::nodeToInstanceDescriptor($this->operands, $moufManager));

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
    public function toSql(array $parameters = array(), Connection $dbConnection = null, $indent = 0, $conditionsMode = self::CONDITION_APPLY)
    {
        $sqlOperands = array();
        foreach ($this->operands as $operand) {
            $sql = NodeFactory::toSql($operand, $dbConnection, $parameters, ' ', true, $indent, $conditionsMode);
            if ($sql != null) {
                $sqlOperands[] = $sql;
            }
        }

        return implode("\n".str_repeat(' ', $indent).$this->getOperatorSymbol().' ', $sqlOperands);
    }

    /**
     * Walks the tree of nodes, calling the visitor passed in parameter.
     *
     * @param VisitorInterface $visitor
     */
    public function walk(VisitorInterface $visitor) {
        $node = $this;
        $result = $visitor->enterNode($node);
        if ($result instanceof NodeInterface) {
            $node = $result;
        }
        if ($result !== NodeTraverser::DONT_TRAVERSE_CHILDREN) {
            foreach ($this->operands as $key => $operand) {
                $result2 = $operand->walk($visitor);
                if ($result2 === NodeTraverser::REMOVE_NODE) {
                    unset($this->operands[$key]);
                } elseif ($result2 instanceof NodeInterface) {
                    $this->operands[$key] = $result2;
                }
            }
        }
        return $visitor->leaveNode($node);
    }

    /**
     * Returns the symbol for this operator.
     *
     * @return string
     */
    abstract protected function getOperatorSymbol();
}
