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

use Mouf\Database\MagicQueryException;
use Mouf\Database\MagicQueryParserException;
use SQLParser\SqlRenderInterface;
use Doctrine\DBAL\Connection;
use Mouf\MoufManager;
use SQLParser\Query\StatementFactory;
use SQLParser\ExpressionType;

/**
 * This class has the ability to create instances implementing NodeInterface based on a descriptive array.
 *
 * @author David Négrier <d.negrier@thecodingmachine.com>
 */
class NodeFactory
{
    public static function toObject(array $desc)
    {
        if (!isset($desc['expr_type'])) {
            throw new \Exception('Invalid array. Could not find expression type: '.var_export($desc, true));
        }

        switch ($desc['expr_type']) {
            case ExpressionType::LIMIT_CONST:
                if (substr($desc['base_expr'], 0, 1) == ':') {
                    $instance = new UnquotedParameter();
                    $instance->setName(substr($desc['base_expr'], 1));
                } else {
                    $instance = new LimitNode();
                    $expr = $desc['base_expr'];
                    if (strpos($expr, "'") === 0) {
                        $expr = substr($expr, 1);
                    }
                    if (strrpos($expr, "'") === strlen($expr) - 1) {
                        $expr = substr($expr, 0, strlen($expr) - 1);
                    }
                    $expr = stripslashes($expr);

                    $instance->setValue($expr);
                }
                // Debug:
                unset($desc['base_expr']);
                unset($desc['expr_type']);
                unset($desc['sub_tree']);
                if (!empty($desc)) {
                    throw new \InvalidArgumentException('Unexpected parameters in exception: '.var_export($desc, true));
                }

                return $instance;
            case ExpressionType::CONSTANT:
                $const = new ConstNode();
                $expr = $desc['base_expr'];
                if (strpos($expr, "'") === 0) {
                    $expr = substr($expr, 1);
                } else {
                    $const->setIsString(false);
                }
                if (strrpos($expr, "'") === strlen($expr) - 1) {
                    $expr = substr($expr, 0, -1);
                }
                $expr = stripslashes($expr);

                $const->setValue($expr);

                // If the constant has an alias, it is declared in the columns section.
                // If this is the case, let's wrap it in an "expression"
                if (isset($desc['alias'])) {
                    $expression = new Expression();
                    $expression->setBaseExpression($desc['base_expr']);
                    $expression->setSubTree($const);
                    $expression->setAlias($desc['alias']['name']);
                    $expression->setBrackets(false);

                    $const = $expression;

                    unset($desc['alias']);
                }

                // Debug:
                unset($desc['base_expr']);
                unset($desc['expr_type']);
                unset($desc['sub_tree']);

                if (!empty($desc)) {
                    throw new \InvalidArgumentException('Unexpected parameters in exception: '.var_export($desc, true));
                }

                return $const;

            case ExpressionType::OPERATOR:
                $operator = new Operator();
                $operator->setValue($desc['base_expr']);
                // Debug:
                unset($desc['base_expr']);
                unset($desc['expr_type']);
                if (!empty($desc['sub_tree'])) {
                    throw new \InvalidArgumentException('Unexpected operator with subtree: '.var_export($desc['sub_tree'], true));
                }
                unset($desc['sub_tree']);
                if (!empty($desc)) {
                    throw new \InvalidArgumentException('Unexpected parameters in exception: '.var_export($desc, true));
                }

                return $operator;

            case ExpressionType::COLREF:
                if (substr($desc['base_expr'], 0, 1) == ':') {
                    $instance = new Parameter();
                    $instance->setName(substr($desc['base_expr'], 1));
                } else {
                    $instance = new ColRef();
                    $lastDot = strrpos($desc['base_expr'], '.');
                    if ($lastDot === false) {
                        $instance->setColumn(str_replace('`', '', $desc['base_expr']));
                    } else {
                        $instance->setColumn(str_replace('`', '', substr($desc['base_expr'], $lastDot + 1)));
                        $instance->setTable(str_replace('`', '', substr($desc['base_expr'], 0, $lastDot)));
                    }
                    if (!empty($desc['alias'])) {
                        $instance->setAlias($desc['alias']['name']);
                    }

                    if (!empty($desc['direction'])) {
                        $instance->setDirection($desc['direction']);
                    }
                }

                // Debug:
                unset($desc['direction']);
                unset($desc['base_expr']);
                unset($desc['expr_type']);
                if (!empty($desc['sub_tree'])) {
                    throw new \InvalidArgumentException('Unexpected operator with subtree: '.var_export($desc['sub_tree'], true));
                }
                unset($desc['sub_tree']);
                unset($desc['alias']);
                if (!empty($desc)) {
                    throw new \InvalidArgumentException('Unexpected parameters in exception: '.var_export($desc, true));
                }

                return $instance;
            case ExpressionType::TABLE:
                $expr = new Table();
                $expr->setTable(str_replace('`', '', $desc['table']));
                switch ($desc['join_type']) {
                    case 'CROSS':
                        $joinType = 'CROSS JOIN';
                        break;
                    case 'JOIN':
                        $joinType = 'JOIN';
                        break;
                    case 'LEFT':
                        $joinType = 'LEFT JOIN';
                        break;
                    case 'RIGHT':
                        $joinType = 'RIGHT JOIN';
                        break;
                    case 'INNER':
                        $joinType = 'INNER JOIN';
                        break;
                    case 'OUTER':
                        $joinType = 'OUTER JOIN';
                        break;
                    case 'NATURAL':
                        $joinType = 'NATURAL JOIN';
                        break;
                    case ',':
                        $joinType = ',';
                        break;
                    default:
                        throw new \Exception("Unexpected join type: '".$desc['join_type']."'");
                }
                $expr->setJoinType($joinType);

                if (isset($desc['alias'])) {
                    $expr->setAlias($desc['alias']['name']);
                }
                $subTreeNodes = self::buildFromSubtree($desc['ref_clause']);
                if ($subTreeNodes) {
                    $expr->setRefClause(self::simplify($subTreeNodes));
                }

                // Debug:
                unset($desc['base_expr']);
                unset($desc['expr_type']);
                if (!empty($desc['sub_tree'])) {
                    throw new \InvalidArgumentException('Unexpected operator with subtree: '.var_export($desc['sub_tree'], true));
                }
                unset($desc['sub_tree']);
                unset($desc['join_type']);
                unset($desc['alias']);
                unset($desc['table']);
                unset($desc['ref_type']);
                unset($desc['ref_clause']);
                if (!empty($desc)) {
                    throw new \InvalidArgumentException('Unexpected parameters in exception: '.var_export($desc, true));
                }

                return $expr;
            case ExpressionType::SUBQUERY:
                $expr = new SubQuery();

                $expr->setSubQuery(self::buildFromSubtree($desc['sub_tree']));

                if (isset($desc['join_type'])) {
                    $expr->setJoinType($desc['join_type']);
                }

                if (isset($desc['alias'])) {
                    $expr->setAlias($desc['alias']['name']);
                }

                if (isset($desc['ref_clause'])) {
                    $subTreeNodes = self::buildFromSubtree($desc['ref_clause']);
                    if ($subTreeNodes) {
                        $expr->setRefClause(self::simplify($subTreeNodes));
                    }
                }

                // Debug:
                unset($desc['base_expr']);
                unset($desc['expr_type']);
                unset($desc['sub_tree']);
                unset($desc['join_type']);
                unset($desc['alias']);
                unset($desc['sub_tree']);
                unset($desc['ref_type']);
                unset($desc['ref_clause']);
                if (!empty($desc)) {
                    throw new \InvalidArgumentException('Unexpected parameters in exception: '.var_export($desc, true));
                }

                return $expr;
            case ExpressionType::AGGREGATE_FUNCTION:
                $expr = new AggregateFunction();
                $expr->setFunctionName($desc['base_expr']);

                $expr->setSubTree(self::buildFromSubtree($desc['sub_tree']));

                if (isset($desc['alias'])) {
                    $expr->setAlias($desc['alias']);
                }

                // Debug:
                unset($desc['base_expr']);
                unset($desc['expr_type']);
                unset($desc['sub_tree']);
                unset($desc['alias']);
                if (!empty($desc)) {
                    throw new \InvalidArgumentException('Unexpected parameters in exception: '.var_export($desc, true));
                }

                return $expr;
            case ExpressionType::SIMPLE_FUNCTION:
                $expr = new SimpleFunction();
                $expr->setBaseExpression($desc['base_expr']);

                if (isset($desc['sub_tree'])) {
                    $expr->setSubTree(self::buildFromSubtree($desc['sub_tree']));
                }

                if (isset($desc['alias'])) {
                    $expr->setAlias($desc['alias']['name']);
                }
                if (isset($desc['direction'])) {
                    $expr->setDirection($desc['direction']);
                }

                // Debug:
                unset($desc['base_expr']);
                unset($desc['expr_type']);
                unset($desc['sub_tree']);
                unset($desc['alias']);
                unset($desc['direction']);
                if (!empty($desc)) {
                    throw new \InvalidArgumentException('Unexpected parameters in simple function: '.var_export($desc, true));
                }

                return $expr;
            case ExpressionType::RESERVED:
                if (in_array(strtoupper($desc['base_expr']), ['CASE', 'WHEN', 'THEN', 'ELSE', 'END'])) {
                    $operator = new Operator();
                    $operator->setValue($desc['base_expr']);
                    // Debug:
                    unset($desc['base_expr']);
                    unset($desc['expr_type']);
                    if (!empty($desc['sub_tree'])) {
                        throw new \InvalidArgumentException('Unexpected operator with subtree: '.var_export($desc['sub_tree'], true));
                    }
                    unset($desc['sub_tree']);
                    if (!empty($desc)) {
                        throw new \InvalidArgumentException('Unexpected parameters in exception: '.var_export($desc, true));
                    }

                    return $operator;
                } else {
                    $res = new Reserved();
                    $res->setBaseExpression($desc['base_expr']);

                    if ($desc['expr_type'] == ExpressionType::BRACKET_EXPRESSION) {
                        $res->setBrackets(true);
                    }

                    // Debug:
                    unset($desc['base_expr']);
                    unset($desc['expr_type']);
                    unset($desc['sub_tree']);
                    unset($desc['alias']);
                    unset($desc['direction']);
                    if (!empty($desc)) {
                        throw new \InvalidArgumentException('Unexpected parameters in exception: '.var_export($desc, true));
                    }

                    return $res;
                }
            case ExpressionType::USER_VARIABLE:
            case ExpressionType::SESSION_VARIABLE:
            case ExpressionType::GLOBAL_VARIABLE:
            case ExpressionType::LOCAL_VARIABLE:
            case ExpressionType::EXPRESSION:
            case ExpressionType::BRACKET_EXPRESSION:
            case ExpressionType::TABLE_EXPRESSION:

            case ExpressionType::IN_LIST:

            case ExpressionType::SIGN:
            case ExpressionType::RECORD:

            case ExpressionType::MATCH_ARGUMENTS:
            case ExpressionType::MATCH_MODE:

            case ExpressionType::ALIAS:
            case ExpressionType::POSITION:

            case ExpressionType::TEMPORARY_TABLE:
            case ExpressionType::VIEW:
            case ExpressionType::DATABASE:
            case ExpressionType::SCHEMA:
                $expr = new Expression();
                $expr->setBaseExpression($desc['base_expr']);

                if (isset($desc['sub_tree'])) {
                    $expr->setSubTree(self::buildFromSubtree($desc['sub_tree']));
                }

                if (isset($desc['alias'])) {
                    $expr->setAlias($desc['alias']['name']);
                }
                if (isset($desc['direction'])) {
                    $expr->setDirection($desc['direction']);
                }

                if ($desc['expr_type'] == ExpressionType::BRACKET_EXPRESSION) {
                    $expr->setBrackets(true);
                }

                // Debug:
                unset($desc['base_expr']);
                unset($desc['expr_type']);
                unset($desc['sub_tree']);
                unset($desc['alias']);
                unset($desc['direction']);
                if (!empty($desc)) {
                    throw new \InvalidArgumentException('Unexpected parameters in exception: '.var_export($desc, true));
                }

                return $expr;
            default:
                throw new \Exception('Unknown expression type');
        }
    }

