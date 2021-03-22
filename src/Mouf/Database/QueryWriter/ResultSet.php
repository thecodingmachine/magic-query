<?php

namespace Mouf\Database\QueryWriter;

use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\Driver\Statement;

/**
 * Wraps the results of a PDOStatement.
 *
 * @author David Negrier
 */
class ResultSet implements \Iterator
{
    /** @var Statement */
    private $statement;
    /** @var int */
    private $key = 0;
    /** @var array|false */
    private $result;
    /** @var bool */
    private $fetched = false;
    /** @var int */
    private $rewindCalls = 0;

    public function __construct(Statement $statement)
    {
        $this->statement = $statement;
    }

    public function rewind()
    {
        ++$this->rewindCalls;
        if ($this->rewindCalls == 2) {
            throw new \Exception("Error: rewind is not possible in a database rowset. You can call 'foreach' on the rowset only once. Use CachedResultSet to be able to call the result several times. TODO: develop CachedResultSet");
        }
    }

    /**
     * @return array|false
     */
    public function current()
    {
        if (!$this->fetched) {
            $this->fetch();
        }

        return $this->result;
    }

    /**
     * @return int
     */
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

    private function fetch(): void
    {
        $this->result = $this->statement->fetch(FetchMode::ASSOCIATIVE);
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
