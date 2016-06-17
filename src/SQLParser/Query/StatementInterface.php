<?php

namespace SQLParser\Query;

use SQLParser\SqlRenderInterface;

/**
 * This base interface for anything that represents a SQL statement (SELECT, UNION, DROP, etc...).
 *
 * @author David Négrier <d.negrier@thecodingmachine.com>
 */
interface StatementInterface extends SqlRenderInterface
{
}
