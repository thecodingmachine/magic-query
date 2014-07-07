<?php 
/**
 * expression-types.php
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

use Mouf\Database\DBConnection\ConnectionInterface;

use Mouf\MoufManager;

use Mouf\MoufInstanceDescriptor;

/**
 * This class represents an aggregation expression (like COUNT, SUM...) that is an SQL expression. 
 * 
 * @author David Négrier <d.negrier@thecodingmachine.com>
 */
class AggregateFunction implements NodeInterface {
	
	private $functionName;
	
	/**
	 * Returns the base expression (the string that generated this expression).
	 * 
	 * @return string
	 */
	public function getFunctionName() {
		return $this->functionName;
	}
	
	/**
	 * Sets the base expression (the string that generated this expression).
	 * 
	 * @Important
	 * @param string $functionName
	 */
	public function setFunctionName($functionName) {
		$this->functionName = $functionName;
	}
	
	private $subTree;
	
	public function getSubTree() {
		return $this->subTree;
	}
	
	/**
	 * Sets the subtree
	 *
	 * @Important
	 * @param array<NodeInterface> $subTree
	 */
	public function setSubTree($subTree) {
		$this->subTree = $subTree;
	}
	
	private $alias;
	
	public function getAlias() {
		return $this->alias;
	}
	
	/**
	 * Sets the alias
	 *
	 * @param string $alias
	 */
	public function setAlias($alias) {
		$this->alias = $alias;
	}
	
	/**
	 * Returns a Mouf instance descriptor describing this object.
	 *
	 * @param MoufManager $moufManager
	 * @return MoufInstanceDescriptor
	 */
	public function toInstanceDescriptor(MoufManager $moufManager) {
		$instanceDescriptor = $moufManager->createInstance(get_called_class());
		$instanceDescriptor->getProperty("functionName")->setValue($this->functionName, $moufManager);
		$instanceDescriptor->getProperty("subTree")->setValue(NodeFactory::nodeToInstanceDescriptor($this->subTree, $moufManager));
		$instanceDescriptor->getProperty("alias")->setValue($this->alias, $moufManager);
		return $instanceDescriptor;
	}
	
	/**
	 * Renders the object as a SQL string
	 *
	 * @param ConnectionInterface $dbConnection
	 * @param array $parameters
	 * @param number $indent
	 * @param bool $ignoreConditions
	 * @return string
	 */
	public function toSql(array $parameters = array(), ConnectionInterface $dbConnection = null, $indent = 0, $ignoreConditions = false) {
		$subTreeSql = NodeFactory::toSql($this->subTree, $dbConnection, $parameters, ' ', false, $indent, $ignoreConditions);
		if ($subTreeSql !== null) {
			$sql = $this->functionName.'(';
			$sql .= $subTreeSql;
			$sql .= ')';
			if ($this->alias) {
			// defensive fix:
                $alias = is_array($this->alias)?$this->alias['name']:$this->alias;

                $sql .= ' AS '.$alias;
			}
		} else {
			$sql = null;
		}
	
		return $sql;
	}
}