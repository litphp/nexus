<?php namespace Lit\Nexus\Traits;

/**
 * Class DiContainerTrait
 *
 * a pimple extension
 *
 * @package Lit\Nexus\Traits
 *
 * @method protect($callable)
 */
trait DiContainerTrait
{
    public static $diContainerPrefix = __CLASS__ . ':di:';

    /**
     * @param string $className
     * @param array $extraParameters
     * @return object of $className«
     */
    public function produce($className, $extraParameters = [])
    {
        if (isset($this[$className])) {
            return $this[$className];
        }

        if (!class_exists($className)) {
            throw new \RuntimeException("$className not found");
        }

        $instance = $this->instantiate($className, $extraParameters);

        /** @noinspection PhpParamsInspection */
        $this[$className] = is_callable($instance) && is_callable([$this, 'protect'])
            ? $this->protect($instance)
            : $instance;

        return $instance;
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @param array $extraParameters
     * @return $this
     */
    public function alias($className, $fieldName, array $extraParameters = [])
    {
        $this[$fieldName] = function () use ($className) {
            return $this->produce($className);
        };

        return $this->provideParameter($className, $extraParameters);
    }

    /**
     * @param string $className
     * @param array $extraParameters
     * @return $this
     */
    public function provideParameter($className, array $extraParameters)
    {
        foreach ($extraParameters as $name => $value) {
            if (isset($this["$className:$name"])) {
                throw new \RuntimeException("cannot override $className:$name");
            }

            $this["$className:$name"] = $value;
        }

        return $this;
    }

    /**
     * @param string $className
     * @param array $extraParameters
     * @return object
     */
    public function instantiate($className, array $extraParameters)
    {
        $class = new \ReflectionClass($className);
        $constructor = $class->getConstructor();

        $constructParams = $constructor
            ? array_map(
                function (\ReflectionParameter $parameter) use ($className, $extraParameters) {
                    return $this->produceParam($className, $parameter, $extraParameters);
                },
                $constructParams = $constructor->getParameters()
            )
            : [];

        $instance = $class->newInstanceArgs($constructParams);
        return $instance;
    }

    public function produceProxy($key)
    {
        return function () use ($key) {
            return $this->produce($key);
        };
    }

    protected function produceParam($className, \ReflectionParameter $parameter, array $extraParameters)
    {
        list($keys, $paramClassName) = $this->parseParameter($parameter);

        foreach ($keys as $key) {
            if (isset($extraParameters[$key])) {
                return $this->populateParameter($extraParameters[$key]);
            }
            if (isset($this["$className:$key"])) {
                return $this->populateParameter($this["$className:$key"]);
            }
        }

        if (!empty($paramClassName)) {
            return $this->produce($paramClassName);
        }

        if ($parameter->isOptional()) {
            return $parameter->getDefaultValue();
        }

        throw new \RuntimeException(sprintf('failed to produce %s for %s', $parameter, $className));
    }

    /**
     * @param \ReflectionParameter $parameter
     * @return array
     */
    protected function parseParameter(\ReflectionParameter $parameter)
    {
        $paramClassName = null;
        $keys = [$parameter->name];

        try {
            $paramClass = $parameter->getClass();
            if (!empty($paramClass)) {
                $keys[] = $paramClassName = $paramClass->name;
            }
        } catch (\ReflectionException $e) {
            //ignore exception when $parameter is type hinting for interface
        }

        $keys[] = $parameter->getPosition();
        return [$keys, $paramClassName];
    }

    protected function populateParameter($stub)
    {
        if (!is_object($stub) || !method_exists($stub, '__invoke')) {
            return $stub;
        }

        $key = static::$diContainerPrefix . spl_object_hash($stub);
        if (isset($this[$key])) {
            return $this[$key];
        }

        //so we respect user's ->protect / ->factory call
        $this[$key] = $stub;
        return $this[$key];
    }
}
