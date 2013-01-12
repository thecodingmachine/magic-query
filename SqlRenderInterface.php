<?php
namespace database\querywriter;

/**
 * Objects implementing SqlRenderInterface can be rendered with the toSql method.
 * 
 * @author David Negrier
 */
interface SqlRenderInterface {
	
	/**
	 * Renders the object as a SQL string
	 * 
	 * @param \DB_ConnectionInterface $dbConnection
	 * @return string
	 */
	public function toSql(\DB_ConnectionInterface $dbConnection);
}