<?php

namespace Mouf\Database;

use Doctrine\Common\Cache\VoidCache;
use Mouf\Database\SchemaAnalyzer\SchemaAnalyzer;
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
     * @param \Doctrine\DBAL\Connection $connection
     * @param \Doctrine\Common\Cache\Cache $cache
     * @param SchemaAnalyzer $schemaAnalyzer (optional). If not set, it is initialized from the connection.
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
        $select = $this->parse($sql);
        return $this->toSql($select, $parameters);
    }

    /**
     * Parses the $sql passed in parameter and returns a tree representation of it.
     * This tree representation can be used to manipulate the SQL.
     *
     * @param string $sql
     * @return NodeInterface
     * @throws MagicQueryMissingConnectionException
     * @throws MagicQueryParserException
     */
    public function parse($sql) {
        // We choose md4 because it is fast.
        $cacheKey = "request_".hash("md4", $sql);
        $select = $this->cache->fetch($cacheKey);

        if ($select === false) {
            $parser = new SQLParser();
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
     * @return string
     */
    public function toSql(NodeInterface $sqlNode, array $parameters = array()) {
        return $sqlNode->toSql($parameters, $this->connection, 0, SqlRenderInterface::CONDITION_GUESS);
    }

    /**
     * Scans the SQL statement and replaces the "magicjoin" part with the correct joins.
     *
     * @param NodeInterface $select
     * @throws MagicQueryMissingConnectionException
     */
    private function magicJoin(NodeInterface $select) {
        // Let's find if this is a MagicJoin query.
        $magicJoinDetector = new DetectMagicJoinSelectVisitor();
        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor($magicJoinDetector);

        $nodeTraverser->walk($select);

        $magicJoinSelects = $magicJoinDetector->getMagicJoinSelects();
        if ($magicJoinSelects) {
            foreach ($magicJoinSelects as $magicJoinSelect) {
                // For each select in the query (there can be nested selects!), let's find the list of tables.
                $this->magicJoinOnOneQuery($magicJoinSelect);
            }
        }
    }

    /**
     * For one given MagicJoin select, let's apply MagicJoin
     * @param MagicJoinSelect $magicJoinSelect
     * @return Select
     */
    private function magicJoinOnOneQuery(MagicJoinSelect $magicJoinSelect) {
        $tableSearchNodeTraverser = new NodeTraverser();
        $detectTableVisitor = new DetectTablesVisitor();
        $tableSearchNodeTraverser->addVisitor($detectTableVisitor);

        $select = $magicJoinSelect->getSelect();

        $tableSearchNodeTraverser->walk($select);
        $tables = $detectTableVisitor->getTables();

        $mainTable = $magicJoinSelect->getMainTable();
        // Let's remove the main table from the list of tables to be linked:
        unset($tables[$mainTable]);

        $foreignKeysSet = new \SplObjectStorage();
        $completePath = [];

        foreach ($tables as $table) {
            $path = $this->getSchemaAnalyzer()->getShortestPath($mainTable, $table);
            foreach ($path as $foreignKey) {
                // If the foreign key is not already in our complete path, let's add it.
                if (!$foreignKeysSet->contains($foreignKey)) {
                    $completePath[] = $foreignKey;
                    $foreignKeysSet->attach($foreignKey);
                }
            }
        }

        // At this point, we have a complete path, we now just have to rewrite the FROM section.
        $tableNode = new Table();
        $tableNode->setTable($mainTable);
        $tables = [
            $tableNode
        ];
        $currentTable = $mainTable;

        foreach ($completePath as $foreignKey) {
            /* @var $foreignKey \Doctrine\DBAL\Schema\ForeignKeyConstraint */

            $onNode = new Equal();
            $leftCol = new ColRef();
            $leftCol->setTable($foreignKey->getLocalTableName());
            $leftCol->setColumn($foreignKey->getLocalColumns()[0]);

            $rightCol = new ColRef();
            $rightCol->setTable($foreignKey->getForeignTableName());
            $rightCol->setColumn($foreignKey->getForeignColumns()[0]);

            $onNode->setLeftOperand($leftCol);
            $onNode->setRightOperand($rightCol);

            $tableNode = new Table();
            $tableNode->setJoinType("LEFT JOIN");
            $tableNode->setRefClause($onNode);

            if ($foreignKey->getLocalTableName() == $currentTable) {
                $tableNode->setTable($foreignKey->getForeignTableName());
                $currentTable = $foreignKey->getForeignTableName();
            } else {
                $tableNode->setTable($foreignKey->getLocalTableName());
                $currentTable = $foreignKey->getLocalTableName();
            }

            $tables[] = $tableNode;
        }

        $select->setFrom($tables);

    }

    /**
     * @return SchemaAnalyzer
     */
    private function getSchemaAnalyzer() {
        if ($this->schemaAnalyzer === null) {
            if (!$this->schemaAnalyzer) {
                throw new MagicQueryMissingConnectionException('In order to use MagicJoin, you need to configure a DBAL connection.');
            }

            $this->schemaAnalyzer = new SchemaAnalyzer($this->connection->getSchemaManager(), $this->cache, $this->getConnectionUniqueId());
        }
        return $this->schemaAnalyzer;
    }

    private function getConnectionUniqueId() {
        return hash('md4', $this->connection->getHost()."-".$this->connection->getPort()."-".$this->connection->getDatabase()."-".$this->connection->getDriver()->getName());
    }
}