    private static function buildFromSubtree($subTree)
    {
        if ($subTree && is_array($subTree)) {
            //if (isset($subTree['SELECT'])) {
            // If the subtree is a map instead of a list, we are likely to be on a SUBSELECT statement.
            if (!empty($subTree) && !isset($subTree[0])) {
                $subTree = StatementFactory::toObject($subTree);
            } else {
                $subTree = array_map(function ($item) {
                    if (is_array($item)) {
                        return self::toObject($item);
                    } else {
                        return $item;
                    }
                }, $subTree);
            }
        }

        return $subTree;
    }

    private static $PRECEDENCE = array(
            array('INTERVAL'),
            array('BINARY', 'COLLATE'),
            array('!'),
            array(/*'-'*/ /* (unary minus) ,*/ '~' /*(unary bit inversion)*/),
            array('^'),
            array('*', '/', 'DIV', '%', 'MOD'),
            array('-', '+'),
            array('<<', '>>'),
            array('&'),
            array('|'),
            array('=' /*(comparison)*/, '<=>', '>=', '>', '<=', '<', '<>', '!=', 'IS', 'LIKE', 'REGEXP', 'IN', 'IS NOT', 'NOT IN'),
            array('AND_FROM_BETWEEN'),
            array('THEN'),
            array('WHEN'),
            array('ELSE'),
            array('BETWEEN', 'CASE', 'END'),
            array('NOT'),
            array('&&', 'AND'),
            array('XOR'),
            array('||', 'OR'), );

