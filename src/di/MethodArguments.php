<?php

namespace quieteroks\components\di;

use Yii;
use yii\base\Module;
use yii\base\InvalidConfigException;
use yii\di\Container;
use yii\di\NotInstantiableException;
use ReflectionMethod;
use ReflectionFunction;
use ReflectionParameter;
use ReflectionException;

class MethodArguments
{
    /**
     * @var Module
     */
    protected $application;
    /**
     * @var Container
     */
    protected $container;
    /**
     * @var ReflectionMethod|ReflectionFunction
     */
    protected $reflection;
    /**
     * @var array
     */
    protected $params = [];
    /**
     * @var array
     */
    protected $arguments;

    /**
     * Constructor for dependency method resolve.
     * If you not need save arguments names, you must use Yii Container.
     *
     * @param callable $callable
     * @param array $params
     * @param Module $application
     * @param Container $container
     * @throws InvalidConfigException
     * @see \yii\di\Container::resolveCallableDependencies
     */
    public function __construct(
        callable $callable,
        array $params = [],
        Module $application = null,
        Container $container = null
    )
    {
        $this->application = $application ?? Yii::$app;
        $this->container = $container ?? Yii::$container;
        $this->reflection = $this->normalizeCallReflection($callable);
        $this->params = $params;
    }

    /**
     * Return the arguments for method.
     *
     * @return array
     * @throws InvalidConfigException
     */
    public function getArguments(): array
    {
        if (is_null($this->arguments)) {
            $this->arguments = $this->resolveArguments();
        }
        return $this->arguments;
    }

    /**
     * Resolve the method dependencies named argument.
     *
     * @param array $params
     * @return array
     * @throws InvalidConfigException
     */
    public function resolveArguments(array $params = []): array
    {
        $args = [];
        $params = array_merge($this->params, $params);

        foreach ($this->reflection->getParameters() as $param) {
            if ($param->isVariadic()) {
                array_push($args, ...array_values($params));
                break;
            }
            $args[$param->name] = is_null($param->getClass())
                ? $this->resolvePrimitive($param, $params)
                : $this->resolveClass($param, $params);
        }

        return $args;
    }

    /**
     * Resolve a non-class hinted primitive dependency.
     *
     * @param ReflectionParameter $param
     * @param array $params
     * @return mixed
     * @throws InvalidConfigException
     */
    protected function resolvePrimitive(ReflectionParameter $param, array &$params = [])
    {
        $name = $param->name;
        if (array_key_exists($name, $params)) {
            if ($param->isArray()) {
                $arg = (array) $params[$name];
            } else {
                $arg = $params[$name];
            }
            unset($params[$name]);
            return $arg;
        }
        if ($param->isDefaultValueAvailable()) {
            return $param->getDefaultValue();
        }
        throw new InvalidConfigException(sprintf(
            'Missing required parameter "%s" when calling "%s".',
            $name, $this->reflection->getName()
        ));
    }

    /**
     * Resolve a class based method dependency.
     *
     * @param ReflectionParameter $param
     * @param array $params
     * @return mixed
     * @throws InvalidConfigException
     */
    protected function resolveClass(ReflectionParameter $param, array &$params = [])
    {
        $name = $param->name;
        $className = $param->getClass()->name;
        if (isset($params[$name]) && $params[$name] instanceof $className) {
            $args = $params[$name];
            unset($params[$name]);
            return $args;
        }
        if ($obj = $this->getArgumentFromApplication($name, $className)) {
            return $obj;
        }
        return $this->getArgumentFromContainer($param, $className);
    }

    /**
     * Try to get dependency from the module service locator by name.
     *
     * @param string $name
     * @param string $className
     * @return object|null
     * @throws InvalidConfigException
     */
    protected function getArgumentFromApplication(string $name, string $className)
    {
        if (isset($this->application) && $this->application->has($name)) {
            $obj = $this->application->get($name);
            if ($obj instanceof $className) {
                return $obj;
            }
        }
        return null;
    }

    /**
     * Try to make dependency via container or return default value.
     *
     * @param ReflectionParameter $param
     * @param string $className
     * @return object|null
     * @throws InvalidConfigException
     */
    protected function getArgumentFromContainer(ReflectionParameter $param, string $className)
    {
        try {
            return $this->container->get($className);
        } catch (NotInstantiableException $e) {
            if ($param->isDefaultValueAvailable()) {
                return $param->getDefaultValue();
            }
            throw new InvalidConfigException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Normalize reflection method object.
     *
     * @param callable $callable
     * @return ReflectionFunction|ReflectionMethod
     * @throws InvalidConfigException
     */
    protected function normalizeCallReflection(callable $callable)
    {
        try {
            if (is_array($callable)) {
                return new ReflectionMethod($callable[0], $callable[1]);
            }
            if (is_object($callable)) {
                return new ReflectionMethod($callable, '__invoke');
            }
            return new ReflectionFunction($callable);
        } catch (ReflectionException $e) {
            throw new InvalidConfigException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
