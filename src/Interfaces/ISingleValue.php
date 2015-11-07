<?php namespace Lit\Nexus\Interfaces;

interface ISingleValue extends IReadableSingleValue
{

    /**
     * @param mixed $value
     * @return void
     */
    public function set($value);

    /**
     * @return void
     */
    public function delete();
}
