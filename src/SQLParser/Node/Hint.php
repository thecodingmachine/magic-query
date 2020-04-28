<?php


namespace SQLParser\Node;


/**
 * This class represent a SQL hint, for example USE INDEX (my_index)
 */
class Hint
{
    /**
     * @var string The hint type (example 'USE INDEX')
     */
    private $type;
    /**
     * @var string The hint list (example '(my_index)')
     */
    private $list;

    public function __construct(string $type, string $list)
    {
        $this->type = $type;
        $this->list = $list;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getList(): string
    {
        return $this->list;
    }
}
