<?php
namespace Mouf\Database\QueryWriter;

use Mouf\Database\DBConnection\ConnectionInterface;

/**
 * Represents a SQL table in a SELECT query.
 * 
 * @author David Negrier
 * @Component
 */
class TableReference implements TableReferenceInterface {
	
	/**
	 * The table name.
	 * 
	 * @Property
	 * @Compulsory
	 * @Important
	 * @var string
	 */
	public $tableName;
	
	/**
	 * The alias.
	 * 
	 * @Property
	 * @Important
	 * @var string
	 */
	public $alias;
	
	/**
	 * Renders the object as a SQL string
	 * @return string
	 */
	public function toSql(ConnectionInterface $dbConnection) {
		$sql = $dbConnection->escapeDBItem($this->tableName);
		if ($this->alias) {
			$sql .= " AS ".$dbConnection->escapeDBItem($this->alias);
		}
		return $sql;
	}
}