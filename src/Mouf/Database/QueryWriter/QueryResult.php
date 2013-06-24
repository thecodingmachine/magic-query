<?php
namespace Mouf\Database\QueryWriter;

use SQLParser\Query\Select;

use Mouf\Utils\Common\PaginableInterface;

use Mouf\Utils\Value\ArrayValueInterface;

use Mouf\Database\DBConnection\ConnectionInterface;

/**
 * A class that can execute a query and expose the results.
 * 
 * @author David Negrier
 */
class QueryResult implements ArrayValueInterface, PaginableInterface {

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
	
	private $limit;
	private $offset;

	/**
	 * 
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
		// FIXME: add support for params!
		$pdoStatement = $this->connection->query($this->select->toSql($this->connection), $this->offset, $this->limit);
		return new ResultSet($pdoStatement);
	}
	
	/**
	 * Paginates the result set.
	 *
	 * @param int $limit
	 * @param int $offset
	 */
	public function paginate($limit, $offset = 0) {
		$this->limit = $limit;
		$this->offset = $offset;
	}

}
