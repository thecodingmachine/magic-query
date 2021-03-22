<?php

namespace SQLParser\Node;

/**
 * This class represents an NOT IN operation in an SQL expression.
 *
 * @author David Négrier <d.negrier@thecodingmachine.com>
 */
class NotIn extends AbstractInListOperator
{
    /**
     * Returns the symbol for this operator.
     *
     * @return string
     */
    protected function getOperatorSymbol(): string
    {
        return 'NOT IN';
    }
}
