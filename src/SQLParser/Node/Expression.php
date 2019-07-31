<?php

/**
 * expression-types.php.
 *
 *
 * Copyright (c) 2010-2013, Justin Swanhart
 * with contributions by André Rothe <arothe@phosco.info, phosco@gmx.de>
 * and David Négrier <d.negrier@thecodingmachine.com>
 *
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *
 *   * Redistributions of source code must retain the above copyright notice,
 *     this list of conditions and the following disclaimer.
 *   * Redistributions in binary form must reproduce the above copyright notice,
 *     this list of conditions and the following disclaimer in the documentation
 *     and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
 * OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT
 * SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED
 * TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR
 * BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH
 * DAMAGE.
 */
namespace SQLParser\Node;

use Doctrine\DBAL\Connection;
use function is_iterable;
use Mouf\MoufInstanceDescriptor;
use Mouf\MoufManager;
use SQLParser\Node\Traverser\NodeTraverser;
use SQLParser\Node\Traverser\VisitorInterface;

/**
 * This class represents a node that is an SQL expression.
 *
 * @author David Négrier <d.negrier@thecodingmachine.com>
 */
class Expression implements NodeInterface, BypassableInterface
{
    private $baseExpression;

    /**
     * Returns the base expression (the string that generated this expression).
     *
     * @return string
     */
    public function getBaseExpression()
    {
        return $this->baseExpression;
    }

    /**
     * Sets the base expression (the string that generated this expression).
     *
     * @param string $baseExpression
     */
    public function setBaseExpression($baseExpression)
    {
        $this->baseExpression = $baseExpression;
    }

    private $subTree;

    public function getSubTree()
    {
        return $this->subTree;
    }

    /**
     * Sets the subtree.
     *
     * @Important
     *
     * @param array<NodeInterface>|NodeInterface $subTree
     */
    public function setSubTree($subTree)
    {
        $this->subTree = $subTree;
        $this->subTree = NodeFactory::simplify($this->subTree);
    }

    private $alias;

    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * Sets the alias.
     *
     * @Important
     *
     * @param string $alias
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;
    }

    private $direction;

    public function getDiretion()
    {
        return $this->direction;
    }

    /**
     * Sets the direction.
     *
     * @Important
     *
     * @param string $direction
     */
    public function setDirection($direction)
    {
        $this->direction = $direction;
    }

    private $brackets = false;

    /**
     * Returns true if the expression is between brackets.
     *
     * @return bool
     */
    public function hasBrackets()
    {
        return $this->brackets;
    }

    /**
     * Sets to true if the expression is between brackets.
     *
     * @Important
     *
     * @param bool $brackets
     */
    public function setBrackets($brackets)
    {
        $this->brackets = $brackets;
    }

    private $delimiter = ' ';

    /**
     * @return string
     */
    public function getDelimiter()
    {
        return $this->delimiter;
    }

    /**
     * Sets the delimiter for the list. Defaults to ' '.
     *
     * @param string $delimiter
     */
    public function setDelimiter($delimiter)
    {
        $this->delimiter = $delimiter;
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
        $instanceDescriptor->getProperty('baseExpression')->setValue(NodeFactory::nodeToInstanceDescriptor($this->baseExpression, $moufManager));
        $instanceDescriptor->getProperty('subTree')->setValue(NodeFactory::nodeToInstanceDescriptor($this->subTree, $moufManager));
        $instanceDescriptor->getProperty('alias')->setValue(NodeFactory::nodeToInstanceDescriptor($this->alias, $moufManager));
        $instanceDescriptor->getProperty('direction')->setValue(NodeFactory::nodeToInstanceDescriptor($this->direction, $moufManager));
        $instanceDescriptor->getProperty('brackets')->setValue(NodeFactory::nodeToInstanceDescriptor($this->brackets, $moufManager));
        $instanceDescriptor->getProperty('delimiter')->setValue(NodeFactory::nodeToInstanceDescriptor($this->delimiter, $moufManager));

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
     * @return string|null
     */
    public function toSql(array $parameters = array(), Connection $dbConnection = null, $indent = 0, $conditionsMode = self::CONDITION_APPLY, bool $extrapolateParameters = true)
    {
        if (empty($this->subTree)) {
            return $this->getBaseExpression();
        }
        $sql = NodeFactory::toSql($this->subTree, $dbConnection, $parameters, $this->delimiter, false, $indent, $conditionsMode, $extrapolateParameters);

        if ($sql === null) {
            return null;
        }

        if ($this->alias) {
            $sql .= ' AS '.$this->alias;
        }
        if ($this->direction) {
            $sql .= ' '.$this->direction;
        }
        if ($this->brackets) {
            $sql = '('.$sql.')';
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
            if (is_array($this->subTree)) {
                foreach ($this->subTree as $key => $operand) {
                    $result2 = $operand->walk($visitor);
                    if ($result2 === NodeTraverser::REMOVE_NODE) {
                        unset($this->subTree[$key]);
                    } elseif ($result2 instanceof NodeInterface) {
                        $this->subTree[$key] = $result2;
                    }
                }
            } else {
                $result2 = $this->subTree->walk($visitor);
                if ($result2 === NodeTraverser::REMOVE_NODE) {
                    $this->subTree = [];
                } elseif ($result2 instanceof NodeInterface) {
                    $this->subTree = $result2;
                }
            }
        }

        return $visitor->leaveNode($node);
    }

    /**
     * Returns if this node should be removed from the tree.
     */
    public function canBeBypassed(array $parameters): bool
    {
        if (empty($this->subTree)) {
            // Some expression can (rarely) have no subtree. Don't know why.
            return false;
        }
        if (is_iterable($this->subTree)) {
            foreach ($this->subTree as $node) {
                if (!$node instanceof BypassableInterface || !$node->canBeBypassed($parameters)) {
                    return false;
                }
            }
        } elseif (!$this->subTree instanceof BypassableInterface || !$this->subTree->canBeBypassed($parameters)) {
            return false;
        }
        return true;
    }
}
