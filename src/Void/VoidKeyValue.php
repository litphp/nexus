<?php namespace Lit\Nexus\Void;

use Lit\Nexus\Interfaces\IKeyValue;
use Lit\Nexus\Traits\KeyValueTrait;

/**
 * Class VoidKeyValue
 * @package Lit\Nexus\Void
 * @SuppressWarnings(PHPMD)
 */
class VoidKeyValue implements IKeyValue
{
    use KeyValueTrait;

    public function set($key, $value)
    {
        //noop
    }

    public function delete($key)
    {
        //noop
    }

    public function get($key)
    {
        throw new \RuntimeException('cannot get from void KeyValue');
    }

    public function exists($key)
    {
        return false;
    }
}
