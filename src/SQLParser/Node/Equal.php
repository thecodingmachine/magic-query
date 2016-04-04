<?php

namespace SQLParser\Node;

use Doctrine\DBAL\Connection;

/**
 * This class represents an = operation in an SQL expression.
 *
 * @author David Négrier <d.negrier@thecodingmachine.com>
 */
class Equal extends AbstractTwoOperandsOperator
{
    /**
     * Returns the symbol for this operator.
     */
    protected function getOperatorSymbol()
    {
        return '=';
    }

    protected function getSql(array $parameters = array(), Connection $dbConnection = null, $indent = 0, $conditionsMode = self::CONDITION_APPLY)
    {
        $rightOperand = $this->getRightOperand();
        if ($rightOperand instanceof Parameter && !isset($parameters[$rightOperand->getName()])) {
            $isNull = true;
        } else {
            $isNull = false;
        }

        $sql = NodeFactory::toSql($this->getLeftOperand(), $dbConnection, $parameters, ' ', false, $indent, $conditionsMode);
        if ($isNull) {
            $sql .= ' IS null';
        } else {
            $sql .= ' '.$this->getOperatorSymbol().' ';
            $sql .= NodeFactory::toSql($rightOperand, $dbConnection, $parameters, ' ', false, $indent, $conditionsMode);
        }

        return $sql;
    }
}
