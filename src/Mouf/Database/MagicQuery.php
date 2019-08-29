<?php

namespace Mouf\Database;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use function array_filter;
use function array_keys;
use Doctrine\Common\Cache\VoidCache;
use function hash;
use function implode;
use Mouf\Database\MagicQuery\Twig\SqlTwigEnvironmentFactory;
use Mouf\Database\SchemaAnalyzer\SchemaAnalyzer;
use PHPSQLParser\PHPSQLParser;
use SQLParser\Node\ColRef;
use SQLParser\Node\Equal;
use SQLParser\Node\NodeInterface;
use SQLParser\Node\Table;
use SQLParser\Node\Traverser\DetectMagicJoinSelectVisitor;
use SQLParser\Node\Traverser\DetectTablesVisitor;
use SQLParser\Node\Traverser\MagicJoinSelect;
use SQLParser\Node\Traverser\NodeTraverser;
use SQLParser\Query\Select;
use SQLParser\Query\StatementFactory;
use SQLParser\SQLParser;
use SQLParser\SqlRenderInterface;

/**
 * The class MagicQuery offers special SQL voodoo methods to automatically strip down unused parameters
 * from parametrized SQL statements.
 */
class MagicQuery
{
    private $connection;
    private $cache;
    private $schemaAnalyzer;
    /**
     * @var \Twig_Environment
     */
    private $twigEnvironment;
    private $enableTwig = false;

    /**
     * @param \Doctrine\DBAL\Connection    $connection
     * @param \Doctrine\Common\Cache\Cache $cache
     * @param SchemaAnalyzer               $schemaAnalyzer (optional). If not set, it is initialized from the connection.
     */
    public function __construct($connection = null, $cache = null, SchemaAnalyzer $schemaAnalyzer = null)
    {
        $this->connection = $connection;
        if ($cache) {
            $this->cache = $cache;
        } else {
            $this->cache = new VoidCache();
        }
        if ($schemaAnalyzer) {
            $this->schemaAnalyzer = $schemaAnalyzer;
        }
    }

    /**
     * Whether Twig parsing should be enabled or not.
     * Defaults to false.
     *
     * @param bool $enableTwig
     *
     * @return $this
     */
    public function setEnableTwig($enableTwig = true)
    {
        $this->enableTwig = $enableTwig;

        return $this;
    }

    /**
     * Returns merged SQL from $sql and $parameters. Any parameters not available will be striped down
     * from the SQL.
     *
     * This is equivalent to calling `parse` and `toSql` successively.
     *
     * @param string $sql
     * @param array  $parameters
     *
     * @return string
     */
    public function build($sql, array $parameters = array())
    {
        if ($this->enableTwig) {
            $sql = $this->getTwigEnvironment()->render($sql, $parameters);
        }

        $select = $this->parse($sql);
        $newSql = $this->toSql($select, $parameters, true);

        return $newSql;
    }

    /**
     * Returns modified SQL from $sql and $parameters. Any parameters not available will be striped down
     * from the SQL. Unlike with the `build` method, the parameters are NOT merged into the SQL.
     * This method is more efficient than `build`  (because result is cached and statements interpolation
     * can be delegated to the database.
     *
     * @param string $sql
     * @param array  $parameters
     *
     * @return string
     */
    public function buildPreparedStatement(string $sql, array $parameters = []): string
    {
        if ($this->enableTwig) {
            $sql = $this->getTwigEnvironment()->render($sql, $parameters);
        }

        $availableParameterKeys = array_keys(array_filter($parameters, static function($param) { return $param !== null;}));
        // We choose md4 because it is fast.
        $cacheKey = 'request_build_'.hash('md4', $sql.'__'.implode('_/_', $availableParameterKeys));
        $newSql = $this->cache->fetch($cacheKey);
        if ($newSql === false) {
            $select = $this->parse($sql);
            $newSql = $this->toSql($select, $parameters, false);

            $this->cache->save($cacheKey, $newSql);
        }

        return $newSql;
    }

    /**
     * Parses the $sql passed in parameter and returns a tree representation of it.
     * This tree representation can be used to manipulate the SQL.
     *
     * @param string $sql
     *
     * @return NodeInterface
     *
     * @throws MagicQueryMissingConnectionException
     * @throws MagicQueryParserException
     */
    public function parse($sql)
    {
        // We choose md4 because it is fast.
        $cacheKey = 'request_'.hash('md4', $sql);
        $select = $this->cache->fetch($cacheKey);

        if ($select === false) {
            //$parser = new SQLParser();
            $parser = new PHPSQLParser();
            $parsed = $parser->parse($sql);

            if ($parsed == false) {
                throw new MagicQueryParserException('Unable to parse query "'.$sql.'"');
            }

            $select = StatementFactory::toObject($parsed);

            $this->magicJoin($select);

            // Let's store the tree
            $this->cache->save($cacheKey, $select);
        }

        return $select;
    }

