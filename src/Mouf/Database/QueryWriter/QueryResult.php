<?php

namespace Mouf\Database\QueryWriter;

use Mouf\Database\QueryWriter\Utils\DbHelper;
use Mouf\Utils\Value\ValueUtils;
use SQLParser\Query\Select;
use Mouf\Utils\Common\PaginableInterface;
use Mouf\Utils\Value\ArrayValueInterface;
use Mouf\Utils\Value\ValueInterface;
use Doctrine\DBAL\Connection;
use Mouf\Utils\Common\SortableInterface;
use SQLParser\Node\NodeInterface;
use SQLParser\Node\ColRef;

/**
 * A class that can execute a query and expose the results.
 *
 * @Renderer { "smallLogo":"vendor/mouf/database.querywriter/icons/database_query.png" }
 *
 * @author David Negrier
 */
class QueryResult implements ArrayValueInterface, PaginableInterface, SortableInterface
{
    /**
     * The Select statement.
     *
     * @var Select
     */
    private $select;

    /**
     * The connection to the database.
     *
     * @var Connection
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
     * @param Select     $select
     * @param Connection $connection
     */
    public function __construct(Select $select, Connection $connection)
    {
        $this->select = $select;
        $this->connection = $connection;
    }

    /**
     * The list of parameters to apply to the SQL request.
     *
     * @param array<string, string>|array<string, ValueInterface>|ArrayValueInterface $parameters
     */
    public function setParameters($parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * (non-PHPdoc).
     *
     * @see \Mouf\Utils\Value\ArrayValueInterface::val()
     */
    public function val()
    {
        $parameters = ValueUtils::val($this->parameters);
        $pdoStatement = $this->connection->query($this->select->toSql($parameters, $this->connection).DbHelper::getFromLimitString($this->offset, $this->limit));

        return new ResultSet($pdoStatement);
    }

    /**
     * Returns the SQL for this query-result (without pagination, but with parameters accounted for).
     *
     * @return string
     */
    public function toSql()
    {
        $parameters = ValueUtils::val($this->parameters);

        return $this->select->toSql($parameters, $this->connection);
    }

    /**
     * Paginates the result set.
     *
     * @param int $limit
     * @param int $offset
     */
    public function paginate($limit, $offset = 0)
    {
        $this->limit = $limit;
        $this->offset = $offset;
    }

    /* (non-PHPdoc)
     * @see \Mouf\Utils\Common\SortableInterface::sort()
     */
    public function sort($key, $direction = SortableInterface::ASC)
    {
        $result = $this->findColumnByKey($key);
        if ($result != null) {
            $columnObj = clone($result);
            if (method_exists($columnObj, 'setAlias')) {
                $columnObj->setAlias(null);
            }
            $columnObj->setDirection($direction);
        } else {
            $columnObj = new ColRef();
            $columnObj->setColumn($key);
            $columnObj->setDirection($direction);
        }
        $this->select->setOrder(array($columnObj));
    }

    /**
     * Returns the object representing a column from the key passed in parameter.
     * It will first scan the column aliases to find if an alias match the key, then the column names, etc...
     * It will throw an exception if the column cannot be found.
     *
     * @param string $key
     *
     * @return NodeInterface
     */
    private function findColumnByKey($key)
    {
        $columns = $this->select->getColumns();
        foreach ($columns as $column) {
            if (method_exists($column, 'getAlias')) {
                $alias = $column->getAlias();
                if ($alias === $key) {
                    return $column;
                }
            }
            if ($column instanceof ColRef) {
                if ($column->getColumn() === $key) {
                    return $column;
                }
            }
        }

        return;
    }
}
