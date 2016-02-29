<?php

namespace SQLParser\Node;

/**
 * This class represents a ELSE (in a CASE WHEN ... THEN ... ELSE ... END construct)
 *
 * @author David Négrier <d.negrier@thecodingmachine.com>
 */
class ElseOperation extends AbstractTwoOperandsOperator
{
    /**
     * Returns the symbol for this operator.
     *
     * @return string
     */
    protected function getOperatorSymbol()
    {
        return 'ELSE';
    }
}
