<?php
namespace SQLParser\Node\Traverser;

use SQLParser\Node\ColRef;

/**
 * This exception is thrown when a column does not fully specify a table name.
 */
class MissingTableRefException extends \Exception
{
    private $missingTableColRef;

    /**
     * @return ColRef
     */
    public function getMissingTableColRef()
    {
        return $this->missingTableColRef;
    }

    /**
     * @param ColRef $missingTableColRef
     */
    public function setMissingTableColRef(ColRef $missingTableColRef)
    {
        $this->missingTableColRef = $missingTableColRef;
    }
}
