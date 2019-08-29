<?php

namespace SQLParser\Node;

use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * This class represents a set of ... WHEN ... THEN ... construct (inside a CASE).
 *
 * @author David Négrier <d.negrier@thecodingmachine.com>
 */
class WhenConditions extends AbstractManyInstancesOperator
{
    private $value;

    public function getValue()
    {
        return $this->value;
    }

    /**
     * Sets the value.
     *
     * @Important
     *
     * @param NodeInterface|NodeInterface[]|string $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * Renders the object as a SQL string.
     *
     * @param array $parameters
     * @param AbstractPlatform $platform
     * @param int $indent
     * @param int $conditionsMode
     *
     * @param bool $extrapolateParameters
     * @return string
     */
    public function toSql(array $parameters, AbstractPlatform $platform, int $indent = 0, $conditionsMode = self::CONDITION_APPLY, bool $extrapolateParameters = true): ?string
    {
        $fullSql = '';

        if ($this->value) {
            $fullSql = NodeFactory::toSql($this->value, $platform, $parameters, ' ', false, $indent, $conditionsMode, $extrapolateParameters);
        }

        foreach ($this->getOperands() as $operand) {
            $sql = NodeFactory::toSql($operand, $platform, $parameters, ' ', false, $indent, $conditionsMode, $extrapolateParameters);
            if ($sql != null) {
                $fullSql .= "\n".str_repeat(' ', $indent).'WHEN '.$sql;
            }
        }

        return $fullSql;
    }

    /**
     * Returns the symbol for this operator.
     *
     * @return string
     */
    protected function getOperatorSymbol()
    {
        return 'WHEN';
    }
}
