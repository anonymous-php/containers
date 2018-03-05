<?php

namespace Anonymous\Containers;


use Anonymous\Containers\Exceptions\FileNotFoundException;

/**
 * Container with files as a source
 * @package Anonymous\Containers
 * @author Anonymous PHP Developer <anonym.php@gmail.com>
 */
class FilePhpArrayContainer extends DotAccessContainer
{

    protected $filesLoaded;

    protected $files = [];
    protected $strict = false;


    /**
     * FilePhpArrayContainer constructor
     * @param array|string $files
     * @param bool $strict
     * @throws FileNotFoundException
     */
    public function __construct($files = [], $strict = false)
    {
        parent::__construct();

        $this->files = (array)$files;
        $this->strict = $strict;

        if ($this->strict) {
            // Check for files existence
            foreach ($this->files as $file) {
                if (!is_file($file)) {
                    throw new FileNotFoundException("No file with name '{$file}' founded");
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function get($id)
    {
        $this->loadFiles();

        return parent::get($id);
    }

    /**
     * @inheritdoc
     */
    public function has($id)
    {
        $this->loadFiles();

        return parent::has($id);
    }

    /**
     * Loads files
     */
    protected function loadFiles()
    {
        if ($this->filesLoaded) {
            return;
        }

        foreach ($this->files as $file) {
            $this->definitions = $this->arrayMerge($this->definitions, $this->loadFile($file));
        }

        $this->filesLoaded = true;
    }

    /**
     * Loads file
     * @param $file
     * @return mixed
     */
    protected function loadFile($file)
    {
        set_error_handler(function () {});
        $data = include $file;
        restore_error_handler();

        return $data;
    }

    /**
     * Merges arrays
     * @param $a
     * @param $b
     * @return array
     */
    protected function arrayMerge($a, $b)
    {
        $args = func_get_args();
        $res = array_shift($args);

        while (!empty($args)) {
            $next = array_shift($args);
            foreach ($next as $k => $v) {
                if ($v instanceof UnsetArrayValue) {
                    unset($res[$k]);
                } elseif ($v instanceof ReplaceArrayValue) {
                    $res[$k] = $v->value;
                } elseif (is_int($k)) {
                    if (array_key_exists($k, $res)) {
                        $res[] = $v;
                    } else {
                        $res[$k] = $v;
                    }
                } elseif (is_array($v) && isset($res[$k]) && is_array($res[$k])) {
                    $res[$k] = $this->arrayMerge($res[$k], $v);
                } else {
                    $res[$k] = $v;
                }
            }
        }

        return $res;
    }

}