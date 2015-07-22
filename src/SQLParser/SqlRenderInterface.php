<?php
namespace SQLParser;

use Doctrine\DBAL\Connection;

/**
 * Objects implementing SqlRenderInterface can be rendered with the toSql method.
 * 
 * @author David Negrier
 */
interface SqlRenderInterface {
	
	/**
	 * Renders the object as a SQL string
	 * 
	 * @param Connection $dbConnection
	 * @param array $parameters
	 * @param number $indent
	 * @param bool $ignoreConditions
	 * @return string
	 */
	public function toSql(array $parameters = array(), Connection $dbConnection = null, $indent = 0, $ignoreConditions = false);
}