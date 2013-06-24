<?php
namespace Mouf\Database\QueryWriter;

use SQLParser\Query\Select;

use Mouf\Utils\Value\IntValueInterface;

use Mouf\Database\DBConnection\ConnectionInterface;

/**
 * A utility class that can compute the number of results returned by a query.
 * It does so by embedding the query into a SELECT count(*) query and computing the results.
 * 
 * @author David Negrier
 */
class CountNbResult implements IntValueInterface {

	/**
	 * The Select statement.
	 *
	 * @var Select
	 */
	private $select;

	/**
	 * The connection to the database.
	 *
	 * @var ConnectionInterface
	 */
	private $connection;

	/**
	 * @Important $select
	 * @param Select $select
	 * @param ConnectionInterface $connection
	 */
	public function __construct(Select $select, ConnectionInterface $connection) {
		$this->select = $select;
		$this->connection = $connection;
	}

	/**
	 * (non-PHPdoc)
	 * @see \Mouf\Utils\Value\ArrayValueInterface::val()
	 */
	public function val() {
		$sql = "SELECT count(*) as cnt FROM (".$this->select->toSql().") tmp";
		// FIXME: add support for params!
		return $this->connection->getOne($sql);
	}

}
