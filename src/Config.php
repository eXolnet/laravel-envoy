<?php

namespace Exolnet\Envoy;

use ArrayAccess;
use Illuminate\Support\Arr;

abstract class Config implements ArrayAccess
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        return Arr::has($this->config, $key);
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return Arr::get($this->config, $key, $default);
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function set($key, $value)
    {
        Arr::set($this->config, $key, $value);

        return $this;
    }

    /**
     * @param array|string $keys
     * @return $this
     */
    public function forget($keys)
    {
        Arr::forget($this->config, $keys);

        return $this;
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        $this->forget($offset);
    }
}
