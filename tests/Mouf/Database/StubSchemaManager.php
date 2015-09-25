<?php
namespace Mouf\Database;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;

/**
 * A stub for schema manager that simply returns the schema we are providing.
 */
class StubSchemaManager extends AbstractSchemaManager
{
    private $schema;

    public function __construct(Schema $schema)
    {
        $this->schema = $schema;
    }

    /**
     * Creates a schema instance for the current database.
     *
     * @return \Doctrine\DBAL\Schema\Schema
     */
    public function createSchema()
    {
        return $this->schema;
    }

    /**
     * Gets Table Column Definition.
     *
     * @param array $tableColumn
     *
     * @return \Doctrine\DBAL\Schema\Column
     */
    protected function _getPortableTableColumnDefinition($tableColumn)
    {
    }
}
