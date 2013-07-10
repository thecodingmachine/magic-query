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

/**
 * This class represents an column in an SQL expression. 
 * 
 * @author David Négrier <d.negrier@thecodingmachine.com>
 */
class ColRef implements NodeInterface {

	private $table;
	
	/**
	 * Returns the table name
	 *
	 * @return string
	 */
	public function getTable() {
		return $this->table;
	}
	
	/**
	 * Sets the table name
	 *
	 * @Important
	 * @param string $table
	 */
	public function setTable($table) {
		$this->table = $table;
	}
	
	
	private $column;
	
	/**
	 * Returns the column name
	 * 
	 * @return string
	 */
	public function getColumn() {
		return $this->column;
	}
	
	/**
	 * Sets the column name
	 *
	 * @Important
	 * @param string $column
	 */
	public function setColumn($column) {
		$this->column = $column;
	}

	private $alias;
	
	/**
	 * Returns the alias
	 *
	 * @return string
	 */
	public function getAlias() {
		return $this->alias;
	}
	
	/**
	 * Sets the alias
	 *
	 * @Important
	 * @param string $alias
	 */
	public function setAlias($alias) {
		$this->alias = $alias;
	}
	
	private $direction;
	
	/**
	 * Returns the direction
	 *
	 * @return string
	 */
	public function getDirection() {
		return $this->direction;
	}
	
	/**
	 * Sets the direction
	 *
	 * @Important
	 * @param string $direction
	 */
	public function setDirection($direction) {
		$this->direction = $direction;
	}
	
	
	/**
	 * Returns a Mouf instance descriptor describing this object.
	 *
	 * @param MoufManager $moufManager
	 * @return MoufInstanceDescriptor
	 */
	public function toInstanceDescriptor(MoufManager $moufManager) {
		$instanceDescriptor = $moufManager->createInstance(get_called_class());
		$instanceDescriptor->getProperty("table")->setValue($this->table);
		$instanceDescriptor->getProperty("column")->setValue($this->column);
		$instanceDescriptor->getProperty("alias")->setValue($this->alias);
		$instanceDescriptor->getProperty("direction")->setValue($this->direction);
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
		$sql = '';
		if ($this->table) {
			$sql .= NodeFactory::escapeDBItem($this->table, $dbConnection).'.';
		}
		if ($this->column != '*') {
			$sql .= NodeFactory::escapeDBItem($this->column, $dbConnection);
		} else {
			$sql .= '*';
		}
		if ($this->alias) {
			$sql .= ' AS '.$this->alias;
		}
		if ($this->direction) {
			$sql .= ' '.$this->direction;
		}
		return $sql;
	}
}