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
use Mouf\MoufInstanceDescriptor;
use Mouf\MoufManager;
use SQLParser\Node\Traverser\VisitorInterface;

/**
 * This class represents a parameter (as in parameterized query).
 *
 * @author David Négrier <d.negrier@thecodingmachine.com>
 */
class Parameter implements NodeInterface
{
    protected $name;
    protected $discardedOnNull = true;

    /**
     * Returns the name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the name.
     * If the name ends with !, the parameter will be considered not nullable (regarding magicparameter settings).
     *
     * @Important
     *
     * @param string $name
     */
    public function setName($name)
    {
        if (strrpos($name, '!') === strlen($name) - 1) {
            $this->name = substr($name, 0, strlen($name) - 1);
            $this->discardedOnNull = false;
        } else {
            $this->name = $name;
            $this->discardedOnNull = true;
        }
    }

    /**
     * @var string
     */
    protected $autoPrepend;

    /**
     * @var string
     */
    protected $autoAppend;

    /**
     * @return string
     */
    public function getAutoPrepend()
    {
        return $this->autoPrepend;
    }

    /**
     * Sets a string that will automatically be appended to the parameter, if the parameter is available.
     * Very useful to automatically add "%" to a parameter used in a LIKE.
     *
     * @Important IfSet
     *
     * @param string $autoPrepend
     */
    public function setAutoPrepend($autoPrepend)
    {
        $this->autoPrepend = $autoPrepend;

        return $this;
    }

    /**
     * @return string
     */
    public function getAutoAppend()
    {
        return $this->autoAppend;
    }

    /**
     * Sets a string that will automatically be preprended to the parameter, if the parameter is available.
     * Very useful to automatically add "%" to a parameter used in a LIKE.
     *
     * @Important IfSet
     *
     * @param string $autoAppend
     */
    public function setAutoAppend($autoAppend)
    {
        $this->autoAppend = $autoAppend;

        return $this;
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
        $instanceDescriptor->getProperty('name')->setValue($this->name);
        $instanceDescriptor->getProperty('autoPrepend')->setValue($this->autoPrepend);
        $instanceDescriptor->getProperty('autoAppend')->setValue($this->autoAppend);

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
        if ($extrapolateParameters && isset($parameters[$this->name])) {
            if ($dbConnection) {
                if (is_array($parameters[$this->name])) {
                    return '('.implode(',', array_map(function ($item) use ($dbConnection) {
                            return $dbConnection->quote($this->autoPrepend.$item.$this->autoAppend);
                        }, $parameters[$this->name])).')';
                } else {
                    return $dbConnection->quote($this->autoPrepend.$parameters[$this->name].$this->autoAppend);
                }
            } else {
                if ($parameters[$this->name] === null) {
                    return 'null';
                } else {
                    if (is_array($parameters[$this->name])) {
                        return '('.implode(',', array_map(function ($item) {
                            return "'".addslashes($this->autoPrepend.$item.$this->autoAppend)."'";
                        }, $parameters[$this->name])).')';
                    } else {
                        return "'".addslashes($this->autoPrepend.$parameters[$this->name].$this->autoAppend)."'";
                    }
                }
            }
        } elseif ($extrapolateParameters && !$this->isDiscardedOnNull()) {
            return 'null';
        } else {
            return ':'.$this->name;
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

    /**
     * Returns whether the parameter can be discarded if provided value is null.
     *
     * @return bool
     */
    public function isDiscardedOnNull()
    {
        return $this->discardedOnNull;
    }
}
