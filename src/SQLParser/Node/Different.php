<?php

namespace SQLParser\Node;

use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * This class represents a Is operation in an SQL expression.
 *
 * @author David Négrier <d.negrier@thecodingmachine.com>
 */
class Different extends AbstractTwoOperandsOperator
{
    /**
     * Returns the symbol for this operator.
     *
     * @return string
     */
    protected function getOperatorSymbol(): string
    {
        return '<>';
    }

    protected function getSql(array $parameters, AbstractPlatform $platform, int $indent = 0, int $conditionsMode = self::CONDITION_APPLY, bool $extrapolateParameters = true): string
    {
        $rightOperand = $this->getRightOperand();
        if ($rightOperand instanceof Parameter && !isset($parameters[$rightOperand->getName()])) {
            $isNull = true;
        } else {
            $isNull = false;
        }

        $sql = NodeFactory::toSql($this->getLeftOperand(), $platform, $parameters, ' ', false, $indent, $conditionsMode, $extrapolateParameters);
        if ($isNull) {
            $sql .= ' IS NOT null';
        } else {
            $sql .= ' '.$this->getOperatorSymbol().' ';
            $sql .= NodeFactory::toSql($rightOperand, $platform, $parameters, ' ', false, $indent, $conditionsMode, $extrapolateParameters);
        }

        return $sql;
    }
}
