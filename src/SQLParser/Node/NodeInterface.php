<?php

namespace SQLParser\Node;

use SQLParser\SqlRenderInterface;
use Mouf\MoufManager;
use Mouf\MoufInstanceDescriptor;

/**
 * This base interface for anything that can represent a SQL expression part.
 *
 * @author David Négrier <d.negrier@thecodingmachine.com>
 */
interface NodeInterface extends SqlRenderInterface
{
    /**
     * Returns a Mouf instance descriptor describing this object.
     *
     * @param MoufManager $moufManager
     *
     * @return MoufInstanceDescriptor
     */
    public function toInstanceDescriptor(MoufManager $moufManager);
}
