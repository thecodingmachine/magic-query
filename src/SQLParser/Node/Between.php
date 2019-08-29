<?php

namespace SQLParser\Node;

use Doctrine\DBAL\Platforms\AbstractPlatform;

use Mouf\MoufManager;
use Mouf\MoufInstanceDescriptor;
use Mouf\Utils\Common\ConditionInterface\ConditionInterface;
use SQLParser\Node\Traverser\NodeTraverser;
use SQLParser\Node\Traverser\VisitorInterface;

/**
 * This class represents a BETWEEN operation.
 *
 * @author David Négrier <d.negrier@thecodingmachine.com>
 */
class Between implements NodeInterface
{
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

    /**
     * @var string|NodeInterface|NodeInterface[]
     */
    private $minValueOperand;

    /**
     * @var string|NodeInterface|NodeInterface[]
     */
    private $maxValueOperand;

    /**
     * @return NodeInterface|NodeInterface[]|string
     */
    public function getMinValueOperand()
    {
        return $this->minValueOperand;
    }

    /**
     * @param NodeInterface|NodeInterface[]|string $minValueOperand
     */
    public function setMinValueOperand($minValueOperand)
    {
        $this->minValueOperand = $minValueOperand;
    }

    /**
     * @return NodeInterface|NodeInterface[]|string
     */
    public function getMaxValueOperand()
    {
        return $this->maxValueOperand;
    }

    /**
     * @param NodeInterface|NodeInterface[]|string $maxValueOperand
     */
    public function setMaxValueOperand($maxValueOperand)
    {
        $this->maxValueOperand = $maxValueOperand;
    }

    /**
     * @var ConditionInterface
     */
    protected $minValueCondition;

    /**
     * Sets the condition.
     *
     * @Important IfSet
     *
     * @param ConditionInterface $minValueCondition
     */
    public function setMinValueCondition(ConditionInterface $minValueCondition = null)
    {
        $this->minValueCondition = $minValueCondition;
    }

    /**
     * @var ConditionInterface
     */
    protected $maxValueCondition;

    /**
     * Sets the condition.
     *
     * @Important IfSet
     *
     * @param ConditionInterface $maxValueCondition
     */
    public function setMaxValueCondition(ConditionInterface $maxValueCondition = null)
    {
        $this->maxValueCondition = $maxValueCondition;
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
        $instanceDescriptor->getProperty('minValueOperand')->setValue(NodeFactory::nodeToInstanceDescriptor($this->minValueOperand, $moufManager));
        $instanceDescriptor->getProperty('maxValueOperand')->setValue(NodeFactory::nodeToInstanceDescriptor($this->maxValueOperand, $moufManager));

        if ($this->minValueOperand instanceof Parameter) {
            // Let's add a condition on the parameter.
            $conditionDescriptor = $moufManager->createInstance('Mouf\\Database\\QueryWriter\\Condition\\ParamAvailableCondition');
            $conditionDescriptor->getProperty('parameterName')->setValue($this->minValueOperand->getName());
            $instanceDescriptor->getProperty('minValueCondition')->setValue($conditionDescriptor);
        }

        if ($this->maxValueOperand instanceof Parameter) {
            // Let's add a condition on the parameter.
            $conditionDescriptor = $moufManager->createInstance('Mouf\\Database\\QueryWriter\\Condition\\ParamAvailableCondition');
            $conditionDescriptor->getProperty('parameterName')->setValue($this->maxValueOperand->getName());
            $instanceDescriptor->getProperty('maxValueCondition')->setValue($conditionDescriptor);
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
        $minBypass = false;
        $maxBypass = false;

        if ($conditionsMode == self::CONDITION_GUESS) {
            if ($this->minValueOperand instanceof Parameter) {
                if ($this->minValueOperand->isDiscardedOnNull() && !isset($parameters[$this->minValueOperand->getName()])) {
                    $minBypass = true;
                }
            }

            if ($this->maxValueOperand instanceof Parameter) {
                if ($this->maxValueOperand->isDiscardedOnNull() && !isset($parameters[$this->maxValueOperand->getName()])) {
                    $maxBypass = true;
                }
            }
        } elseif ($conditionsMode == self::CONDITION_IGNORE) {
            $minBypass = false;
            $maxBypass = false;
        } else {
            if ($this->minValueCondition && !$this->minValueCondition->isOk($parameters)) {
                $minBypass = true;
            }
            if ($this->maxValueCondition && !$this->maxValueCondition->isOk($parameters)) {
                $maxBypass = true;
            }
        }

        if (!$minBypass && !$maxBypass) {
            $sql = NodeFactory::toSql($this->leftOperand, $platform, $parameters, ' ', false, $indent, $conditionsMode, $extrapolateParameters);
            $sql .= ' BETWEEN ';
            $sql .= NodeFactory::toSql($this->minValueOperand, $platform, $parameters, ' ', false, $indent, $conditionsMode, $extrapolateParameters);
            $sql .= ' AND ';
            $sql .= NodeFactory::toSql($this->maxValueOperand, $platform, $parameters, ' ', false, $indent, $conditionsMode, $extrapolateParameters);
        } elseif (!$minBypass && $maxBypass) {
            $sql = NodeFactory::toSql($this->leftOperand, $platform, $parameters, ' ', false, $indent, $conditionsMode, $extrapolateParameters);
            $sql .= ' >= ';
            $sql .= NodeFactory::toSql($this->minValueOperand, $platform, $parameters, ' ', false, $indent, $conditionsMode, $extrapolateParameters);
        } elseif ($minBypass && !$maxBypass) {
            $sql = NodeFactory::toSql($this->leftOperand, $platform, $parameters, ' ', false, $indent, $conditionsMode, $extrapolateParameters);
            $sql .= ' <= ';
            $sql .= NodeFactory::toSql($this->maxValueOperand, $platform, $parameters, ' ', false, $indent, $conditionsMode, $extrapolateParameters);
        } else {
            $sql = null;
        }

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

            $result2 = $this->minValueOperand->walk($visitor);
            if ($result2 === NodeTraverser::REMOVE_NODE) {
                return NodeTraverser::REMOVE_NODE;
            } elseif ($result2 instanceof NodeInterface) {
                $this->minValueOperand = $result2;
            }

            $result2 = $this->maxValueOperand->walk($visitor);
            if ($result2 === NodeTraverser::REMOVE_NODE) {
                return NodeTraverser::REMOVE_NODE;
            } elseif ($result2 instanceof NodeInterface) {
                $this->maxValueOperand = $result2;
            }
        }

        return $visitor->leaveNode($node);
    }
}
