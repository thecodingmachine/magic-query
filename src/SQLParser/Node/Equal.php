<?php

namespace SQLParser\Node;

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
}
