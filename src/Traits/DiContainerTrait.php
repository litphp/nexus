<?php namespace Lit\Nexus\Traits;

trait DiContainerTrait
{
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
     * @param $className
     * @param $extraParameters
     * @return object
     */
    public function instantiate($className, $extraParameters)
    {
        $class = new \ReflectionClass($className);
        $constructor = $class->getConstructor();

        $constructParams = $constructor
            ? array_map(
                function (\ReflectionParameter $parameter) use ($className, $extraParameters) {
                    $parameterName = $parameter->getName();
                    if (isset($extraParameters[$parameterName])) {
                        return $extraParameters[$parameterName];
                    }

                    try {
                        $parameterClass = $parameter->getClass();
                        if ($parameterClass && isset($extraParameters[$parameterClass->getName()])) {
                            return $extraParameters[$parameterClass->getName()];
                        }
                    } catch (\ReflectionException $e) {
                    }

                    $idx = $parameter->getPosition();
                    if (isset($extraParameters[$idx])) {
                        return $extraParameters[$idx];
                    }

                    return $this->produceParam($className, $parameter);
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

    protected function produceParam($className, \ReflectionParameter $parameter)
    {
        $paramName = $parameter->getName();
        if (isset($this["$className:$paramName"])) {
            return $this["$className:$paramName"];
        }


        try {
            $paramClass = $parameter->getClass();
            if (!empty($paramClass)) {
                $paramClassName = $paramClass->getName();

                if (isset($this["$className:$paramClassName"])) {
                    return $this["$className:$paramClassName"];
                }

                return $this->produce($paramClassName);
            }
        } catch (\ReflectionException $e) {
        }

        $idx = $parameter->getPosition();
        if (isset($this["$className:$idx"])) {
            return $this["$className:$idx"];
        }

        if ($parameter->isOptional()) {
            return $parameter->getDefaultValue();
        }

        throw new \RuntimeException(sprintf('failed to produce %s for %s', $parameter, $className));
    }
}
