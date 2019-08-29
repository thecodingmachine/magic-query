<?php

namespace SQLParser\Node;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Mouf\Utils\Common\ConditionInterface\ConditionTrait;
use Mouf\MoufManager;
use Mouf\MoufInstanceDescriptor;
use SQLParser\Node\Traverser\NodeTraverser;
use SQLParser\Node\Traverser\VisitorInterface;

/**
 * This class represents an operation that takes 2 operands (for instance =, <, >, etc...) in an SQL expression.
 *
 * @author David Négrier <d.negrier@thecodingmachine.com>
 */
abstract class AbstractTwoOperandsOperator implements NodeInterface
{
    use ConditionTrait;

    private $leftOperand;

    public function getLeftOperand()
    {
        return $this->leftOperand;
    }

    /**
     * Sets the leftOperand.
     *
     * @Important
     *
     * @param NodeInterface|NodeInterface[]|string $leftOperand
     */
    public function setLeftOperand($leftOperand)
    {
        $this->leftOperand = $leftOperand;
    }

    private $rightOperand;

    public function getRightOperand()
    {
        return $this->rightOperand;
    }

    /**
     * Sets the rightOperand.
     *
     * @Important
     *
     * @param string|NodeInterface|NodeInterface[] $rightOperand
     */
    public function setRightOperand($rightOperand)
    {
        $this->rightOperand = $rightOperand;
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
        $instanceDescriptor->getProperty('leftOperand')->setValue(NodeFactory::nodeToInstanceDescriptor($this->leftOperand, $moufManager));
        $instanceDescriptor->getProperty('rightOperand')->setValue(NodeFactory::nodeToInstanceDescriptor($this->rightOperand, $moufManager));

        if ($this->leftOperand instanceof Parameter) {
            // Let's add a condition on the parameter.
            $conditionDescriptor = $moufManager->createInstance('Mouf\\Database\\QueryWriter\\Condition\\ParamAvailableCondition');
            $conditionDescriptor->getProperty('parameterName')->setValue($this->leftOperand->getName());
            $instanceDescriptor->getProperty('condition')->setValue($conditionDescriptor);
        }
        // TODO: manage cases where both leftOperand and rightOperand are parameters.
        if ($this->rightOperand instanceof Parameter) {
            // Let's add a condition on the parameter.
            $conditionDescriptor = $moufManager->createInstance('Mouf\\Database\\QueryWriter\\Condition\\ParamAvailableCondition');
            $conditionDescriptor->getProperty('parameterName')->setValue($this->rightOperand->getName());
            $instanceDescriptor->getProperty('condition')->setValue($conditionDescriptor);
        }

        return $instanceDescriptor;
    }

    /**
     * Renders the object as a SQL string.
     *
     * @param array $parameters
     * @param AbstractPlatform $platform
     * @param int $indent
     * @param int $conditionsMode
     *
     * @param bool $extrapolateParameters
     * @return string
     */
    public function toSql(array $parameters, AbstractPlatform $platform, $indent = 0, $conditionsMode = self::CONDITION_APPLY, bool $extrapolateParameters = true): ?string
    {
        if ($conditionsMode == self::CONDITION_GUESS) {
            $bypass = false;
            if ($this->leftOperand instanceof BypassableInterface && $this->leftOperand->canBeBypassed($parameters)) {
                $bypass = true;
            }
            if ($this->rightOperand instanceof BypassableInterface && $this->rightOperand->canBeBypassed($parameters)) {
                $bypass = true;
            }
            if ($bypass === true) {
                return null;
            } else {
                $conditionsMode = self::CONDITION_IGNORE;
            }
        }
        if ($conditionsMode == self::CONDITION_IGNORE || !$this->condition || $this->condition->isOk($parameters)) {
            $sql = $this->getSql($parameters, $platform, $indent, $conditionsMode, $extrapolateParameters);
        } else {
            $sql = null;
        }

        return $sql;
    }

    protected function getSql(array $parameters, AbstractPlatform $platform, $indent = 0, $conditionsMode = self::CONDITION_APPLY, bool $extrapolateParameters = true)
    {
        $sql = NodeFactory::toSql($this->leftOperand, $platform, $parameters, ' ', false, $indent, $conditionsMode, $extrapolateParameters);
        $sql .= ' '.$this->getOperatorSymbol().' ';
        $sql .= NodeFactory::toSql($this->rightOperand, $platform, $parameters, ' ', false, $indent, $conditionsMode, $extrapolateParameters);

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
            $result2 = $this->leftOperand->walk($visitor);
            if ($result2 === NodeTraverser::REMOVE_NODE) {
                return NodeTraverser::REMOVE_NODE;
            } elseif ($result2 instanceof NodeInterface) {
                $this->leftOperand = $result2;
            }

            $result2 = $this->rightOperand->walk($visitor);
            if ($result2 === NodeTraverser::REMOVE_NODE) {
                return NodeTraverser::REMOVE_NODE;
            } elseif ($result2 instanceof NodeInterface) {
                $this->rightOperand = $result2;
            }
        }

        return $visitor->leaveNode($node);
    }

    /**
     * Returns the symbol for this operator.
     */
    abstract protected function getOperatorSymbol();
}
