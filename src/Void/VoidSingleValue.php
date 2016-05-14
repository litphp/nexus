<?php namespace Lit\Nexus\Void;

use Lit\Nexus\Interfaces\ISingleValue;
use Lit\Nexus\Traits\SingleValueTrait;

/**
 * Class VoidSingleValue
 * @package Lit\Nexus\Void
 * @SuppressWarnings(PHPMD)
 */
class VoidSingleValue implements ISingleValue
{
    use SingleValueTrait;

    public function get()
    {
        throw new \RuntimeException('cannot get from void SingleValue');
    }

    public function exists()
    {
        return false;
    }

    public function set($value)
    {
        //noop
    }

    public function delete()
    {
        //noop
    }


}
