<?php
namespace Mouf\Database\QueryWriter;

use Mouf\Database\DBConnection\ConnectionInterface;

/**
 * Represents a LEFT JOIN, JOIN, RIGHT JOIN or OUTER JOIN in a SELECT query.
 * 
 * @author David Negrier
 * @Component
 */
class Join implements TableReferenceInterface {
	/**
	 * The left table in the join
	 * 
	 * @Important
	 * @var TableReferenceInterface
	 */
	public $left;
	
	/**
	 * The right table in the join
	 * 
	 * @Important
	 * @var TableReferenceInterface
	 */
	public $right;
	
	/**
	 * The ON condition.
	 * 
	 * @Important
	 * @var Filters\FilterInterface
	 */
	public $on;
	
	/**
	 * The kind of Join to apply
	 * 
	 * @Important
	 * @OneOf ("LEFT JOIN", "JOIN", "RIGHT JOIN", "OUTER JOIN")
	 * @var string
	 */
	public $joinType = "LEFT JOIN";
	
	/**
	 * (non-PHPdoc)
	 * @see Mouf\Database\QueryWriter.SqlRenderInterface::toSql()
	 */
	public function toSql(ConnectionInterface $dbConnection) {
		$sql = "(".$this->left->toSql($dbConnection);
		$sql .= " ".$this->joinType." ";
		$sql .= $this->right->toSql($dbConnection);
		$sql .= " ON ";
		$sql .= $this->on->toSql($dbConnection);
		$sql .= ")";
		return $sql;
	}
}