    private static $OPERATOR_TO_CLASS = array(
            '=' => 'SQLParser\Node\Equal',
            '<' => 'SQLParser\Node\Less',
            '>' => 'SQLParser\Node\Greater',
            '<=' => 'SQLParser\Node\LessOrEqual',
            '=>' => 'SQLParser\Node\GreaterOrEqual',
            //'<=>' => '????',
            '<>' => 'SQLParser\Node\Different',
            '!=' => 'SQLParser\Node\Different',
            'IS' => 'SQLParser\Node\Is',
            'IS NOT' => 'SQLParser\Node\IsNot',
            'LIKE' => 'SQLParser\Node\Like',
            'REGEXP' => 'SQLParser\Node\Regexp',
            'IN' => 'SQLParser\Node\In',
            'NOT IN' => 'SQLParser\Node\NotIn',
            '+' => 'SQLParser\Node\Plus',
            '-' => 'SQLParser\Node\Minus',
            '*' => 'SQLParser\Node\Multiply',
            '/' => 'SQLParser\Node\Divide',
            '%' => 'SQLParser\Node\Modulo',
            'MOD' => 'SQLParser\Node\Modulo',
            'DIV' => 'SQLParser\Node\Div',
            '&' => 'SQLParser\Node\BitwiseAnd',
            '|' => 'SQLParser\Node\BitwiseOr',
            '^' => 'SQLParser\Node\BitwiseXor',
            '<<' => 'SQLParser\Node\ShiftLeft',
            '>>' => 'SQLParser\Node\ShiftRight',
            '<=>' => 'SQLParser\Node\NullCompatibleEqual',
            'AND' => 'SQLParser\Node\AndOp',
            '&&' => 'SQLParser\Node\AndOp',
            '||' => 'SQLParser\Node\OrOp',
            'OR' => 'SQLParser\Node\OrOp',
            'XOR' => 'SQLParser\Node\XorOp',
            'THEN' => 'SQLParser\Node\Then',
            'ELSE' => 'SQLParser\Node\ElseOperation',
    );

