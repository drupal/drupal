<?php

namespace Drupal\Component\DependencyInjection;

use Symfony\Component\DependencyInjection\Argument\RewindableGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;

/**
 * Provides a container optimized for Drupal's needs.
 *
 * This container implementation is compatible with the default Symfony
 * dependency injection container and similar to the Symfony ContainerBuilder
 * class, but optimized for speed.
 *
 * It is based on a human-readable PHP array container definition with a
 * structure very similar to the YAML container definition.
 *
 * @see \Drupal\Component\DependencyInjection\Container
 * @see \Drupal\Component\DependencyInjection\Dumper\PhpArrayDumper
 * @see \Drupal\Component\DependencyInjection\DependencySerializationTrait
 *
 * @ingroup container
 */
class PhpArrayContainer extends Container {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $container_definition = []) {
    if (isset($container_definition['machine_format']) && $container_definition['machine_format'] === TRUE) {
      throw new InvalidArgumentException('The machine-optimized format is not supported by this class. Use a human-readable format instead, e.g. as produced by \Drupal\Component\DependencyInjection\Dumper\PhpArrayDumper.');
    }

    // Do not call the parent's constructor as it would bail on the
    // machine-optimized format.
    $this->aliases = $container_definition['aliases'] ?? [];
    $this->parameters = $container_definition['parameters'] ?? [];
    $this->serviceDefinitions = $container_definition['services'] ?? [];
    $this->frozen = $container_definition['frozen'] ?? FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function createService(array $definition, $id) {
    // This method is a verbatim copy of
    // \Drupal\Component\DependencyInjection\Container::createService
    // except for the following difference:
    // - There are no instanceof checks on \stdClass, which are used in the
    //   parent class to avoid resolving services and parameters when it is
    //   known from dumping that there is nothing to resolve.
    if (isset($definition['synthetic']) && $definition['synthetic'] === TRUE) {
      throw new RuntimeException(sprintf('You have requested a synthetic service ("%s"). The service container does not know how to construct this service. The service will need to be set before it is first used.', $id));
    }

    $arguments = [];
    if (isset($definition['arguments'])) {
      $arguments = $this->resolveServicesAndParameters($definition['arguments']);
    }

    if (isset($definition['file'])) {
      $file = $this->frozen ? $definition['file'] : current($this->resolveServicesAndParameters([$definition['file']]));
      require_once $file;
    }

    if (isset($definition['factory'])) {
      $factory = $definition['factory'];
      if (is_array($factory)) {
        $factory = $this->resolveServicesAndParameters([$factory[0], $factory[1]]);
      }
      elseif (!is_string($factory)) {
        throw new RuntimeException(sprintf('Cannot create service "%s" because of invalid factory', $id));
      }

      $service = call_user_func_array($factory, $arguments);
    }
    else {
      $class = $this->frozen ? $definition['class'] : current($this->resolveServicesAndParameters([$definition['class']]));
      $service = new $class(...$arguments);
    }

    if (!isset($definition['shared']) || $definition['shared'] !== FALSE) {
      $this->services[$id] = $service;
    }

    if (isset($definition['calls'])) {
      foreach ($definition['calls'] as $call) {
        $method = $call[0];
        $arguments = [];
        if (!empty($call[1])) {
          $arguments = $call[1];
          $arguments = $this->resolveServicesAndParameters($arguments);
        }
        call_user_func_array([$service, $method], $arguments);
      }
    }

    if (isset($definition['properties'])) {
      $definition['properties'] = $this->resolveServicesAndParameters($definition['properties']);
      foreach ($definition['properties'] as $key => $value) {
        $service->{$key} = $value;
      }
    }

    if (isset($definition['configurator'])) {
      $callable = $definition['configurator'];
      if (is_array($callable)) {
        $callable = $this->resolveServicesAndParameters($callable);
      }

      if (!is_callable($callable)) {
        throw new InvalidArgumentException(sprintf('The configurator for class "%s" is not a callable.', get_class($service)));
      }

      call_user_func($callable, $service);
    }

    return $service;
  }

  /**
   * {@inheritdoc}
   */
  protected function resolveServicesAndParameters($arguments) {
    // This method is different from the parent method only for the following
    // cases:
    // - A service is denoted by '@service' and not by a \stdClass object.
    // - A parameter is denoted by '%parameter%' and not by a \stdClass object.
    // - The depth of the tree representing the arguments is not known in
    //   advance, so it needs to be fully traversed recursively.
    foreach ($arguments as $key => $argument) {
      if ($argument instanceof \stdClass) {
        $type = $argument->type;

        // Private services are a special flavor: In case a private service is
        // only used by one other service, the ContainerBuilder uses a
        // Definition object as an argument, which does not have an ID set.
        // Therefore the format uses a \stdClass object to store the definition
        // and to be able to create the service on the fly.
        //
        // Note: When constructing a private service by hand, 'id' must be set.
        //
        // The PhpArrayDumper just uses the hash of the private service
        // definition to generate a unique ID.
        //
        // @see \Drupal\Component\DependencyInjection\Dumper\OptimizedPhpArrayDumper::getPrivateServiceCall
        if ($type == 'private_service') {
          $id = $argument->id;

          // Check if the private service already exists - in case it is shared.
          if (!empty($argument->shared) && isset($this->privateServices[$id])) {
            $arguments[$key] = $this->privateServices[$id];
            continue;
          }

          // Create a private service from a service definition.
          $arguments[$key] = $this->createService($argument->value, $id);
          if (!empty($argument->shared)) {
            $this->privateServices[$id] = $arguments[$key];
          }

          continue;
        }
        elseif ($type == 'service_closure') {
          $arguments[$key] = function () use ($argument) {
            return $this->get($argument->id, $argument->invalidBehavior);
          };

          continue;
        }
        elseif ($type == 'raw') {
          $arguments[$key] = $argument->value;

          continue;
        }
        elseif ($type == 'iterator') {
          $services = $argument->value;
          $arguments[$key] = new RewindableGenerator(function () use ($services) {
            foreach ($services as $key => $service) {
              yield $key => $this->resolveServicesAndParameters([$service])[0];
            }
          }, count($services));
          continue;
        }

        if ($type !== NULL) {
          throw new InvalidArgumentException("Undefined type '$type' while resolving parameters and services.");
        }
      }

      if (is_array($argument)) {
        $arguments[$key] = $this->resolveServicesAndParameters($argument);
        continue;
      }

      if (!is_string($argument)) {
        continue;
      }

      // Resolve parameters.
      if ($argument[0] === '%') {
        $name = substr($argument, 1, -1);
        if (!isset($this->parameters[$name])) {
          $arguments[$key] = $this->getParameter($name);
          // This can never be reached as getParameter() throws an Exception,
          // because we already checked that the parameter is not set above.
        }
        $argument = $this->parameters[$name];
        $arguments[$key] = $argument;
      }

      // Resolve services.
      if ($argument[0] === '@') {
        $id = substr($argument, 1);
        $invalid_behavior = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE;
        if ($id[0] === '?') {
          $id = substr($id, 1);
          $invalid_behavior = ContainerInterface::NULL_ON_INVALID_REFERENCE;
        }
        if (isset($this->services[$id])) {
          $arguments[$key] = $this->services[$id];
        }
        else {
          $arguments[$key] = $this->get($id, $invalid_behavior);
        }
      }
    }

    return $arguments;
  }

}
