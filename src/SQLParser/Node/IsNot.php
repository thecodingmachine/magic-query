<?php

namespace SQLParser\Node;

/**
 * This class represents a Is operation in an SQL expression.
 *
 * @author David Négrier <d.negrier@thecodingmachine.com>
 */
class IsNot extends AbstractTwoOperandsOperator
{
    /**
     * Returns the symbol for this operator.
     *
     * @return string
     */
    protected function getOperatorSymbol()
    {
        return 'IS NOT';
    }
}