    /**
     * Transforms back a tree of SQL node into a SQL string.
     *
     * @param NodeInterface $sqlNode
     * @param array $parameters
     * @param bool $extrapolateParameters Whether the parameters should be fed into the returned SQL query

     * @return string
     */
    public function toSql(NodeInterface $sqlNode, array $parameters = array(), bool $extrapolateParameters = true)
    {
        $platform = $this->connection ? $this->connection->getDatabasePlatform() : new MySqlPlatform();
        return (string) $sqlNode->toSql($parameters, $platform, 0, SqlRenderInterface::CONDITION_GUESS, $extrapolateParameters);
    }

    /**
     * Scans the SQL statement and replaces the "magicjoin" part with the correct joins.
     *
     * @param NodeInterface $select
     *
     * @throws MagicQueryMissingConnectionException
     */
    private function magicJoin(NodeInterface $select)
    {
        // Let's find if this is a MagicJoin query.
        $magicJoinDetector = new DetectMagicJoinSelectVisitor();
        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor($magicJoinDetector);

        $nodeTraverser->walk($select);

        $magicJoinSelects = $magicJoinDetector->getMagicJoinSelects();
        foreach ($magicJoinSelects as $magicJoinSelect) {
            // For each select in the query (there can be nested selects!), let's find the list of tables.
            $this->magicJoinOnOneQuery($magicJoinSelect);
        }
    }

    /**
     * For one given MagicJoin select, let's apply MagicJoin.
     *
     * @param MagicJoinSelect $magicJoinSelect
     *
     * @return Select
     */
    private function magicJoinOnOneQuery(MagicJoinSelect $magicJoinSelect)
    {
        $tableSearchNodeTraverser = new NodeTraverser();
        $detectTableVisitor = new DetectTablesVisitor($magicJoinSelect->getMainTable());
        $tableSearchNodeTraverser->addVisitor($detectTableVisitor);

        $select = $magicJoinSelect->getSelect();

        $tableSearchNodeTraverser->walk($select);
        $tables = $detectTableVisitor->getTables();

        $mainTable = $magicJoinSelect->getMainTable();
        // Let's remove the main table from the list of tables to be linked:
        unset($tables[$mainTable]);

        $foreignKeysSet = [];
        $completePath = [];

        foreach ($tables as $table) {
            $path = $this->getSchemaAnalyzer()->getShortestPath($mainTable, $table);
            foreach ($path as $foreignKey) {
                // If the foreign key is not already in our complete path, let's add it.
                if (!isset($foreignKeysSet[$foreignKey->getName()])) {
                    $completePath[] = $foreignKey;
                    $foreignKeysSet[$foreignKey->getName()] = true;
                }
            }
        }

        // At this point, we have a complete path, we now just have to rewrite the FROM section.
        $tableNode = new Table();
        $tableNode->setTable($mainTable);
        $tables = [
            $mainTable => $tableNode,
        ];

        foreach ($completePath as $foreignKey) {
            /* @var $foreignKey \Doctrine\DBAL\Schema\ForeignKeyConstraint */

            $onNode = new Equal();
            $leftCol = new ColRef();
            $leftCol->setTable($foreignKey->getLocalTableName());
            // For some reasons, with Oracle, DBAL returns quoted identifiers. We need to unquote them.
            $leftCol->setColumn($foreignKey->getUnquotedLocalColumns()[0]);

            $rightCol = new ColRef();
            $rightCol->setTable($foreignKey->getForeignTableName());
            $rightCol->setColumn($foreignKey->getUnquotedForeignColumns()[0]);

            $onNode->setLeftOperand($leftCol);
            $onNode->setRightOperand($rightCol);

            $tableNode = new Table();
            $tableNode->setJoinType('LEFT JOIN');
            $tableNode->setRefClause($onNode);

            if (isset($tables[$foreignKey->getLocalTableName()])) {
                $tableNode->setTable($foreignKey->getForeignTableName());
                $tables[$foreignKey->getForeignTableName()] = $tableNode;
            } else {
                $tableNode->setTable($foreignKey->getLocalTableName());
                $tables[$foreignKey->getLocalTableName()] = $tableNode;
            }
        }

        $select->setFrom($tables);
    }

    /**
     * @return SchemaAnalyzer
     */
    private function getSchemaAnalyzer()
    {
        if ($this->schemaAnalyzer === null) {
            if (!$this->connection) {
                throw new MagicQueryMissingConnectionException('In order to use MagicJoin, you need to configure a DBAL connection.');
            }

            $this->schemaAnalyzer = new SchemaAnalyzer($this->connection->getSchemaManager(), $this->cache, $this->getConnectionUniqueId($this->connection));
        }

        return $this->schemaAnalyzer;
    }

    private function getConnectionUniqueId(Connection $connection)
    {
        return hash('md4', $connection->getHost().'-'.$connection->getPort().'-'.$connection->getDatabase().'-'.$connection->getDriver()->getName());
    }

    /**
     * @return \Twig_Environment
     */
    private function getTwigEnvironment()
    {
        if ($this->twigEnvironment === null) {
            $this->twigEnvironment = SqlTwigEnvironmentFactory::getTwigEnvironment();
        }

        return $this->twigEnvironment;
    }
}
