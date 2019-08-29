<?php

namespace SQLParser;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * Objects implementing SqlRenderInterface can be rendered with the toSql method.
 *
 * @author David Negrier
 */
interface SqlRenderInterface
{
    // Apply the conditions
    const CONDITION_APPLY = 0;
    const CONDITION_IGNORE = 1;
    const CONDITION_GUESS = 2;

    /**
     * Renders the object as a SQL string.
     *
     * @param array $parameters
     * @param AbstractPlatform $platform
     * @param int $indent
     * @param int $conditionsMode
     * @param bool $extrapolateParameters Whether the parameters should be fed into the returned SQL query
     *
     * @return string|null
     */
    public function toSql(array $parameters, AbstractPlatform $platform, int $indent = 0, $conditionsMode = self::CONDITION_APPLY, bool $extrapolateParameters = true): ?string;
}
