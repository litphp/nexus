<?php namespace Lit\Nexus\Traits;

use Lit\Nexus\Interfaces\ISingleValue;

trait EmbedSingleValueTrait
{
    /**
     * @var ISingleValue
     */
    protected $innerSingleValue;

    public function get()
    {
        return $this->innerSingleValue->get();
    }

    public function delete()
    {
        $this->innerSingleValue->delete();
    }

    public function exists()
    {
        return $this->innerSingleValue->exists();
    }

    public function set($value)
    {
        $this->innerSingleValue->set($value);
    }

    /**
     * @return ISingleValue
     */
    public function getInnerSingleValue()
    {
        return $this->innerSingleValue;
    }
}
