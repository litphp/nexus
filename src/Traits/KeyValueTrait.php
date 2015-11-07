<?php namespace Lit\Nexus\Traits;

use Lit\Nexus\Derived\FrozenKeyValue;
use Lit\Nexus\Derived\PrefixKeyValue;
use Lit\Nexus\Derived\SlicedValue;
use Lit\Nexus\Interfaces\IKeyValue;

trait KeyValueTrait
{
    /**
     * @return FrozenKeyValue
     */
    public function freeze()
    {
        /**
         * @var IKeyValue $this
         */
        return FrozenKeyValue::wrap($this);
    }

    /**
     * @param $key
     * @return SlicedValue
     */
    public function slice($key)
    {
        /**
         * @var IKeyValue $this
         */
        return SlicedValue::slice($this, $key);
    }

    /**
     * @param $prefix
     * @return PrefixKeyValue
     */
    public function prefix($prefix)
    {
        /**
         * @var IKeyValue|self $this
         */
        return PrefixKeyValue::wrap($this, $prefix . $this->getPrefixDelimiter());
    }

    /**
     * @return string
     */
    protected function getPrefixDelimiter()
    {
        return '!!';
    }
}
