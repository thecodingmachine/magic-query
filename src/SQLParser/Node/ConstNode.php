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
use Mouf\MoufManager;
use Mouf\MoufInstanceDescriptor;
use SQLParser\Node\Traverser\VisitorInterface;

/**
 * This class represents a constant in an SQL expression.
 *
 * @author David Négrier <d.negrier@thecodingmachine.com>
 */
class ConstNode implements NodeInterface
{
    private $value;
    private $isString = true;

    public function getValue()
    {
        return $this->value;
    }

    /**
     * Sets the value.
     *
     * @Important
     *
     * @param string $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return mixed
     */
    public function getIsString()
    {
        return $this->isString;
    }

    /**
     * @param mixed $isString
     */
    public function setIsString($isString)
    {
        $this->isString = $isString;
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
        $instanceDescriptor->getProperty('value')->setValue(NodeFactory::nodeToInstanceDescriptor($this->value, $moufManager));

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
        if ($this->value === null) {
            return 'NULL';
        } elseif (!$this->isString) {
            return $this->value;
        } elseif ($dbConnection != null) {
            return $dbConnection->quote($this->value);
        } else {
            return "'".addslashes($this->value)."'";
        }
    }

    /**
     * Walks the tree of nodes, calling the visitor passed in parameter.
     *
     * @param VisitorInterface $visitor
     *
     * @return NodeInterface|null|string Can return null if nothing is to be done or a node that should replace this node, or NodeTraverser::REMOVE_NODE to remove the node
     */
    public function walk(VisitorInterface $visitor)
    {
        $node = $this;
        $result = $visitor->enterNode($node);
        if ($result instanceof NodeInterface) {
            $node = $result;
        }

        return $visitor->leaveNode($node);
    }
}
