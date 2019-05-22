<?php

namespace SQLParser\Node;
use Doctrine\DBAL\Connection;
use Mouf\Database\MagicQueryException;

/**
 * This class represents an In operation in an SQL expression.
 *
 * @author David Négrier <d.negrier@thecodingmachine.com>
 */
class In extends AbstractTwoOperandsOperator
{
    /**
     * Returns the symbol for this operator.
     *
     * @return string
     */
    protected function getOperatorSymbol()
    {
        return 'IN';
    }

    protected function getSql(array $parameters = array(), Connection $dbConnection = null, $indent = 0, $conditionsMode = self::CONDITION_APPLY, bool $extrapolateParameters = true)
    {
        $rightOperand = $this->getRightOperand();
        if ($rightOperand instanceof Parameter) {
            if (!isset($parameters[$rightOperand->getName()])) {
                throw new MagicQueryException("Missing parameter '" . $rightOperand->getName() . "' for 'IN' operand.");
            }
            if ($parameters[$rightOperand->getName()] === []) {
                return "FALSE";
            }
        }

        return parent::getSql($parameters, $dbConnection, $indent, $conditionsMode, $extrapolateParameters);
    }
}
