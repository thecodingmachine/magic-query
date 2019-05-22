<?php

namespace SQLParser\Node;

use Doctrine\DBAL\Connection;

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
    protected function getOperatorSymbol()
    {
        return '<>';
    }

    protected function getSql(array $parameters = array(), Connection $dbConnection = null, $indent = 0, $conditionsMode = self::CONDITION_APPLY, bool $extrapolateParameters = true)
    {
        $rightOperand = $this->getRightOperand();
        if ($rightOperand instanceof Parameter && !isset($parameters[$rightOperand->getName()])) {
            $isNull = true;
        } else {
            $isNull = false;
        }

        $sql = NodeFactory::toSql($this->getLeftOperand(), $dbConnection, $parameters, ' ', false, $indent, $conditionsMode, $extrapolateParameters);
        if ($isNull) {
            $sql .= ' IS NOT null';
        } else {
            $sql .= ' '.$this->getOperatorSymbol().' ';
            $sql .= NodeFactory::toSql($rightOperand, $dbConnection, $parameters, ' ', false, $indent, $conditionsMode, $extrapolateParameters);
        }

        return $sql;
    }
}
