<?php namespace Lit\Nexus\Interfaces;

interface IReadableSingleValue
{
    /**
     * @return mixed
     */
    public function get();

    /**
     * @return bool
     */
    public function exists();
}