    /**
     * Takes an array of nodes (including operators) and try to build a tree from it.
     *
     * @param NodeInterface[]|NodeInterface $nodes
     */
    public static function simplify($nodes)
    {
        if (empty($nodes)) {
            $nodes = array();
        } elseif (!is_array($nodes)) {
            $nodes = array($nodes);
        }
        $minPriority = -1;
        $selectedOperators = array();
        $lastSelectedOperator = '';
        $differentOperatorWithSamePriority = false;

        // Let's transform "NOT" + "IN" into "NOT IN"
        $newNodes = array();
        for ($i = 0; $i < count($nodes); ++$i) {
            $node = $nodes[$i];
            if ($node instanceof Operator && isset($nodes[$i + 1]) && $nodes[$i + 1] instanceof Operator
                    && strtoupper($node->getValue()) == 'IS' && strtoupper($nodes[$i + 1]->getValue()) == 'NOT') {
                $notIn = new Operator();
                $notIn->setValue('IS NOT');
                $newNodes[] = $notIn;
                ++$i;
            } elseif ($node instanceof Operator && isset($nodes[$i + 1]) && $nodes[$i + 1] instanceof Operator
                    && strtoupper($node->getValue()) == 'NOT' && strtoupper($nodes[$i + 1]->getValue()) == 'IN') {
                $notIn = new Operator();
                $notIn->setValue('NOT IN');
                $newNodes[] = $notIn;
                ++$i;
            } else {
                $newNodes[] = $node;
            }
        }
        $nodes = $newNodes;

        // Let's find the highest level operator.
        for ($i = count($nodes) - 1; $i >= 0; --$i) {
            $node = $nodes[$i];
            if ($node instanceof Operator) {
                $priority = self::getOperatorPrecedence($node);

                if ($priority == $minPriority && $lastSelectedOperator != strtoupper($node->getValue())) {
                    $differentOperatorWithSamePriority = true;
                } elseif ($priority > $minPriority) {
                    $minPriority = $priority;
                    $selectedOperators = array($node);
                    $lastSelectedOperator = strtoupper($node->getValue());
                } else {
                    if (strtoupper($node->getValue()) == $lastSelectedOperator && !$differentOperatorWithSamePriority) {
                        $selectedOperators[] = $node;
                    }
                }
            }
        }
        $selectedOperators = array_reverse($selectedOperators);

        // At this point, the $selectedOperator list contains a list of operators of the same kind that will apply
        // at the same time.
        if (empty($selectedOperators)) {
            // If we have an Expression with no base expression, let's simply discard it.
            // Indeed, the tree will add brackets by itself, and no Expression is needed for that.
            $newNodes = array();
            /*foreach ($nodes as $key=>$operand) {
                if ($operand instanceof Expression) {
                    $subTree = $operand->getSubTree();
                    if (count($subTree) == 1) {
                        $nodes[$key] = self::simplify($subTree);
                    }
                }
            }*/
            foreach ($nodes as $operand) {
                if ($operand instanceof Expression) {
                    if (empty($operand->getBaseExpression())) {
                        $subTree = $operand->getSubTree();
                        if (count($subTree) == 1) {
                            $newNodes = array_merge($newNodes, self::simplify($subTree));
                        } else {
                            $newNodes[] = $operand;
                        }
                    } else {
                        $newNodes[] = $operand;
                    }
                } else {
                    $newNodes[] = $operand;
                }
            }

            return $newNodes;
        }

        // Let's grab the operands of the operator.
        $operands = array();
        $operand = array();
        $tmpOperators = $selectedOperators;
        $nextOperator = array_shift($tmpOperators);

        $isSelectedOperatorFirst = null;

        foreach ($nodes as $node) {
            if ($node === $nextOperator) {
                if ($isSelectedOperatorFirst === null) {
                    $isSelectedOperatorFirst = true;
                }
                // Let's apply the "simplify" method on the operand before storing it.
                //$operands[] = self::simplify($operand);
                $simple = self::simplify($operand);
                if (is_array($simple)) {
                    $operands = array_merge($operands, $simple);
                } else {
                    $operands[] = $simple;
                }

                $operand = array();
                $nextOperator = array_shift($tmpOperators);
            } else {
                if ($isSelectedOperatorFirst === null) {
                    $isSelectedOperatorFirst = false;
                }
                $operand[] = $node;
            }
        }
        //$operands[] = self::simplify($operand);
        //$operands = array_merge($operands, self::simplify($operand));
        $simple = self::simplify($operand);
        if (is_array($simple)) {
            $operands = array_merge($operands, $simple);
        } else {
            $operands[] = $simple;
        }

        // Now, if we have an Expression, let's simply discard it.
        // Indeed, the tree will add brackets by itself, and no Expression in needed for that.
        /*foreach ($operands as $key=>$operand) {
            if ($operand instanceof Expression) {
                $subTree = $operand->getSubTree();
                if (count($subTree) == 1) {
                    $operands[$key] = self::simplify($subTree);
                }
            }
        }*/

        $operation = strtoupper($selectedOperators[0]->getValue());

        /* TODO:
        Remaining operators to code:
        array('INTERVAL'),
        array('BINARY', 'COLLATE'),
        array('!'),
        array('NOT'),
        */

        if (isset(self::$OPERATOR_TO_CLASS[$operation]) && is_subclass_of(self::$OPERATOR_TO_CLASS[$operation], 'SQLParser\Node\AbstractTwoOperandsOperator')) {
            if (count($operands) != 2) {
                throw new MagicQueryException('An error occured while parsing SQL statement. Invalid character found next to "'.$operation.'"');
            }

            $leftOperand = array_shift($operands);
            $rightOperand = array_shift($operands);

            $instance = new self::$OPERATOR_TO_CLASS[$operation]();
            $instance->setLeftOperand($leftOperand);
            $instance->setRightOperand($rightOperand);

            return $instance;
        } elseif (isset(self::$OPERATOR_TO_CLASS[$operation]) && is_subclass_of(self::$OPERATOR_TO_CLASS[$operation], 'SQLParser\Node\AbstractManyInstancesOperator')) {
            $instance = new self::$OPERATOR_TO_CLASS[$operation]();
            $instance->setOperands($operands);

            return $instance;
        } elseif ($operation === 'BETWEEN') {
            $leftOperand = array_shift($operands);
            $rightOperand = array_shift($operands);
            if (!$rightOperand instanceof Operation || $rightOperand->getOperatorSymbol() !== 'AND_FROM_BETWEEN') {
                throw new MagicQueryException('Missing AND in BETWEEN filter.');
            }

            $innerOperands = $rightOperand->getOperands();
            $minOperand = array_shift($innerOperands);
            $maxOperand = array_shift($innerOperands);

            $instance = new Between();
            $instance->setLeftOperand($leftOperand);
            $instance->setMinValueOperand($minOperand);
            $instance->setMaxValueOperand($maxOperand);

            return $instance;
        } elseif ($operation === 'WHEN') {
            $instance = new WhenConditions();

            if (!$isSelectedOperatorFirst) {
                $value = array_shift($operands);
                $instance->setValue($value);
            }
            $instance->setOperands($operands);

            return $instance;
        } elseif ($operation === 'CASE') {
            $innerOperation = array_shift($operands);

            if (!empty($operands)) {
                throw new MagicQueryException('A CASE statement should contain only a ThenConditions or a ElseOperand object.');
            }

            $instance = new CaseOperation();
            $instance->setOperation($innerOperation);

            return $instance;
        } elseif ($operation === 'END') {
            // Simply bypass the END operation. We already have a CASE matching node:
            $caseOperation = array_shift($operands);

            return $caseOperation;
        } else {
            $instance = new Operation();
            $instance->setOperatorSymbol($operation);
            $instance->setOperands($operands);

            return $instance;
        }
    }

