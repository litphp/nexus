<?php namespace Lit\Nexus\Traits;

use Lit\Nexus\Derived\FrozenValue;
use Lit\Nexus\Interfaces\ISingleValue;

trait SingleValueTrait
{
    public function freeze()
    {
        /**
         * @var ISingleValue $this ;
         */
        return FrozenValue::wrap($this);
    }
}
