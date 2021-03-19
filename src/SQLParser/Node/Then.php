<?php

namespace SQLParser\Node;

/**
 * This class represents a THEN (in a CASE WHEN ... THEN ... END construct).
 *
 * @author David Négrier <d.negrier@thecodingmachine.com>
 */
class Then extends AbstractTwoOperandsOperator
{
    /**
     * Returns the symbol for this operator.
     *
     * @return string
     */
    protected function getOperatorSymbol(): string
    {
        return 'THEN';
    }
}
