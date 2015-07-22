<?php

namespace SQLParser\Node;

/**
 * This class represents a DIV operation (division with integer result) in an SQL expression.
 *
 * @author David Négrier <d.negrier@thecodingmachine.com>
 */
class Div extends AbstractTwoOperandsOperator
{
    /**
     * Returns the symbol for this operator.
     *
     * @return string
     */
    protected function getOperatorSymbol()
    {
        return 'DIV';
    }
}
