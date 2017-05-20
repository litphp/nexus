<?php namespace Lit\Nexus\Traits;

use Lit\Nexus\Exceptions\DiException;
use Lit\Nexus\Interfaces\IPropertyInjection;

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
    public static $diContainerPrefix = __CLASS__;

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
    public function instantiate($className, array $extraParameters = [])
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
        if($instance instanceof IPropertyInjection) {
            $this->injectProperty($className, $extraParameters, $instance);
        }

        return $instance;
    }

    public function produceProxy($key)
    {
        return function () use ($key) {
            return $this->produce($key);
        };
    }

    protected function injectProperty($className, $extra, IPropertyInjection $target)
    {
        foreach($target::getInjectedProperties() as $name => $value) {
            $value = (array) $value;
            if(isset($value['keys'])){
                $keys = $value['keys'];
                $dClassName = isset($value['class']) ? $value['class'] : null;
            } else {
                $keys = $value;
                $dClassName = count($value) == 1 && class_exists($value[0]) ? $value[0] : null;
            }
            $keys[] = $name;

            $value = $this->produceDependency($className, $keys, $dClassName, $extra);
            $prop = new \ReflectionProperty(get_class($target), $name);
            $prop->setAccessible(true);
            $prop->setValue($target, $value);
        }
    }

    protected function produceParam($className, \ReflectionParameter $parameter, array $extraParameters)
    {
        list($keys, $paramClassName) = $this->parseParameter($parameter);

        try {
            return $this->produceDependency($className, $keys, $paramClassName, $extraParameters);
        } catch (DiException $e) {
            if($e->getCode() === DiException::CODE_DEPENDENCY_FAULT && $parameter->isOptional()) {
                return $parameter->getDefaultValue();
            }

            throw new DiException(
                sprintf('failed to produce constructor parameter "%s" for %s', $parameter->getName(), $className),
                DiException::CODE_DEPENDENCY_FAULT,
                $e
            );
        }
    }

    protected function produceDependency($className, array $keys, $dependencyClassName = null, array $extra = [])
    {
        do {
            foreach ($keys as $key) {
                if (isset($extra[$key])) {
                    return $this->populateDependency($extra[$key]);
                }
                if (isset($this["$className::"]) && isset($this["$className::"][$key])) {
                    return $this->populateDependency($this["$className::"][$key]);
                }
                if (isset($this["$className:$key"])) {
                    return $this->populateDependency($this["$className:$key"]);
                }
            }
        } while ($className = get_parent_class($className));

        if($dependencyClassName && isset($this[$dependencyClassName])) {
            return $this[$dependencyClassName];
        }

        if (isset($dependencyClassName) && class_exists($dependencyClassName)) {
            return $this->produce($dependencyClassName);
        }

        throw new DiException('failed to produce dependency', DiException::CODE_DEPENDENCY_FAULT);
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

    protected function populateDependency($stub)
    {
        if (!is_object($stub) || !method_exists($stub, '__invoke')) {
            return $stub;
        }

        $key = static::$diContainerPrefix . ':dependency:' . spl_object_hash($stub);
        if (isset($this[$key])) {
            return $this[$key];
        }

        //so we respect user's ->protect / ->factory call
        $this[$key] = $stub;
        return $this[$key];
    }
}
