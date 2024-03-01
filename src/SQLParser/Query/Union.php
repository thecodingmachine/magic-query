<?php

namespace SQLParser\Query;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Mouf\MoufInstanceDescriptor;
use SQLParser\Node\NodeFactory;
use Mouf\MoufManager;
use SQLParser\Node\NodeInterface;
use SQLParser\Node\Traverser\NodeTraverser;
use SQLParser\Node\Traverser\VisitorInterface;

/**
 * This class represents a <code>UNION</code> query. You can use it to generate a SQL query statement
 * using the <code>toSql</code> method.
 * You can use the <code>QueryResult</code> class if you want to run the query directly.
 *
 * @author David Négrier <d.negrier@thecodingmachine.com>
 */
class Union implements StatementInterface, NodeInterface
{
    /**
     * @var array|Select[]
     */
    private $selects;

    /**
     * @var bool
     */
    private $isUnionAll;

    /**
     * Union constructor.
     * @param Select[] $selects
     */
    public function __construct(array $selects, bool $isUnionAll)
    {
        $this->selects = $selects;
        $this->isUnionAll = $isUnionAll;
    }

    /** @var NodeInterface[]|NodeInterface */
    private $order;

    /**
     * Returns the list of order statements.
     *
     * @return NodeInterface[]|NodeInterface
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Sets the list of order statements.
     *
     * @param NodeInterface[]|NodeInterface $order
     */
    public function setOrder($order): void
    {
        $this->order = $order;
    }

    /**
     * @param MoufManager $moufManager
     *
     * @return MoufInstanceDescriptor
     */
    public function toInstanceDescriptor(MoufManager $moufManager)
    {
        $instanceDescriptor = $moufManager->createInstance(get_called_class());
        $instanceDescriptor->getProperty('selects')->setValue(NodeFactory::nodeToInstanceDescriptor($this->selects, $moufManager));
        $instanceDescriptor->getProperty('order')->setValue(NodeFactory::nodeToInstanceDescriptor($this->order, $moufManager));

        return $instanceDescriptor;
    }

    /**
     * Configure the $instanceDescriptor describing this object (it must already exist as a Mouf instance).
     *
     * @param MoufManager $moufManager
     *
     * @return MoufInstanceDescriptor
     */
    public function overwriteInstanceDescriptor($name, MoufManager $moufManager)
    {
        //$name = $moufManager->findInstanceName($this);
        $instanceDescriptor = $moufManager->getInstanceDescriptor($name);
        $instanceDescriptor->getProperty('selects')->setValue(NodeFactory::nodeToInstanceDescriptor($this->selects, $moufManager));
        $instanceDescriptor->getProperty('order')->setValue(NodeFactory::nodeToInstanceDescriptor($this->order, $moufManager));

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
    public function toSql(array $parameters, AbstractPlatform $platform, int $indent = 0, $conditionsMode = self::CONDITION_APPLY, bool $extrapolateParameters = true): ?string
    {
        $selectsSql = array_map(function(Select $select) use ($parameters, $platform, $indent, $conditionsMode, $extrapolateParameters) {
            return $select->toSql($parameters, $platform, $indent, $conditionsMode, $extrapolateParameters);
        }, $this->selects);

        $unionStatement = $this->isUnionAll ? 'UNION ALL' : 'UNION';

        $sql = '(' . implode(') ' . $unionStatement . ' (', $selectsSql) . ')';

        if (!empty($this->order)) {
            $order = NodeFactory::toSql($this->order, $platform, $parameters, ',', false, $indent + 2, $conditionsMode, $extrapolateParameters);
            if ($order) {
                $sql .= "\nORDER BY ".$order;
            }
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
            $this->walkChildren($this->selects, $visitor);
            $this->walkChildren($this->order, $visitor);
        }

        return $visitor->leaveNode($node);
    }

    /**
     * @param array<Select|NodeInterface|null>|NodeInterface|null $children
     * @param VisitorInterface $visitor
     */
    private function walkChildren(&$children, VisitorInterface $visitor): void
    {
        if ($children) {
            if (is_array($children)) {
                foreach ($children as $key => $operand) {
                    if ($operand) {
                        $result2 = $operand->walk($visitor);
                        if ($result2 === NodeTraverser::REMOVE_NODE) {
                            unset($children[$key]);
                        } elseif ($result2 instanceof NodeInterface) {
                            $children[$key] = $result2;
                        }
                    }
                }
            } else {
                $result2 = $children->walk($visitor);
                if ($result2 === NodeTraverser::REMOVE_NODE) {
                    $children = null;
                } elseif ($result2 instanceof NodeInterface) {
                    $children = $result2;
                }
            }
        }
    }
}
