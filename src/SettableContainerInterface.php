<?php

namespace Anonymous\Containers;


use Psr\Container\ContainerExceptionInterface;

/**
 * Interface defines method set
 * @package Anonymous\Containers
 * @author Anonymous PHP Developer <anonym.php@gmail.com>
 */
interface SettableContainerInterface
{

    /**
     * Sets the value to the container
     * @param $id
     * @param $value
     * @return void
     * @throws ContainerExceptionInterface
     */
    public function set($id, $value);

}