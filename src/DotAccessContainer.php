<?php

namespace Anonymous\Containers;


/**
 * Accepts dot key notation to access to elements of the multidimensional array of definitions.
 * Useful for configs for example.
 * @package Anonymous\Containers
 */
class DotAccessContainer extends ArrayContainer
{

    /**
     * @inheritdoc
     */
    public function get($id)
    {
        list($has, $value) = $this->find($this->definitions, $id);

        if (!$has) {
            throw new NotFoundException();
        }

        return $value;
    }

    /**
     * @inheritdoc
     */
    public function has($id)
    {
        list($has, $value) = $this->find($this->definitions, $id);

        return $has;
    }

    /**
     * Checks existence and returns the value of the key
     * @param array $array
     * @param string $key
     * @return array
     */
    protected function find($array, $key)
    {
        if (empty($key) && !is_numeric($key) || empty($array) || !is_array($array)) {
            return [false, null];
        }

        $partsOfKey = explode('.', $key);
        $variants = [];
        $parts = [];

        do {
            $partial = implode('.', $partsOfKey);

            if (array_key_exists($partial, $array)) {
                if (!count($parts)) {
                    return [true, $array[$partial]];
                }

                $variants[implode('.', $parts)] = $array[$partial];
            }

            array_unshift($parts, array_pop($partsOfKey));
        } while (count($partsOfKey));

        foreach ($variants as $path => $branch) {
            list($has, $value) = $this->find($branch, $path);

            if ($has) {
                return [true, $value];
            }
        }

        return [false, null];
    }

}