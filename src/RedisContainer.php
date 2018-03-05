<?php

namespace Anonymous\Containers;


use Anonymous\Containers\Exceptions\RedisConnectionException;
use Anonymous\Containers\Exceptions\RedisException;
use Anonymous\Containers\Exceptions\NotFoundException;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class RedisContainer
 * @package Anonymous\Containers
 * @author Anonymous PHP Developer <anonym.php@gmail.com>
 */
class RedisContainer extends SettableContainer
{

    protected $hashName = 'redisContainerData';

    protected $allowedClients = ['\\Redis', '\\Predis\\Client'];

    protected $prefill = true;
    protected $prefilled;

    /** @var \Redis */
    protected $writeConnection;

    /** @var \Redis|null */
    protected $readConnection;


    /**
     * RedisContainer constructor
     * @param $writeConnection
     * @param null $readConnection
     * @param bool $prefill
     * @throws RedisConnectionException
     */
    public function __construct($writeConnection, $readConnection = null, $prefill = true)
    {
        parent::__construct();

        $this->writeConnection = $this->testConnectionType($writeConnection);
        $this->readConnection = $this->testConnectionType($readConnection, true);
        $this->prefill = $prefill;
    }

    /**
     * @inheritdoc
     */
    public function get($id)
    {
        $this->prefill();

        if ($this->prefilled) {
            return parent::get($id);
        }

        try {
            if (!$this->getReadConnection()->hExists($this->hashName, $id)) {
                throw new NotFoundException("No entry found for '{$id}'");
            }

            return $this->getReadConnection()->hGet($this->hashName, $id);
        } catch (\Exception $exception) {
            if ($exception instanceof NotFoundExceptionInterface) {
                throw $exception;
            }

            throw new RedisException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * @inheritdoc
     */
    public function has($id)
    {
        $this->prefill();

        if ($this->prefilled) {
            return parent::has($id);
        }

        try {
            return $this->getReadConnection()->hExists($this->hashName, $id);
        } catch (\Exception $exception) {
            throw new RedisException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * @inheritdoc
     */
    public function set($id, $value)
    {
        try {
            $this->getWriteConnection()->hSet($this->hashName, $id, $value);

            if ($this->prefilled) {
                parent::set($id, $value);
            }
        } catch (\Exception $exception) {
            throw new RedisException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * Returns Redis write connection
     * @return \Redis
     */
    protected function getWriteConnection()
    {
        return $this->writeConnection;
    }

    /**
     * Returns Redis read connection
     * @return \Redis
     */
    protected function getReadConnection()
    {
        return $this->readConnection !== null
            ? $this->readConnection
            : $this->getWriteConnection();
    }

    /**
     * Loads all container data from Redis
     * @throws RedisException
     */
    protected function prefill()
    {
        if (!$this->prefill || $this->prefilled) {
            return;
        }

        try {
            $this->definitions = $this->getReadConnection()->hGetAll($this->hashName);
            $this->prefilled = true;
        } catch (\Exception $exception) {
            throw new RedisException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * Checks if connection is allowed
     * @param $connection
     * @param bool $nullable
     * @return mixed
     * @throws RedisConnectionException
     */
    protected function testConnectionType($connection, $nullable = false)
    {
        if ($nullable && $connection === null) {
            return $connection;
        }

        foreach ($this->allowedClients as $class) {
            if (class_exists($class) && $connection instanceof $class) {
                return $connection;
            }
        }

        throw new RedisConnectionException('Connection of unknown type provided');
    }

    /**
     * Setter for the prefill property
     * @param bool $prefill
     * @return $this
     */
    public function setPrefill($prefill = true)
    {
        $this->prefill = $prefill;

        return $this;
    }

    /**
     * Setter for tht hashName property
     * @param $hashName
     * @return $this
     */
    public function setHashName($hashName)
    {
        $this->hashName = $hashName;

        return $this;
    }

}