<?php

namespace SQLParser\Node;

use Doctrine\DBAL\Platforms\AbstractPlatform;
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
    /** @var NodeInterface|NodeInterface[]|string */
    private $operation;

    /**
     * @return NodeInterface|NodeInterface[]|string
     */
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
    public function setOperation($operation): void
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
        $sql = 'CASE '.NodeFactory::toSql($this->operation, $platform, $parameters, ' ', false, $indent, $conditionsMode).' END';

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
