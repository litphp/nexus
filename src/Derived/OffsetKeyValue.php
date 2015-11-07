<?php namespace Lit\Nexus\Derived;

use Lit\Nexus\Interfaces\IKeyValue;
use Lit\Nexus\Traits\KeyValueTrait;

class OffsetKeyValue implements IKeyValue, \ArrayAccess
{
    use KeyValueTrait;

    protected $content;

    protected function __construct($content)
    {
        $this->content = $content;
    }

    /**
     * @param array|\ArrayAccess $content
     * @return static
     */
    public static function wrap($content)
    {
        if ($content instanceof static) {
            return $content;
        }

        if ($content instanceof \ArrayAccess || is_array($content)) {
            return new static($content);
        }

        throw new \InvalidArgumentException;
    }


    /**
     * @inheritdoc
     */
    public function offsetExists($offset)
    {
        return isset($this->content[$offset]);
    }

    /**
     * @inheritdoc
     */
    public function offsetGet($offset)
    {
        return $this->content[$offset];
    }

    /**
     * @inheritdoc
     */
    public function offsetSet($offset, $value)
    {
        $this->content[$offset] = $value;
    }

    /**
     * @inheritdoc
     */
    public function offsetUnset($offset)
    {
        unset($this->content[$offset]);
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set($key, $value)
    {
        $this->content[$key] = $value;
    }

    /**
     * @param string $key
     * @return void
     */
    public function delete($key)
    {
        unset($this->content[$key]);
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        return $this->content[$key];
    }

    /**
     * @param string $key
     * @return bool
     */
    public function exists($key)
    {
        return isset($this->content[$key]);
    }

    /**
     * @return array|\ArrayAccess
     */
    public function getContent()
    {
        return $this->content;
    }
}
