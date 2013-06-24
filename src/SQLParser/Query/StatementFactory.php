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

namespace SQLParser\Query;

use Mouf\MoufManager;

use Mouf\MoufInstanceDescriptor;

use SQLParser\Node\NodeFactory;

use SQLParser\ExpressionType;

/**
 * This class has the ability to create instances implementing NodeInterface based on a descriptive array.
 * 
 * @author David Négrier <d.negrier@thecodingmachine.com>
 */
class StatementFactory {
	
	public static function toObject(array $desc) {
		if (isset($desc['SELECT'])) {
			$select = new Select();
			
			$columns = array_map(function($item) {
				return NodeFactory::toObject($item);
			}, $desc['SELECT']);
			$columns = NodeFactory::simplify($columns);
			$select->setColumns($columns);
			
			if (isset($desc['OPTIONS'])) {
				$options = $desc['OPTIONS'];
				$key = array_search('DISTINCT', $options);
				if ($key !== false) {
					$select->setDistinct(true);
					unset($options[$key]);
				} else {
					$select->setDistinct(false);
				}
				$select->setOptions($options);
			}
			
			if (isset($desc['FROM'])) {
				$from = array_map(function($item) {
					return NodeFactory::toObject($item);
				}, $desc['FROM']);
				$select->setFrom($from);
			}

			if (isset($desc['WHERE'])) {
				$where = array_map(function($item) {
					return NodeFactory::toObject($item);
				}, $desc['WHERE']);
				$where = NodeFactory::simplify($where);
				$select->setWhere($where);
			}
				
			if (isset($desc['GROUP'])) {
				$group = array_map(function($item) {
					return NodeFactory::toObject($item);
				}, $desc['GROUP']);
				$select->setGroup($group);
			}

			if (isset($desc['HAVING'])) {
				$having = array_map(function($item) {
					return NodeFactory::toObject($item);
				}, $desc['HAVING']);
				$select->setHaving($having);
			}
			
			if (isset($desc['ORDER'])) {
				$order = array_map(function($item) {
					return NodeFactory::toObject($item);
				}, $desc['ORDER']);
				$select->setOrder($order);
			}
			
			return $select;
			
		} else {
			throw new \BadMethodCallException("Unknown query");
		}		
	}
}