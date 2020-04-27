<?php

namespace SQLParser\Node;

/**
 * This class represents an NOT LIKE operation in an SQL expression.
 *
 * @author David Négrier <d.negrier@thecodingmachine.com>
 */
class NotLike extends AbstractTwoOperandsOperator
{
    /**
     * Returns the symbol for this operator.
     *
     * @return string
     */
    protected function getOperatorSymbol()
    {
        return 'NOT LIKE';
    }
}
