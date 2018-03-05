<?php

namespace Anonymous\Containers;


use Anonymous\Containers\Exceptions\ContainerNotFoundException;
use Anonymous\Containers\Exceptions\NotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Container which can work with the collection of nested containers
 * @package Anonymous\Containers
 * @author Anonymous PHP Developer <anonym.php@gmail.com>
 */
class NestedContainer extends SettableContainer
{

    /** @var ContainerInterface[] */
    protected $containers = [];

    protected $saveIfFound = false;
    protected $checkIfNestedHas = false;

    protected $useHasCache = false;
    protected $hasCacheLength = 100;

    protected $hasCache = [];


    /**
     * @inheritdoc
     */
    public function has($id)
    {
        // Check cache
        if ($this->checkIfNestedHas && $this->getHasCache($id) !== false) {
            return true;
        }

        // This container has the key
        if (parent::has($id)) {
            return $this->setHasCache($id, null);
        }

        // Do we need to test nested
        if (!$this->checkIfNestedHas) {
            return false;
        }

        // Check added containers for the key
        foreach ($this->containers as $index => $container) {
            if ($container->has($id)) {
                return $this->setHasCache($id, $index);
            }
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function get($id)
    {
        // Check cache for the container index
        if (($containerIndex = $this->getHasCache($id)) !== false) {
            return $containerIndex !== null
                ? $this->containers[$containerIndex]->get($id)
                : parent::get($id);
        }

        try {
            $value = parent::get($id);
            $this->setHasCache($id, null);

            return $value;
        } catch (NotFoundException $exception) {}

        foreach ($this->containers as $index => $container) {
            try {
                $value = $container->get($id);

                if ($this->saveIfFound) {
                    parent::set($id, $value);
                    $index = null;
                }

                $this->setHasCache($id, $index);

                return $value;
            } catch (\Exception $exception) {
                if (!$exception instanceof NotFoundExceptionInterface) {
                    throw $exception;
                }
            }
        }

        throw new NotFoundException("No entry found for '{$id}'");
    }

    /**
     * @inheritdoc
     */
    public function set($id, $value)
    {
        parent::set($id, $value);
        $this->setHasCache($id, null);

        foreach ($this->containers as $container) {
            if ($container instanceof SettableContainer || method_exists($container, 'set')) {
                try {
                    $container->set($id, $value);
                } catch (\Exception $exception) {
                    if (!$exception instanceof ContainerExceptionInterface) {
                        throw $exception;
                    }
                }
            }
        }
    }

    /**
     * Stores index of the container to the cache
     * @param $id
     * @param $value
     * @return bool
     */
    protected function setHasCache($id, $value)
    {
        if (!$this->useHasCache) {
            return true;
        }

        unset($this->hasCache[$id]);
        $this->hasCache[$id] = $value;

        if (count($this->hasCache) > $this->hasCacheLength) {
            $this->hasCache = array_slice($this->hasCache, -$this->hasCacheLength, null, true);
        }

        return true;
    }

    /**
     * Gets index of the container from the cache
     * @param $id
     * @return bool|mixed
     */
    protected function getHasCache($id)
    {
        return $this->useHasCache && array_key_exists($id, $this->hasCache)
            ? $this->hasCache[$id]
            : false;
    }

    /**
     * Adds container to the collection
     * @param ContainerInterface $container
     * @param string $name
     */
    public function add(ContainerInterface $container, $name = null)
    {
        if ($name !== null) {
            $this->containers[$name] = $container;
        } else {
            array_push($this->containers, $container);
        }
    }

    /**
     * Gets named container
     * @param $name
     * @return ContainerInterface
     * @throws ContainerNotFoundException
     */
    public function getContainer($name)
    {
        if (!array_key_exists($name, $this->containers)) {
            throw new ContainerNotFoundException("No container with name '{$name}' found");
        }

        return $this->containers[$name];
    }

    /**
     * Sets the property useHasCache and size of cache
     * @param bool $useHasCache
     * @param null $hasCacheLength
     * @return $this
     */
    public function setUseHasCache($useHasCache = true, $hasCacheLength = null)
    {
        $this->useHasCache = (bool)$useHasCache;

        if ($hasCacheLength !== null) {
            $this->hasCacheLength = (int)$hasCacheLength;
        }

        return $this;
    }

    /**
     * Test has result on nested containers
     * @param bool $checkIfNestedHas
     * @return $this
     */
    public function setCheckIfNestedHas($checkIfNestedHas = true)
    {
        $this->checkIfNestedHas = $checkIfNestedHas;

        return $this;
    }

    /**
     * Sets the property saveIfFound
     * @param bool $saveIfFound
     * @return $this
     */
    public function setSaveIfFound($saveIfFound = true)
    {
        $this->saveIfFound = (bool)$saveIfFound;

        return $this;
    }

}