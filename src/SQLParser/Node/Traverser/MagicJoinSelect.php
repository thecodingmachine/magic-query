<?php

namespace SQLParser\Node\Traverser;

use SQLParser\Query\Select;

/**
 * Wraps a select that contains a MagicJoin.
 */
class MagicJoinSelect
{
    /**
     * @var Select
     */
    private $select;

    /**
     * @var string
     */
    private $mainTable;

    /**
     * MagicJoinSelect constructor.
     *
     * @param Select $select
     * @param string $mainTable
     */
    public function __construct(Select $select, $mainTable)
    {
        $this->select = $select;
        $this->mainTable = $mainTable;
    }

    /**
     * @return Select
     */
    public function getSelect()
    {
        return $this->select;
    }

    /**
     * @return string
     */
    public function getMainTable()
    {
        return $this->mainTable;
    }
}
