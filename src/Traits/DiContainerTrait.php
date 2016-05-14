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

        $class = new \ReflectionClass($className);
        $constructor = $class->getConstructor();

        $constructParams = $constructor
            ? array_map(
                function (\ReflectionParameter $parameter) use ($className, $extraParameters) {
                    $parameterName = $parameter->getName();
                    if (isset($extraParameters[$parameterName])) {
                        return $extraParameters[$parameterName];
                    }

                    $parameterClass = $parameter->getClass();
                    if ($parameterClass && isset($extraParameters[$parameterClass->getName()])) {
                        return $extraParameters[$parameterClass->getName()];
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

        /** @noinspection PhpParamsInspection */
        $this[$className] = is_callable($instance) && is_callable([$this, 'protect'])
            ? $this->protect($instance)
            : $instance;

        return $instance;
    }

    public function alias($className, $fieldName, array $extraParameters = [])
    {
        $this[$fieldName] = function () use ($className) {
            return $this->produce($className);
        };

        foreach ($extraParameters as $name => $value) {
            $this["$className:$name"] = $value;
        }

        return $this;
    }

    protected function produceParam($className, \ReflectionParameter $parameter)
    {
        $paramClass = $parameter->getClass();
        $paramName = $parameter->getName();
        $idx = $parameter->getPosition();

        if (isset($this["$className:$paramName"])) {
            return $this["$className:$paramName"];
        }

        if ($paramClass) {
            $paramClassName = $paramClass->getName();

            if (isset($this["$className:$paramClassName"])) {
                return $this["$className:$paramClassName"];
            }

            return $this->produce($paramClassName);
        }

        if (isset($this["$className:$idx"])) {
            return $this["$className:$idx"];
        }

        if ($parameter->isOptional()) {
            return $parameter->getDefaultValue();
        }

        if ($parameter->allowsNull()) {
            return null;
        }

        throw new \RuntimeException('failed to produce ' . $parameter);
    }
}
