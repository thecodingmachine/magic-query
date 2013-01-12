<?php
namespace database\querywriter;

/**
 * The Select class represents a SQL Select statement
 * 
 * @author David Negrier
 * @Component
 */
class Select {
	
	/**
	 * The connection to the database.
	 * 
	 * @Property
	 * @Compulsory
	 * @var \DB_ConnectionInterface
	 */
	public $connection;
	
	/**
	 * If true, the DISTINCT keyword will be used with this select statement.
	 * 
	 * @Property
	 * @var bool
	 */
	public $distinct;
	
	/**
	 * This list of columns (or select expressions) that should be returned by the query.
	 * 
	 * @Property
	 * @Compulsory
	 * @var array<SelectExpressionInterface>
	 */
	public $columns = array();
	
	/**
	 * One or many tables or joins to add to the select statement.
	 * Advice: if you want to perform joins, create a JOIN first and put tables in the join.
	 * 
	 * @Property
	 * @Compulsory
	 * @var TableReferenceInterface
	 */
	public $from;
	
	/**
	 * The where condition.
	 * 
	 * @Property
	 * @var filters\FilterInterface
	 */
	public $where;
	
	/**
	 * The list of fields to group on.
	 * 
	 * @Property
	 * @var array<OrderByGroupByElementInterface>
	 */
	public $groupBy = array();
	
	/**
	 * The having condition.
	 * 
	 * @Property
	 * @var filters\FilterInterface
	 */
	public $having;
	
	/**
	 * The list of fields to order on.
	 * 
	 * @Property
	 * @var array<OrderByGroupByElementInterface>
	 */
	public $orderBy = array();
	
	/**
	 * The limit number of fields to return.
	 * 
	 * @Property
	 * @var int
	 */
	public $limit;
	
	/**
	 * Start returning firlds from field number $offset.
	 * 
	 * @Property
	 * @var int
	 */
	public $offset;
	
	/**
	 * Returns the SELECT statement in a string.
	 * 
	 * @return string
	 */
	public function toSql() {
		$sql = "SELECT ";
		if ($this->distinct) {
			$sql .= "DISTINCT ";
		}
		// Apply the toSql function on the columns 
		$connection = $this->connection;
				
		$columnsSql = array_map(function(SelectExpressionInterface $elem) use ($connection)  {
			return $elem->toSql($connection);
		}, $this->columns);
		$sql .= implode(", ", $columnsSql);
		
		$sql .= " FROM ";
		
		$sql .= $this->from->toSql($connection);
		
		if ($this->where) {
			$sql .= " WHERE ".$this->where->toSql($connection);
		}
		
		return $sql;
	}
}