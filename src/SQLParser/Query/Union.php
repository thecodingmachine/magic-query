<?php

namespace SQLParser\Query;

use Doctrine\DBAL\Connection;
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
     * Union constructor.
     * @param Select[] $selects
     */
    public function __construct(array $selects)
    {
        $this->selects = $selects;
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

        return $instanceDescriptor;
    }

    /**
     * Renders the object as a SQL string.
     *
     * @param array      $parameters
     * @param Connection $dbConnection
     * @param int|number $indent
     * @param int        $conditionsMode
     *
     * @return string
     */
    public function toSql(array $parameters = array(), Connection $dbConnection = null, $indent = 0, $conditionsMode = self::CONDITION_APPLY, bool $extrapolateParameters = true)
    {
        $selectsSql = array_map(function(Select $select) use ($parameters, $dbConnection, $indent, $conditionsMode, $extrapolateParameters) {
            return $select->toSql($parameters, $dbConnection, $indent, $conditionsMode, $extrapolateParameters);
        }, $this->selects);

        $sql = implode(' UNION ', $selectsSql);

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
        }

        return $visitor->leaveNode($node);
    }

    /**
     * @param Select[] $children
     * @param VisitorInterface $visitor
     */
    private function walkChildren(array &$children, VisitorInterface $visitor)
    {
        if ($children) {
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
        }
    }
}
