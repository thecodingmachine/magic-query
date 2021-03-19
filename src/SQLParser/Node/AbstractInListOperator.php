<?php

namespace SQLParser\Node;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Mouf\Database\MagicQueryException;

/**
 * This class represents an In operation in an SQL expression.
 *
 * @author David Négrier <d.negrier@thecodingmachine.com>
 */
abstract class AbstractInListOperator extends AbstractTwoOperandsOperator
{
    protected function getSql(array $parameters, AbstractPlatform $platform, int $indent = 0, int $conditionsMode = self::CONDITION_APPLY, bool $extrapolateParameters = true): string
    {
        $rightOperand = $this->getRightOperand();

        $rightOperand = $this->refactorParameterToExpression($rightOperand);

        $this->setRightOperand($rightOperand);

        $parameterNode = $this->getParameter($rightOperand);

        if ($parameterNode !== null) {
            if (!isset($parameters[$parameterNode->getName()])) {
                throw new MagicQueryException("Missing parameter '" . $parameterNode->getName() . "' for 'IN' operand.");
            }
            if ($parameters[$parameterNode->getName()] === []) {
                return "0 <> 0";
            }
        }

        return parent::getSql($parameters, $platform, $indent, $conditionsMode, $extrapolateParameters);
    }

    protected function refactorParameterToExpression(NodeInterface $rightOperand): NodeInterface
    {
        if ($rightOperand instanceof Parameter) {
            $expression = new Expression();
            $expression->setSubTree([$rightOperand]);
            $expression->setBrackets(true);
            return $expression;
        }
        return $rightOperand;
    }

    protected function getParameter(NodeInterface $operand): ?Parameter
    {
        if (!$operand instanceof Expression) {
            return null;
        }
        $subtree = $operand->getSubTree();
        if (!isset($subtree[0])) {
            return null;
        }
        if ($subtree[0] instanceof Parameter) {
            return $subtree[0];
        }
        return null;
    }
}
