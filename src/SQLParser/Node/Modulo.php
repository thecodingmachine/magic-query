<?php

namespace SQLParser\Node;

/**
 * This class represents an % or MOD operation in an SQL expression.
 *
 * @author David Négrier <d.negrier@thecodingmachine.com>
 */
class Modulo extends AbstractTwoOperandsOperator
{
    /**
     * Returns the symbol for this operator.
     *
     * @return string
     */
    protected function getOperatorSymbol()
    {
        return '%';
    }
}
