<?php
namespace Mouf\Database\QueryWriter;

use Mouf\Utils\Value\ArrayValueInterface;

use Mouf\Database\DBConnection\ConnectionInterface;

/**
 * Wraps the results of a PDOStatement.
 * 
 * @author David Negrier
 */
class ResultSet implements \Iterator {

	/**
	 * 
	 * @var \PDOStatement
	 */
	private $statement;
	private $castToClass;
	private $key = 0;
	private $result;
	private $fetched = false;
	private $rewindCalls = 0;
	
	public function __construct(\PDOStatement $statement, $castToClass = "") {
		$this->statement = $statement;
		$this->castToClass = $castToClass;
	}
	

	function rewind() {
		$this->rewindCalls++;
		if ($this->rewindCalls == 2) {
			throw new \Exception("Error: rewind is not possible in a database rowset. You can call 'foreach' on the rowset only once. Use CachedResultSet to be able to call the result several times. TODO: develop CachedResultSet");
		}
	}
	
	function current() {
		
		if (!$this->fetched) {
			$this->fetch();
		}
		
		return $this->result;
	}
	
	function key() {
		return $this->key;
	}
	
	function next() {
		++$this->key;
		$this->fetched = false;
		$this->fetch();
	}
	
	private function fetch() {
		$this->result = $this->statement->fetch(\PDO::FETCH_ASSOC);
		$this->fetched = true;
	}
	
	function valid() {

		if (!$this->fetched) {
			$this->fetch();
		}
		
		return $this->result !== false;
	}
}