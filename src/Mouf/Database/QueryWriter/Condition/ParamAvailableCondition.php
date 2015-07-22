<?php

namespace Mouf\Database\QueryWriter\Condition;

use Mouf\Utils\Common\ConditionInterface\ConditionInterface;

/**
 * This condition returns true if the SQL parameter has been passed and is not empty.
 *
 * @author David Negrier
 */
class ParamAvailableCondition implements ConditionInterface
{
    private $parameterName;

    /**
     * @Important
     *
     * @param string $parameterName The name of the parameter to check
     */
    public function __construct($parameterName)
    {
        $this->parameterName = $parameterName;
    }

    /**
     * @param string $caller
     */
    public function isOk($parameters = null)
    {
        return isset($parameters[$this->parameterName]) && !empty($parameters[$this->parameterName]);
    }
}
