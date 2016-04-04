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
namespace SQLParser\Query;

use SQLParser\Node\NodeFactory;
use SQLParser\Node\Operator;

/**
 * This class has the ability to create instances implementing NodeInterface based on a descriptive array.
 *
 * @author David Négrier <d.negrier@thecodingmachine.com>
 */
class StatementFactory
{
    public static function toObject(array $desc)
    {
        if (isset($desc['SELECT'])) {
            $select = new Select();

            $columns = array_map(function ($item) {
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
                $from = self::mapArrayToNodeObjectList($desc['FROM']);
                $select->setFrom($from);
            }

            if (isset($desc['WHERE'])) {
                $where = self::mapArrayToNodeObjectList($desc['WHERE']);
                $where = NodeFactory::simplify($where);
                $select->setWhere($where);
            }

            if (isset($desc['GROUP'])) {
                $group = self::mapArrayToNodeObjectList($desc['GROUP']);
                $group = NodeFactory::simplify($group);
                $select->setGroup($group);
            }

            if (isset($desc['HAVING'])) {
                $having = self::mapArrayToNodeObjectList($desc['HAVING']);
                $having = NodeFactory::simplify($having);
                $select->setHaving($having);
            }

            if (isset($desc['ORDER'])) {
                $order = self::mapArrayToNodeObjectList($desc['ORDER']);
                $order = NodeFactory::simplify($order);
                $select->setOrder($order);
            }

            if (isset($desc['LIMIT'])) {
                $descLimit = self::checkLimitDesc($desc['LIMIT']);

                $limit = NodeFactory::toObject($descLimit['limit']);
                //$limit = NodeFactory::simplify($limit);
                $select->setLimit($limit);

                $offset = NodeFactory::toObject($descLimit['offset']);
                //$offset = NodeFactory::simplify($offset);
                $select->setOffset($offset);
            }

            return $select;
        } else {
            throw new \BadMethodCallException('Unknown query');
        }
    }

    /**
     * @param array $descLimit
     *
     * @return array
     *
     * @throws \Exception
     */
    private static function checkLimitDesc(array $descLimit)
    {
        if (count($descLimit) > 2) {
            throw new \Exception('The limit returned by the SQLParser contains more than 2 items, something might went wrong.');
        }

        return ['offset' => $descLimit[0], 'limit' => $descLimit[1]];
    }

    /**
     * @param array $items An array of objects represented as SQLParser arrays.
     */
    private static function mapArrayToNodeObjectList(array $items)
    {
        $list = [];

        $nextAndPartOfBetween = false;

        // Special case, let's replace the AND of a between with a ANDBETWEEN object.
        foreach ($items as $item) {
            $obj = NodeFactory::toObject($item);
            if ($obj instanceof Operator) {
                if ($obj->getValue() == 'BETWEEN') {
                    $nextAndPartOfBetween = true;
                } elseif ($nextAndPartOfBetween && $obj->getValue() == 'AND') {
                    $nextAndPartOfBetween = false;
                    $obj->setValue('AND_FROM_BETWEEN');
                }
            }
            $list[] = $obj;
        }

        return $list;
    }
}
