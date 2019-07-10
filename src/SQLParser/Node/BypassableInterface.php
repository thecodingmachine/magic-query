<?php

namespace SQLParser\Node;


interface BypassableInterface
{
    /**
     * Returns if this node should be removed from the tree.
     */
    public function canBeBypassed(array $parameters): bool;
}