    /**
     * Finds the precedence for operator $node (highest number has the least precedence).
     *
     * @param Operator $node
     *
     * @throws \Exception
     *
     * @return unknown
     */
    private static function getOperatorPrecedence(Operator $node)
    {
        $value = strtoupper($node->getValue());

        foreach (self::$PRECEDENCE as $priority => $arr) {
            foreach ($arr as $op) {
                if ($value == $op) {
                    return $priority;
                }
            }
        }
        throw new \Exception('Unknown operator precedence for operator '.$value);
    }

    /**
     * @param mixed       $node        a node of a recursive array of node
     * @param MoufManager $moufManager
     *
     * @return MoufInstanceDescriptor
     */
    public static function nodeToInstanceDescriptor($node, MoufManager $moufManager)
    {
        $instanceDescriptor = $moufManager->createInstance(get_called_class());

        return self::array_map_deep($node, function ($item) use ($moufManager) {
            if ($item instanceof NodeInterface) {
                return $item->toInstanceDescriptor($moufManager);
            } else {
                return $item;
            }
        });
    }

    private static function array_map_deep($array, $callback)
    {
        $new = array();
        if (is_array($array)) {
            foreach ($array as $key => $val) {
                if (is_array($val)) {
                    $new[$key] = self::array_map_deep($val, $callback);
                } else {
                    $new[$key] = call_user_func($callback, $val);
                }
            }
        } else {
            $new = call_user_func($callback, $array);
        }

        return $new;
    }

