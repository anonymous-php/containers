<?php

namespace Anonymous\Containers;


/**
 * Helper class for the FilePhpArrayContainer
 * @package Anonymous\Containers
 * @see FilePhpArrayContainer
 * @author Anonymous PHP Developer <anonym.php@gmail.com>
 */
class ReplaceArrayValue
{

    /** @var mixed */
    public $value;

    /**
     * Constructor.
     * @param mixed $value value used as replacement.
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

}