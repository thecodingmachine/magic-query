<?php
namespace database\querywriter;

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
	 * @Property
	 * @Compulsory
	 * @var TableReferenceInterface
	 */
	public $left;
	
	/**
	 * The right table in the join
	 * 
	 * @Property
	 * @Compulsory
	 * @var TableReferenceInterface
	 */
	public $right;
	
	/**
	 * The ON condition.
	 * 
	 * @Property
	 * @Compulsory
	 * @var filters\FilterInterface
	 */
	public $on;
	
	/**
	 * The kind of Join to apply
	 * 
	 * @Property
	 * @Compulsory
	 * @OneOf ("LEFT JOIN", "JOIN", "RIGHT JOIN", "OUTER JOIN")
	 * @var string
	 */
	public $joinType = "LEFT JOIN";
	
	/**
	 * (non-PHPdoc)
	 * @see database\querywriter.SqlRenderInterface::toSql()
	 */
	public function toSql(\DB_ConnectionInterface $dbConnection) {
		$sql = "(".$this->left->toSql($dbConnection);
		$sql .= " ".$this->joinType." ";
		$sql .= $this->right->toSql($dbConnection);
		$sql .= " ON ";
		$sql .= $this->on->toSql($dbConnection);
		$sql .= ")";
		return $sql;
	}
}