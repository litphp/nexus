<?php namespace Lit\Nexus\Traits;

trait KeyValueObjectAccessTrait
{
    public function __get($name)
    {
        return $this->get($name);
    }

    public function __set($name, $value)
    {
        $this->set($name, $value);
    }

    public function __isset($name)
    {
        return $this->exists($name);
    }

    public function __unset($name)
    {
        $this->delete($name);
    }
}
