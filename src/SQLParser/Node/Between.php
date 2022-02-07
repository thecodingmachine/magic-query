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
    /** @var NodeInterface|NodeInterface[]|string */
    private $leftOperand;

    /**
     * @return NodeInterface|NodeInterface[]|string
     */
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
     * @var NodeInterface
     */
    private $minValueOperand;

    /**
     * @var NodeInterface
     */
    private $maxValueOperand;

    /**
     * @return NodeInterface
     */
    public function getMinValueOperand()
    {
        return $this->minValueOperand;
    }

    /**
     * @param NodeInterface $minValueOperand
     */
    public function setMinValueOperand(NodeInterface $minValueOperand)
    {
        $this->minValueOperand = $minValueOperand;
    }

    /**
     * @return NodeInterface
     */
    public function getMaxValueOperand()
    {
        return $this->maxValueOperand;
    }

    /**
     * @param NodeInterface $maxValueOperand
     */
    public function setMaxValueOperand(NodeInterface $maxValueOperand): void
    {
        $this->maxValueOperand = $maxValueOperand;
    }

    /**
     * @var ConditionInterface|null
     */
    protected $minValueCondition;

    /**
     * Sets the condition.
     *
     * @Important IfSet
     *
     * @param ConditionInterface|null $minValueCondition
     */
    public function setMinValueCondition(ConditionInterface $minValueCondition = null): void
    {
        $this->minValueCondition = $minValueCondition;
    }

    /**
     * @var ConditionInterface|null
     */
    protected $maxValueCondition;

    /**
     * Sets the condition.
     *
     * @Important IfSet
     *
     * @param ConditionInterface|null $maxValueCondition
     */
    public function setMaxValueCondition(ConditionInterface $maxValueCondition = null): void
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
     * Renders the object as an SQL string.
     *
     * @param array $parameters
     * @param AbstractPlatform $platform
     * @param int $indent
     * @param int $conditionsMode
     *
     * @param bool $extrapolateParameters
     * @return string
     */
    public function toSql(array $parameters, AbstractPlatform $platform, int $indent = 0, $conditionsMode = self::CONDITION_APPLY, bool $extrapolateParameters = true): ?string
    {
        switch ($conditionsMode) {
            case self::CONDITION_APPLY:
                $minBypass = $this->minValueCondition && !$this->minValueCondition->isOk($parameters);
                $maxBypass = $this->maxValueCondition && !$this->maxValueCondition->isOk($parameters);
                break;
            case self::CONDITION_GUESS:
                $minBypass = $this->minValueOperand instanceof Parameter && $this->minValueOperand->isDiscardedOnNull() && !isset($parameters[$this->minValueOperand->getName()]);
                $maxBypass = $this->maxValueOperand instanceof Parameter && $this->maxValueOperand->isDiscardedOnNull() && !isset($parameters[$this->maxValueOperand->getName()]);
                break;
            case self::CONDITION_IGNORE:
                $minBypass = false;
                $maxBypass = false;
                break;
            default:
                throw new \InvalidArgumentException('Invalid `$conditionsMode`: "' . $conditionsMode. '"');
        }

        if ($maxBypass && $minBypass) {
            return null;
        }

        if ($minBypass) {
            return sprintf('%s <= %s',
                NodeFactory::toSql($this->leftOperand, $platform, $parameters, ' ', false, $indent, $conditionsMode, $extrapolateParameters),
                NodeFactory::toSql($this->maxValueOperand, $platform, $parameters, ' ', false, $indent, $conditionsMode, $extrapolateParameters)
            );
        }

        if ($maxBypass) {
            return sprintf('%s >= %s',
                NodeFactory::toSql($this->leftOperand, $platform, $parameters, ' ', false, $indent, $conditionsMode, $extrapolateParameters),
                NodeFactory::toSql($this->minValueOperand, $platform, $parameters, ' ', false, $indent, $conditionsMode, $extrapolateParameters));
        }

        return sprintf('%s BETWEEN %s AND %s',
            NodeFactory::toSql($this->leftOperand, $platform, $parameters, ' ', false, $indent, $conditionsMode, $extrapolateParameters),
            NodeFactory::toSql($this->minValueOperand, $platform, $parameters, ' ', false, $indent, $conditionsMode, $extrapolateParameters),
            NodeFactory::toSql($this->maxValueOperand, $platform, $parameters, ' ', false, $indent, $conditionsMode, $extrapolateParameters)
        );
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
