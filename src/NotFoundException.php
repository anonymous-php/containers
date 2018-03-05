<?php

namespace Anonymous\Containers;


use Psr\Container\NotFoundExceptionInterface;

/**
 * Class NotFoundException
 * @package Anonymous\Containers
 * @author Anonymous PHP Developer <anonym.php@gmail.com>
 */
class NotFoundException extends ContainerException implements NotFoundExceptionInterface {}