    /**
     * Tansforms the array of nodes (or the node) passed in parameter into a SQL string.
     *
     * @param mixed       $nodes          Recursive array of node interface
     * @param Connection  $dbConnection
     * @param array       $parameters
     * @param string      $delimiter
     * @param bool|string $wrapInBrackets
     * @param int|number  $indent
     * @param int         $conditionsMode
     *
     * @return null|string
     */
    public static function toSql($nodes, Connection $dbConnection = null, array $parameters = array(), $delimiter = ',', $wrapInBrackets = true, $indent = 0, $conditionsMode = SqlRenderInterface::CONDITION_APPLY)
    {
        if (is_array($nodes)) {
            $elems = array();
            array_walk_recursive($nodes, function ($item) use (&$elems, $dbConnection, $indent, $delimiter, $parameters, $conditionsMode) {
                if ($item instanceof SqlRenderInterface) {
                    $itemSql = $item->toSql($parameters, $dbConnection, $indent, $conditionsMode);
                    if ($itemSql !== null) {
                        $elems[] = str_repeat(' ', $indent).$itemSql;
                    }
                } else {
                    if ($item !== null) {
                        $elems[] = str_repeat(' ', $indent).$item;
                    }
                }
            });
            $sql = implode($delimiter, $elems);
        } else {
            $item = $nodes;
            if ($item instanceof SqlRenderInterface) {
                $itemSql = $item->toSql($parameters, $dbConnection, $indent, $conditionsMode);
                if ($itemSql === null || $itemSql === '') {
                    return;
                }
                $sql = str_repeat(' ', $indent).$itemSql;
            } else {
                if ($item === null || $item === '') {
                    return;
                }
                $sql = str_repeat(' ', $indent).$item;
            }
        }
        if ($wrapInBrackets) {
            $sql = '('.$sql.')';
        }

        return $sql;
    }

    /**
     * Escapes a DB item (should not be used. Only used if no DBConnection is passed).
     *
     * @return string
     *
     * @param string $str
     */
    public static function escapeDBItem($str, Connection $dbConnection = null)
    {
        if ($dbConnection) {
            return $dbConnection->quoteIdentifier($str);
        } else {
            return '`'.$str.'`';
        }
    }
}
