<?php namespace Lit\Nexus\Derived;

use Lit\Nexus\Interfaces\IKeyValue;
use Lit\Nexus\Traits\KeyValueTrait;

class PrefixKeyValue implements IKeyValue
{
    use KeyValueTrait;

    /**
     * @var IKeyValue
     */
    protected $store;
    /**
     * @var string
     */
    protected $prefix;

    protected function __construct(IKeyValue $store, $prefix)
    {
        if (empty($prefix)) {
            throw new \InvalidArgumentException;
        }

        $this->store = $store;
        $this->prefix = $prefix;
    }

    public static function wrap(IKeyValue $store, $prefix)
    {
        return new self($store, $prefix);
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set($key, $value)
    {
        $this->store->set($this->key($key), $value);
    }

    /**
     * @param string $key
     * @return void
     */
    public function delete($key)
    {
        $this->store->delete($this->key($key));
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        return $this->store->get($this->key($key));
    }

    /**
     * @param string $key
     * @return bool
     */
    public function exists($key)
    {
        return $this->store->exists($this->key($key));
    }

    protected function key($key)
    {
        return $this->prefix . $key;
    }
}
