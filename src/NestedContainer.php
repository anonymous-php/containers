<?php

namespace Anonymous\Containers;


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

    protected $setDowngraded = false;

    protected $useHasNested = false;

    protected $useHasCache = false;
    protected $hasCacheLength = 100;

    protected $hasCache = [];


    /**
     * @inheritdoc
     */
    public function has($id)
    {
        // Check cache
        if ($this->useHasNested && $this->getHasCache($id) !== false) {
            return true;
        }

        // This container has the key
        if (parent::has($id)) {
            return $this->setHasCache($id, null);
        }

        // Do we need to test nested
        if (!$this->useHasNested) {
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

                if ($this->setDowngraded) {
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
     */
    public function add(ContainerInterface $container)
    {
        array_push($this->containers, $container);
    }

    /**
     * Sets the property useHasCache and size of cache
     * @param bool $useHasCache
     * @param null $hasCacheLength
     * @return $this
     */
    public function useHasCache($useHasCache = true, $hasCacheLength = null)
    {
        $this->useHasCache = (bool)$useHasCache;

        if ($hasCacheLength !== null) {
            $this->hasCacheLength = (int)$hasCacheLength;
        }

        return $this;
    }

    /**
     * Test has result on nested containers
     * @param bool $useHasNested
     * @return $this
     */
    public function useHasNested($useHasNested = true)
    {
        $this->useHasNested = $useHasNested;

        return $this;
    }

    /**
     * Sets the property setDowngraded
     * @param bool $setDowngraded
     * @return $this
     */
    public function setDowngraded($setDowngraded = true)
    {
        $this->setDowngraded = (bool)$setDowngraded;

        return $this;
    }

}