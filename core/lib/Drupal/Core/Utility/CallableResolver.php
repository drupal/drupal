<?php

namespace Drupal\Core\Utility;

use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Psr\Container\ContainerInterface;

/**
 * Resolves PHP callables.
 *
 * The callable resolver service aims to provide a standardized approach to how
 * callables are resolved and invoked from various subsystems. The callable
 * resolver will return or invoke a callable defined in any of the following
 * definition formats:
 *
 * - Static methods: @code '\FooClass::staticMethod' @endcode
 * - Non-static methods, instantiated with the class resolver:
 *   @code '\DependencyInjectedClass::method' @endcode
 * - Object calls: @code [$object, 'method'] @endcode
 * - Classes with an __invoke method: @code '\ClassWithInvoke' @endcode
 * - Service notation: @code 'some.service:method' @endcode
 */
class CallableResolver {

  /**
   * The container.
   *
   * @var \Psr\Container\ContainerInterface
   */
  protected $container;

  /**
   * The class resolver.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface
   */
  protected $classResolver;

  /**
   * Create an instance of the callable resolver.
   */
  public function __construct(ContainerInterface $container, ClassResolverInterface $classResolver) {
    $this->container = $container;
    $this->classResolver = $classResolver;
  }

  /**
   * Get a callable from a definition.
   *
   * @param mixed $definition
   *   A callable definition.
   *
   * @return callable
   *   A callable.
   *
   * @throws \InvalidArgumentException
   *   Thrown when no valid callable could be resolved from the definition.
   */
  public function getCallableFromDefinition($definition) {
    // Check if the definition is in the static method format, allow the
    // class resolver the opportunity to create a new instance of the class.
    if (is_string($definition) && substr_count($definition, '::') === 1) {
      list($class, $method) = explode('::', $definition);

      try {
        // If the method is defined as static, just use the static method format
        // definition as given.
        if ((new \ReflectionClass($class))->getMethod($method)->isStatic()) {
          return $definition;
        }
      }
      catch (\ReflectionException $e) {
        throw new \InvalidArgumentException(sprintf('The callable definition provided was invalid. Either class "%s" does not have a method "%s", or it is not callable.', $class, $method), 0, $e);
      }

      // If the method is not defined as static, use the class resolver to
      // create a new instance of the class.
      $resolved_class_callable = [$this->classResolver->getInstanceFromDefinition($class), $method];
      if (!is_callable($resolved_class_callable)) {
        throw new \InvalidArgumentException(sprintf('The callable definition provided was invalid. Either class "%s" does not have a method "%s", or it is not callable.', $class, $method));
      }
      return $resolved_class_callable;
    }

    // Handle the service notation syntax.
    if (is_string($definition) && substr_count($definition, ':') === 1) {
      list($service, $method) = explode(':', $definition);
      if (!$this->container->has($service)) {
        throw new \InvalidArgumentException(sprintf('The callable definition provided was invalid. No service found with name "%s".', $service));
      }
      if (!is_callable([$this->container->get($service), $method])) {
        throw new \InvalidArgumentException(sprintf('The callable definition provided was invalid. No method with name "%s" found on the "%s" service.', $method, $service));
      }
      return [$this->container->get($service), $method];
    }

    // If the definition is natively a callable, we can return it immediately.
    if (is_callable($definition)) {
      return $definition;
    }

    // Support using classes as callables if the __invoke method exists on the
    // class.
    if (method_exists($definition, '__invoke')) {
      return new $definition();
    }

    throw new \InvalidArgumentException('The callable definition provided was not a valid callable to service method.');
  }

  /**
   * Invoke a callable from a definition.
   *
   * Additional parameters may be passed in and will be passed as arguments to
   * the callable.
   *
   * @param mixed $definition
   *   A callable definition.
   * @param mixed $arguments
   *   Arguments passed to the invoked definition.
   *
   * @return mixed
   *   The return value of the passed callable definition.
   *
   * @throws \InvalidArgumentException
   *   Thrown when no valid callable could be resolved from the definition.
   */
  public function invokeFromDefinition($definition, ...$arguments) {
    return $this->getCallableFromDefinition($definition)(...$arguments);
  }

}
