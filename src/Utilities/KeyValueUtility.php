<?php namespace Lit\Nexus\Utilities;

use Lit\Nexus\Interfaces\ISingleValue;

class KeyValueUtility
{
    /**
     * @param ISingleValue $store
     * @param callable $compute
     * @return mixed
     */
    public static function getOrSet(ISingleValue $store, callable $compute)
    {
        if ($store->exists()) {
            return $store->get();
        }
        $value = call_user_func($compute);
        $store->set($value);

        return $value;
    }
}
