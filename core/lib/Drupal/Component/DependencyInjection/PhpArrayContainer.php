<?php

namespace Drupal\Component\DependencyInjection;

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
  public function __construct(array $container_definition = array()) {
    if (isset($container_definition['machine_format']) && $container_definition['machine_format'] === TRUE) {
      throw new InvalidArgumentException('The machine-optimized format is not supported by this class. Use a human-readable format instead, e.g. as produced by \Drupal\Component\DependencyInjection\Dumper\PhpArrayDumper.');
    }

    // Do not call the parent's constructor as it would bail on the
    // machine-optimized format.
    $this->aliases = isset($container_definition['aliases']) ? $container_definition['aliases'] : array();
    $this->parameters = isset($container_definition['parameters']) ? $container_definition['parameters'] : array();
    $this->serviceDefinitions = isset($container_definition['services']) ? $container_definition['services'] : array();
    $this->frozen = isset($container_definition['frozen']) ? $container_definition['frozen'] : FALSE;

    // Register the service_container with itself.
    $this->services['service_container'] = $this;
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

    $arguments = array();
    if (isset($definition['arguments'])) {
      $arguments = $this->resolveServicesAndParameters($definition['arguments']);
    }

    if (isset($definition['file'])) {
      $file = $this->frozen ? $definition['file'] : current($this->resolveServicesAndParameters(array($definition['file'])));
      require_once $file;
    }

    if (isset($definition['factory'])) {
      $factory = $definition['factory'];
      if (is_array($factory)) {
        $factory = $this->resolveServicesAndParameters(array($factory[0], $factory[1]));
      }
      elseif (!is_string($factory)) {
        throw new RuntimeException(sprintf('Cannot create service "%s" because of invalid factory', $id));
      }

      $service = call_user_func_array($factory, $arguments);
    }
    else {
      $class = $this->frozen ? $definition['class'] : current($this->resolveServicesAndParameters(array($definition['class'])));
      $length = isset($definition['arguments_count']) ? $definition['arguments_count'] : count($arguments);

      // Optimize class instantiation for services with up to 10 parameters as
      // reflection is noticeably slow.
      switch ($length) {
        case 0:
          $service = new $class();
          break;

        case 1:
          $service = new $class($arguments[0]);
          break;

        case 2:
          $service = new $class($arguments[0], $arguments[1]);
          break;

        case 3:
          $service = new $class($arguments[0], $arguments[1], $arguments[2]);
          break;

        case 4:
          $service = new $class($arguments[0], $arguments[1], $arguments[2], $arguments[3]);
          break;

        case 5:
          $service = new $class($arguments[0], $arguments[1], $arguments[2], $arguments[3], $arguments[4]);
          break;

        case 6:
          $service = new $class($arguments[0], $arguments[1], $arguments[2], $arguments[3], $arguments[4], $arguments[5]);
          break;

        case 7:
          $service = new $class($arguments[0], $arguments[1], $arguments[2], $arguments[3], $arguments[4], $arguments[5], $arguments[6]);
          break;

        case 8:
          $service = new $class($arguments[0], $arguments[1], $arguments[2], $arguments[3], $arguments[4], $arguments[5], $arguments[6], $arguments[7]);
          break;

        case 9:
          $service = new $class($arguments[0], $arguments[1], $arguments[2], $arguments[3], $arguments[4], $arguments[5], $arguments[6], $arguments[7], $arguments[8]);
          break;

        case 10:
          $service = new $class($arguments[0], $arguments[1], $arguments[2], $arguments[3], $arguments[4], $arguments[5], $arguments[6], $arguments[7], $arguments[8], $arguments[9]);
          break;

        default:
          $r = new \ReflectionClass($class);
          $service = $r->newInstanceArgs($arguments);
          break;
      }
    }

    // Share the service if it is public.
    if (!isset($definition['public']) || $definition['public'] !== FALSE) {
      // Forward compatibility fix for Symfony 2.8 update.
      if (!isset($definition['shared']) || $definition['shared'] !== FALSE) {
        $this->services[$id] = $service;
      }
    }

    if (isset($definition['calls'])) {
      foreach ($definition['calls'] as $call) {
        $method = $call[0];
        $arguments = array();
        if (!empty($call[1])) {
          $arguments = $call[1];
          $arguments = $this->resolveServicesAndParameters($arguments);
        }
        call_user_func_array(array($service, $method), $arguments);
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
        // @see \Drupal\Component\DependecyInjection\Dumper\OptimizedPhpArrayDumper::getPrivateServiceCall
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
