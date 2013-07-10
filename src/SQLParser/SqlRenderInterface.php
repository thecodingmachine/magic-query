<?php
namespace SQLParser;

use Mouf\Database\DBConnection\ConnectionInterface;

/**
 * Objects implementing SqlRenderInterface can be rendered with the toSql method.
 * 
 * @author David Negrier
 */
interface SqlRenderInterface {
	
	/**
	 * Renders the object as a SQL string
	 * 
	 * @param ConnectionInterface $dbConnection
	 * @param array $parameters
	 * @param number $indent
	 * @param bool $ignoreConditions
	 * @return string
	 */
	public function toSql(array $parameters = array(), ConnectionInterface $dbConnection = null, $indent = 0, $ignoreConditions = false);
}