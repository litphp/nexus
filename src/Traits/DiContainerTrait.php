<?php namespace Lit\Nexus\Traits;

/**
 * Class DiContainerTrait
 * @package Lit\Nexus\Traits
 *
 * @method protect($callable)
 */
trait DiContainerTrait
{
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
        $paramName = $parameter->name;
        if (isset($extraParameters[$paramName])) {
            return $extraParameters[$paramName];
        }
        if (isset($this["$className:$paramName"])) {
            return $this["$className:$paramName"];
        }

        try {
            $paramClass = $parameter->getClass();
            if (!empty($paramClass)) {
                $paramClassName = $paramClass->name;

                if (isset($extraParameters[$paramClassName])) {
                    return $extraParameters[$paramClassName];
                }
                if (isset($this["$className:$paramClassName"])) {
                    return $this["$className:$paramClassName"];
                }
            }
        } catch (\ReflectionException $e) {
            //ignore exception when $parameter is type hinting for interface
        }

        $idx = $parameter->getPosition();
        if (isset($extraParameters[$idx])) {
            return $extraParameters[$idx];
        }
        if (isset($this["$className:$idx"])) {
            return $this["$className:$idx"];
        }

        if (isset($paramClassName)) {
            return $this->produce($paramClassName);
        }

        if ($parameter->isOptional()) {
            return $parameter->getDefaultValue();
        }

        throw new \RuntimeException(sprintf('failed to produce %s for %s', $parameter, $className));
    }
}
