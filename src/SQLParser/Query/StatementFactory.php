<?php

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
                $descLimit = $desc['LIMIT'];

                //$limit = NodeFactory::toObject($descLimit['limit']);
                //$limit = NodeFactory::simplify($limit);
                if (isset($descLimit['rowcount'])) {
                    $select->setLimit(NodeFactory::toLimitNode($descLimit['rowcount']));
                }

                if (isset($descLimit['offset'])) {
                    $select->setOffset(NodeFactory::toLimitNode($descLimit['offset']));
                }


                //$offset = NodeFactory::toObject($descLimit['offset']);
                //$offset = NodeFactory::simplify($offset);
                //$select->setOffset($offset);
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
    /*private static function checkLimitDesc(array $descLimit)
    {
        if (count($descLimit) > 2) {
            throw new \Exception('The limit returned by the SQLParser contains more than 2 items, something might went wrong.');
        }

        return ['offset' => $descLimit['offset'], 'limit' => $descLimit['rowcount']];
    }*/

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
