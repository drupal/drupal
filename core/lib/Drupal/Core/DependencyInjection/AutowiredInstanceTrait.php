<?php

declare(strict_types=1);

namespace Drupal\Core\DependencyInjection;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\AutowiringFailedException;

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
    if (method_exists(static::class, '__construct')) {
      $constructor = new \ReflectionMethod(static::class, '__construct');
      foreach (array_slice($constructor->getParameters(), count($args)) as $parameter) {
        $service = ltrim((string) $parameter->getType(), '?');
        foreach ($parameter->getAttributes(Autowire::class) as $attribute) {
          $service = (string) $attribute->newInstance()->value;
        }

        if (!$container->has($service)) {
          if ($parameter->allowsNull()) {
            $args[] = NULL;
            continue;
          }
          throw new AutowiringFailedException($service, sprintf('Cannot autowire service "%s": argument "$%s" of method "%s::__construct()", you should configure its value explicitly.', $service, $parameter->getName(), static::class));
        }

        $args[] = $container->get($service);
      }
    }

    return new static(...$args);
  }

}
