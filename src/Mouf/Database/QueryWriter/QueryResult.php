<?php
namespace Mouf\Database\QueryWriter;

use Mouf\Utils\Value\ValueUtils;

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
	
	/**
	 * The list of parameters to apply to the SQL request.
	 * 
	 * @var array<string, string>|ArrayValueInterface
	 */
	private $parameters = array();
	
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
	 * The list of parameters to apply to the SQL request.
	 * 
	 * @param array<string, string>|ArrayValueInterface $parameters
	 */
	public function setParameters($parameters) {
		$this->parameters = $parameters;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see \Mouf\Utils\Value\ArrayValueInterface::val()
	 */
	public function val() {
		$parameters = ValueUtils::val($this->parameters);
		$pdoStatement = $this->connection->query($this->select->toSql($this->connection, $parameters), $this->offset, $this->limit);
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
