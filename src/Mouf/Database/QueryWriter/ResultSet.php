<?php

namespace Mouf\Database\QueryWriter;

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
    /** @var array */
    private $result;
    /** @var bool */
    private $fetched = false;
    /** @var int */
    private $rewindCalls = 0;

    public function __construct(Statement $statement)
    {
        $this->statement = $statement;
    }

    public function rewind(): void
    {
        ++$this->rewindCalls;
        if ($this->rewindCalls == 2) {
            throw new \Exception("Error: rewind is not possible in a database rowset. You can call 'foreach' on the rowset only once. Use CachedResultSet to be able to call the result several times. TODO: develop CachedResultSet");
        }
    }

    public function current(): array
    {
        if (!$this->fetched) {
            $this->fetch();
        }

        return $this->result;
    }

    public function key(): int
    {
        return $this->key;
    }

    public function next(): void
    {
        ++$this->key;
        $this->fetched = false;
        $this->fetch();
    }

    private function fetch(): void
    {
        $this->result = $this->statement->execute()->fetchAllAssociative();
        $this->fetched = true;
    }

    public function valid(): bool
    {
        if (!$this->fetched) {
            $this->fetch();
        }

        return $this->result !== false;
    }
}
