<?php

namespace SQLParser\Query;

use Mouf\Database\MagicQueryException;
use SQLParser\Node\NodeFactory;
use SQLParser\Node\Operator;
use SQLParser\Node\Reserved;

/**
 * This class has the ability to create instances implementing NodeInterface based on a descriptive array.
 *
 * @author David Négrier <d.negrier@thecodingmachine.com>
 */
class StatementFactory
{
    /**
     * @return Select|Union
     * @throws MagicQueryException
     */
    public static function toObject(array $desc)
    {
        if (isset($desc['SELECT'])) {
            $select = new Select();

            $columns = array_map(function ($item) {
                return NodeFactory::toObject($item);
            }, $desc['SELECT']);
            $columns = NodeFactory::simplify($columns);

            $options = [];
            foreach ($columns as $key => $column) {
                if ($column instanceof Reserved) {
                    if (strtoupper($column->getBaseExpression()) === 'DISTINCT') {
                        $select->setDistinct(true);
                    } else {
                        $options[] = $column->getBaseExpression();
                    }
                    unset($columns[$key]);
                }
            }
            $select->setOptions($options);

            $select->setColumns($columns);

            if (isset($desc['FROM'])) {
                $from = NodeFactory::mapArrayToNodeObjectList($desc['FROM']);
                $select->setFrom($from);
            }

            if (isset($desc['WHERE'])) {
                $where = NodeFactory::mapArrayToNodeObjectList($desc['WHERE']);
                $where = NodeFactory::simplify($where);
                $select->setWhere($where);
            }

            if (isset($desc['GROUP'])) {
                $group = NodeFactory::mapArrayToNodeObjectList($desc['GROUP']);
                $group = NodeFactory::simplify($group);
                $select->setGroup($group);
            }

            if (isset($desc['HAVING'])) {
                $having = NodeFactory::mapArrayToNodeObjectList($desc['HAVING']);
                $having = NodeFactory::simplify($having);
                $select->setHaving($having);
            }

            if (isset($desc['ORDER'])) {
                $order = NodeFactory::mapArrayToNodeObjectList($desc['ORDER']);
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
        } elseif (isset($desc['UNION'])) {
            /** @var Select[] $selects */
            $selects = array_map([self::class, 'toObject'], $desc['UNION']);

            $union = new Union($selects);

            if (isset($desc['0']) && isset($desc['0']['ORDER'])) {
                $order = NodeFactory::mapArrayToNodeObjectList($desc['0']['ORDER']);
                $order = NodeFactory::simplify($order);
                $union->setOrder($order);
            }

            return $union;
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

}
