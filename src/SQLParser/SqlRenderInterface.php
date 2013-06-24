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
	 * @return string
	 */
	public function toSql(ConnectionInterface $dbConnection = null);
}