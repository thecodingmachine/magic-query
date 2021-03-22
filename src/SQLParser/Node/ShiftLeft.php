<?php

namespace SQLParser\Node;

/**
 * This class represents an << operation in an SQL expression.
 *
 * @author David Négrier <d.negrier@thecodingmachine.com>
 */
class ShiftLeft extends AbstractTwoOperandsOperator
{
    /**
     * Returns the symbol for this operator.
     *
     * @return string
     */
    protected function getOperatorSymbol(): string
    {
        return '<<';
    }
}
