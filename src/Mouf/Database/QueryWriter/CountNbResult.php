<?php

namespace Mouf\Database\QueryWriter;

use Doctrine\DBAL\Connection;
use Mouf\Utils\Value\IntValueInterface;

/**
 * A utility class that can compute the number of results returned by a query.
 * It does so by embedding the query into a SELECT count(*) query and computing the results.
 *
 * @Renderer { "smallLogo":"vendor/mouf/database.querywriter/icons/database_query.png" }
 *
 * @author David Negrier
 */
class CountNbResult implements IntValueInterface
{
    /**
     * The Select statement.
     *
     * @var QueryResult
     */
    private $queryResult;

    /**
     * The connection to the database.
     *
     * @var Connection
     */
    private $connection;

    /**
     * @Important $select
     *
     * @param QueryResult $queryResult The query we will perform "count" upon.
     * @param Connection  $connection
     */
    public function __construct(QueryResult $queryResult, Connection $connection)
    {
        $this->queryResult = $queryResult;
        $this->connection = $connection;
    }

    /**
     * (non-PHPdoc).
     *
     * @see \Mouf\Utils\Value\ArrayValueInterface::val()
     */
    public function val()
    {
        $sql = 'SELECT count(*) as cnt FROM ('.$this->queryResult->toSql().') tmp';

        return $this->connection->fetchOne(
            $sql,
            $this->queryResult->getParametersForBind(),
            $this->queryResult->getParameterTypesForBind(),
        );
    }
}
