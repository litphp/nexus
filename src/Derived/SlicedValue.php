<?php namespace Lit\Nexus\Derived;

use Lit\Nexus\Interfaces\IKeyValue;
use Lit\Nexus\Interfaces\ISingleValue;
use Lit\Nexus\Traits\SingleValueTrait;

class SlicedValue implements ISingleValue
{
    use SingleValueTrait;
    /**
     * @var IKeyValue
     */
    private $keyValue;
    /**
     * @var string
     */
    private $key;

    /**
     * @param IKeyValue $keyValue
     * @param string $key
     */
    public function __construct(IKeyValue $keyValue, $key)
    {

        $this->keyValue = $keyValue;
        $this->key = $key;
    }

    /**
     * @param IKeyValue $keyValue
     * @param string $key
     * @return static
     */
    public static function slice(IKeyValue $keyValue, $key)
    {
        return new static($keyValue, $key);
    }

    /**
     * @return mixed
     */
    public function get()
    {
        return $this->keyValue->get($this->key);
    }

    /**
     * @return bool
     */
    public function exists()
    {
        return $this->keyValue->exists($this->key);
    }

    /**
     * @param mixed $value
     * @return void
     */
    public function set($value)
    {
        $this->keyValue->set($this->key, $value);
    }

    /**
     * @return void
     */
    public function delete()
    {
        $this->keyValue->delete($this->key);
    }
}
 