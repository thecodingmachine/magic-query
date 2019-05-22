<?php

namespace Mouf\Database\QueryWriter\Condition;

use Mouf\Utils\Value\ValueUtils;
use Mouf\Utils\Value\ValueInterface;
use Mouf\Utils\Common\ConditionInterface\ConditionInterface;

/**
 * This condition returns true if the SQL parameter is equal to the passed value.
 *
 * @author David Negrier
 */
class ParamEqualsCondition implements ConditionInterface
{
    private $parameterName;
    private $value;

    /**
     * @Important
     *
     * @param string                $parameterName The name of the parameter to check
     * @param string|ValueInterface $value
     */
    public function __construct($parameterName, $value)
    {
        $this->parameterName = $parameterName;
        $this->value = $value;
    }

    public function isOk($parameters = null)
    {
        return isset($parameters[$this->parameterName]) && $parameters[$this->parameterName] == ValueUtils::val($this->value);
    }
}
