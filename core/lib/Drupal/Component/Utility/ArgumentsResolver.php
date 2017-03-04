<?php

namespace Drupal\Component\Utility;

/**
 * Resolves the arguments to pass to a callable.
 */
class ArgumentsResolver implements ArgumentsResolverInterface {

  /**
   * An associative array of parameter names to scalar candidate values.
   *
   * @var array
   */
  protected $scalars;

  /**
   * An associative array of parameter names to object candidate values.
   *
   * @var array
   */
  protected $objects;

  /**
   * An array object candidates tried on every parameter regardless of name.
   *
   * @var array
   */
  protected $wildcards;

  /**
   * Constructs a new ArgumentsResolver.
   *
   * @param array $scalars
   *   An associative array of parameter names to scalar candidate values.
   * @param object[] $objects
   *   An associative array of parameter names to object candidate values.
   * @param object[] $wildcards
   *   An array object candidates tried on every parameter regardless of its
   *   name.
   */
  public function __construct(array $scalars, array $objects, array $wildcards) {
    $this->scalars = $scalars;
    $this->objects = $objects;
    $this->wildcards = $wildcards;
  }

  /**
   * {@inheritdoc}
   */
  public function getArguments(callable $callable) {
    $arguments = [];
    foreach ($this->getReflector($callable)->getParameters() as $parameter) {
      $arguments[] = $this->getArgument($parameter);
    }
    return $arguments;
  }

  /**
   * Gets the argument value for a parameter.
   *
   * @param \ReflectionParameter $parameter
   *   The parameter of a callable to get the value for.
   *
   * @return mixed
   *   The value of the requested parameter value.
   *
   * @throws \RuntimeException
   *   Thrown when there is a missing parameter.
   */
  protected function getArgument(\ReflectionParameter $parameter) {
    $parameter_type_hint = $parameter->getClass();
    $parameter_name = $parameter->getName();

    // If the argument exists and is NULL, return it, regardless of
    // parameter type hint.
    if (!isset($this->objects[$parameter_name]) && array_key_exists($parameter_name, $this->objects)) {
      return NULL;
    }

    if ($parameter_type_hint) {
      // If the argument exists and complies with the type hint, return it.
      if (isset($this->objects[$parameter_name]) && is_object($this->objects[$parameter_name]) && $parameter_type_hint->isInstance($this->objects[$parameter_name])) {
        return $this->objects[$parameter_name];
      }
      // Otherwise, resolve wildcard arguments by type matching.
      foreach ($this->wildcards as $wildcard) {
        if ($parameter_type_hint->isInstance($wildcard)) {
          return $wildcard;
        }
      }
    }
    elseif (isset($this->scalars[$parameter_name])) {
      return $this->scalars[$parameter_name];
    }

    // If the callable provides a default value, use it.
    if ($parameter->isDefaultValueAvailable()) {
      return $parameter->getDefaultValue();
    }

    // Can't resolve it: call a method that throws an exception or can be
    // overridden to do something else.
    return $this->handleUnresolvedArgument($parameter);
  }

  /**
   * Gets a reflector for the access check callable.
   *
   * The access checker may be either a procedural function (in which case the
   * callable is the function name) or a method (in which case the callable is
   * an array of the object and method name).
   *
   * @param callable $callable
   *   The callable (either a function or a method).
   *
   * @return \ReflectionFunctionAbstract
   *   The ReflectionMethod or ReflectionFunction to introspect the callable.
   */
  protected function getReflector(callable $callable) {
    return is_array($callable) ? new \ReflectionMethod($callable[0], $callable[1]) : new \ReflectionFunction($callable);
  }

  /**
   * Handles unresolved arguments for getArgument().
   *
   * Subclasses that override this method may return a default value
   * instead of throwing an exception.
   *
   * @throws \RuntimeException
   *   Thrown when there is a missing parameter.
   */
  protected function handleUnresolvedArgument(\ReflectionParameter $parameter) {
    $class = $parameter->getDeclaringClass();
    $function = $parameter->getDeclaringFunction();
    if ($class && !$function->isClosure()) {
      $function_name = $class->getName() . '::' . $function->getName();
    }
    else {
      $function_name = $function->getName();
    }
    throw new \RuntimeException(sprintf('Callable "%s" requires a value for the "$%s" argument.', $function_name, $parameter->getName()));
  }

}
