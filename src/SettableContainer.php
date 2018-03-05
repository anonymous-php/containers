<?php

namespace Anonymous\Containers;


/**
 * Simple container with the possibility to set values
 * @package Anonymous\Containers
 * @author Anonymous PHP Developer <anonym.php@gmail.com>
 */
class SettableContainer extends ArrayContainer implements SettableContainerInterface
{

    /**
     * @inheritdoc
     */
    public function set($id, $value)
    {
        $this->definitions[$id] = $value;
    }

}