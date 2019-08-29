<?php

namespace Mouf\Database\QueryWriter;

use Doctrine\DBAL\Statement;

/**
 * Wraps the results of a PDOStatement.
 *
 * @author David Negrier
 */
class ResultSet implements \Iterator
{
    /**
     * @var \PDOStatement|Statement
     */
    private $statement;
    private $castToClass;
    private $key = 0;
    private $result;
    private $fetched = false;
    private $rewindCalls = 0;

    public function __construct($statement, $castToClass = '')
    {
        $this->statement = $statement;
        $this->castToClass = $castToClass;
    }

    public function rewind()
    {
        ++$this->rewindCalls;
        if ($this->rewindCalls == 2) {
            throw new \Exception("Error: rewind is not possible in a database rowset. You can call 'foreach' on the rowset only once. Use CachedResultSet to be able to call the result several times. TODO: develop CachedResultSet");
        }
    }

    public function current()
    {
        if (!$this->fetched) {
            $this->fetch();
        }

        return $this->result;
    }

    public function key()
    {
        return $this->key;
    }

    public function next()
    {
        ++$this->key;
        $this->fetched = false;
        $this->fetch();
    }

    private function fetch()
    {
        $this->result = $this->statement->fetch(\PDO::FETCH_ASSOC);
        $this->fetched = true;
    }

    public function valid()
    {
        if (!$this->fetched) {
            $this->fetch();
        }

        return $this->result !== false;
    }
}
