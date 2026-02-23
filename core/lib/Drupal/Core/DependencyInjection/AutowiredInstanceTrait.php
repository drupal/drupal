<?php

declare(strict_types=1);

namespace Drupal\Core\DependencyInjection;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\AutowiringFailedException;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * Defines a base trait for automatically wiring dependency arguments.
 */
trait AutowiredInstanceTrait {

  /**
   * Instantiates a new instance of the implementing class using autowiring.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container this instance should use.
   * @param mixed ...$args
   *   Any predefined arguments to pass to the constructor.
   *
   * @return static
   */
  public static function createInstanceAutowired(ContainerInterface $container, mixed ...$args): static {
    $reflection = new \ReflectionClass(static::class);

    if (method_exists(static::class, '__construct')) {
      $parameters = array_slice($reflection->getMethod('__construct')->getParameters(), count($args));
      $args = array_merge($args, self::getAutowireArguments($container, $parameters, '__construct'));
    }

    $instance = new static(...$args);

    foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
      if (!empty($method->getAttributes(Required::class))) {
        $method->invoke($instance, ...self::getAutowireArguments($container, $method->getParameters(), $method->getName()));
      }
    }

    return $instance;
  }

  /**
   * Resolves arguments for a method using autowiring.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   * @param \ReflectionParameter[] $parameters
   *   The parameters to resolve.
   * @param string $method_name
   *   The name of the method being called.
   *
   * @return array
   *   The resolved arguments.
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\AutowiringFailedException
   *   When a service cannot be resolved.
   */
  private static function getAutowireArguments(ContainerInterface $container, array $parameters, string $method_name): array {
    $args = [];
    foreach ($parameters as $parameter) {
      $service = ltrim((string) $parameter->getType(), '?');
      foreach ($parameter->getAttributes(Autowire::class) as $attribute) {
        $service = (string) $attribute->newInstance()->value;
      }

      if ($container->has($service)) {
        $args[] = $container->get($service);
        continue;
      }

      if ($parameter->allowsNull()) {
        $args[] = NULL;
        continue;
      }

      throw new AutowiringFailedException($service, sprintf('Cannot autowire service "%s": argument "$%s" of method "%s::%s()". Check that either the argument type is correct or the Autowire attribute is passed a valid identifier. Otherwise configure its value explicitly if possible.', $service, $parameter->getName(), static::class, $method_name));
    }
    return $args;
  }

}
