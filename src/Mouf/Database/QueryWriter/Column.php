<?php
namespace Mouf\Database\QueryWriter;

use Mouf\Database\DBConnection\ConnectionInterface;

/**
 * The Column class represents one or many columns to be retrieved in a SQL Select statement
 * 
 * @author David Negrier
 * @Component
 */
class Column implements SelectExpressionInterface {
	
	/**
	 * Optional table name
	 * 
	 * @Important
	 * @var string
	 */
	public $tableName;

	/**
	 * The name of the column.
	 * Use "*" to retrieve all columns.
	 * 
	 * @Important
	 * @var string
	 */
	public $columnName;
	
	/**
	 * Optional alias name
	 * 
	 * @var string
	 */
	public $alias;
	
	/**
	 * Constructor
	 * 
	 * @param string $tableName
	 * @param string $columnName
	 * @param string $alias
	 */
	public function __construct($tableName = null, $columnName = null, $alias = null) {
		$this->tableName = $tableName;
		$this->columnName = $columnName;
		$this->alias = $alias;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Mouf\Database\QueryWriter.SqlRenderInterface::toSql()
	 */
	public function toSql(ConnectionInterface $dbConnection) {
		$sql = "";
		if ($this->tableName) {
			$sql .= $dbConnection->escapeDBItem($this->tableName).".";
		}
		if ($this->columnName != "*") {
			$sql .= $dbConnection->escapeDBItem($this->columnName);
		} else {
			$sql .= "*";
		}
		if ($this->alias) {
			$sql .= " AS ".$dbConnection->escapeDBItem($this->alias);
		}
		return $sql;
	}
}