<?php
namespace Mouf\Database;
use SQLParser\Query\StatementFactory;
use SQLParser\SQLParser;
use SQLParser\SqlRenderInterface;

/**
 * The class MagicQuery offers special SQL voodoo methods to automatically strip down unused parameters
 * from parameterized SQL statements.
 */
class MagicQuery
{
    private $connection;

    /**
     * @param Doctrine\DBAL\Connection $connection
     */
    public function __construct($connection = null) {
        $this->connection = $connection;
    }

    /**
     * Returns merged SQL from $sql and $parameters. Any parameters not available will be striped down
     * from the SQL.
     *
     * @param string $sql
     * @param array $parameters
     * @return string
     */
    public function build($sql, array $parameters = array()) {
        $parser = new SQLParser();
        $parsed = $parser->parse($sql);

        if ($parsed == false) {
            throw new MagicQueryParserException('Unable to parse query "'.$sql.'"');
        }

        $select = StatementFactory::toObject($parsed);

        $sql = $select->toSql($parameters, $this->connection, 0, SqlRenderInterface::CONDITION_GUESS);
        return $sql;
    }
}
