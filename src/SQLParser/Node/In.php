<?php

namespace SQLParser\Node;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Mouf\Database\MagicQueryException;

/**
 * This class represents an In operation in an SQL expression.
 *
 * @author David Négrier <d.negrier@thecodingmachine.com>
 */
class In extends AbstractInListOperator
{
    /**
     * Returns the symbol for this operator.
     *
     * @return string
     */
    protected function getOperatorSymbol(): string
    {
        return 'IN';
    }
}
