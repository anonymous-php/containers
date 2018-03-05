<?php

namespace Anonymous\Containers;


use Anonymous\Containers\Exceptions\NotFoundException;
use Psr\Container\ContainerInterface;

/**
 * Simple implementation of Psr\Container\ContainerInterface with the array as a storage
 * @package Anonymous\Containers
 * @author Anonymous PHP Developer <anonym.php@gmail.com>
 */
class ArrayContainer implements ContainerInterface
{

    protected $definitions = [];


    /**
     * Container constructor
     * @param array $definitions
     */
    public function __construct(array $definitions = [])
    {
        $this->definitions = $definitions;
    }

    /**
     * @inheritdoc
     */
    public function get($id)
    {
        if (!array_key_exists($id, $this->definitions)) {
            throw new NotFoundException("No entry found for '{$id}'");
        }

        return $this->definitions[$id];
    }

    /**
     * @inheritdoc
     */
    public function has($id)
    {
        return array_key_exists($id, $this->definitions);
    }

}