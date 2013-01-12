<?php
namespace database\querywriter;

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
	 * @Property
	 * @var string
	 */
	public $tableName;

	/**
	 * The name of the column.
	 * Use "*" to retrieve all columns.
	 * 
	 * @Property
	 * @Compulsory
	 * @var string
	 */
	public $columnName;
	
	/**
	 * Optional alias name
	 * 
	 * @Property
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
	 * @see database\querywriter.SqlRenderInterface::toSql()
	 */
	public function toSql(\DB_ConnectionInterface $dbConnection) {
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