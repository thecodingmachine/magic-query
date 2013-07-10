<?php
namespace Mouf\Database\QueryWriter;

use Mouf\Utils\Value\ValueUtils;

use SQLParser\Query\Select;

use Mouf\Utils\Common\PaginableInterface;

use Mouf\Utils\Value\ArrayValueInterface;
use Mouf\Utils\Value\ValueInterface;

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
	 * @var array<string, string>|array<string, ValueInterface>|ArrayValueInterface
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
	 * @param array<string, string>|array<string, ValueInterface>|ArrayValueInterface $parameters
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
		$pdoStatement = $this->connection->query($this->select->toSql($parameters, $this->connection), $this->offset, $this->limit);
		return new ResultSet($pdoStatement);
	}
	
	/**
	 * Returns the SQL for this query-result (without pagination, but with parameters accounted for)
	 * @return string
	 */
	public function toSql() {
		$parameters = ValueUtils::val($this->parameters);
		return $this->select->toSql($parameters, $this->connection);
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
