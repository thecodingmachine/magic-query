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

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Mouf\MoufInstanceDescriptor;
use Mouf\MoufManager;
use SQLParser\Node\Traverser\NodeTraverser;
use SQLParser\Node\Traverser\VisitorInterface;

/**
 * This class represents a table (and optionally a JOIN .. ON expression) in a SQL expression.
 *
 * @author David Négrier <d.negrier@thecodingmachine.com>
 */
class Table implements NodeInterface
{
    private $database;

    /**
     * Returns the name of the database.
     *
     * @return mixed
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * Sets the name of the database
     *
     * @param mixed $database
     */
    public function setDatabase($database)
    {
        $this->database = $database;
    }

    private $table;

    /**
     * Returns the table name.
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Sets the table name.
     *
     * @Important
     *
     * @param string $table
     */
    public function setTable($table)
    {
        $this->table = $table;
    }

    private $alias;

    /**
     * Returns the alias.
     *
     * @return string
     */
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

    private $joinType;

    /**
     * Returns the join type.
     *
     * @return string
     */
    public function getJoinType()
    {
        return $this->joinType;
    }

    /**
     * Sets the join type (JOIN, LEFT JOIN, RIGHT JOIN, etc...).
     *
     * @Important
     *
     * @param string $joinType
     */
    public function setJoinType($joinType)
    {
        $this->joinType = $joinType;
    }

    private $refClause;

    /**
     * Returns the list of refClause statements.
     *
     * @return NodeInterface[]|NodeInterface
     */
    public function getRefClause()
    {
        return $this->refClause;
    }

    /**
     * Sets the list of refClause statements.
     *
     * @Important
     *
     * @param NodeInterface[]|NodeInterface $refClause
     */
    public function setRefClause($refClause)
    {
        $this->refClause = $refClause;
    }

    /**
     * @var Hint[]
     */
    private $hints;

    /**
     * Return the list of table hints
     *
     * @return Hint[]
     */
    public function getHints(): array
    {
        return $this->hints;
    }

    /**
     * Set a list of table hints
     *
     * @param Hint[] $hints
     */
    public function setHints(array $hints): void
    {
        $this->hints = $hints;
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
        $instanceDescriptor->getProperty('database')->setValue($this->database);
        $instanceDescriptor->getProperty('table')->setValue($this->table);
        $instanceDescriptor->getProperty('alias')->setValue($this->alias);
        $instanceDescriptor->getProperty('joinType')->setValue($this->joinType);
        $instanceDescriptor->getProperty('refClause')->setValue(NodeFactory::nodeToInstanceDescriptor($this->refClause, $moufManager));

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
        $sql = '';
        if ($this->refClause || $this->joinType === 'CROSS JOIN') {
            $sql .= "\n  ".$this->joinType.' ';
        }
        if ($this->database) {
            $sql .= $platform->quoteSingleIdentifier($this->database).'.';
        }
        $sql .= $platform->quoteSingleIdentifier($this->table);
        if ($this->alias) {
            $sql .= ' '.$platform->quoteSingleIdentifier($this->alias);
        }
        if ($this->hints) {
            foreach ($this->hints as $hint) {
                $sql .= ' ' . $hint->getType() . ' ' . $hint->getList();
            }
        }
        if ($this->refClause) {
            $sql .= ' ON ';
            $sql .= NodeFactory::toSql($this->refClause, $platform, $parameters, ' ', true, $indent, $conditionsMode, $extrapolateParameters);
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
            if (is_array($this->refClause)) {
                foreach ($this->refClause as $key => $operand) {
                    $result2 = $operand->walk($visitor);
                    if ($result2 === NodeTraverser::REMOVE_NODE) {
                        unset($this->refClause[$key]);
                    } elseif ($result2 instanceof NodeInterface) {
                        $this->refClause[$key] = $result2;
                    }
                }
            } elseif ($this->refClause) {
                $result2 = $this->refClause->walk($visitor);
                if ($result2 === NodeTraverser::REMOVE_NODE) {
                    $this->refClause = null;
                } elseif ($result2 instanceof NodeInterface) {
                    $this->refClause = $result2;
                }
            }
        }

        return $visitor->leaveNode($node);
    }
}
