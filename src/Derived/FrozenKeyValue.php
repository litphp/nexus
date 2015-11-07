<?php namespace Lit\Nexus\Derived;

use Lit\Nexus\Interfaces\IKeyValue;
use Lit\Nexus\Interfaces\IReadableKeyValue;
use Lit\Nexus\Traits\KeyValueTrait;

class FrozenKeyValue implements IReadableKeyValue
{
    use KeyValueTrait;

    /**
     * @var IKeyValue
     */
    protected $keyValue;

    protected function __construct(IKeyValue $keyValue)
    {
        $this->keyValue = $keyValue;
    }

    /**
     * @param IKeyValue $keyValue
     * @return static
     */
    public static function wrap(IKeyValue $keyValue)
    {
        if ($keyValue instanceof static) {
            return $keyValue;
        }
        return new static($keyValue);
    }

    /**
     * @param array|\ArrayAccess $content
     * @return static
     */
    public static function wrapOffset($content)
    {
        return self::wrap(OffsetKeyValue::wrap($content));
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        return $this->keyValue->get($key);
    }

    /**
     * @param string $key
     * @return bool
     */
    public function exists($key)
    {
        return $this->keyValue->exists($key);
    }
}
 