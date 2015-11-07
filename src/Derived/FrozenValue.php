<?php namespace Lit\Nexus\Derived;

use Lit\Nexus\Interfaces\IReadableSingleValue;
use Lit\Nexus\Interfaces\ISingleValue;

class FrozenValue implements IReadableSingleValue
{
    /**
     * @var ISingleValue
     */
    protected $value;

    protected function __construct(ISingleValue $value)
    {
        $this->value = $value;
    }

    /**
     * @param ISingleValue $value
     * @return static
     */
    public static function wrap(ISingleValue $value)
    {
        if ($value instanceof static) {
            return $value;
        }
        return new static($value);
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function get()
    {
        return $this->value->get();
    }

    /**
     * @param string $key
     * @return bool
     */
    public function exists()
    {
        return $this->value->exists();
    }
}
 