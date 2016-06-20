<?php

namespace Drupal\Component\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\IntrospectableContainerInterface;
use Symfony\Component\DependencyInjection\ResettableContainerInterface;
use Symfony\Component\DependencyInjection\ScopeInterface;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;

/**
 * Provides a container optimized for Drupal's needs.
 *
 * This container implementation is compatible with the default Symfony
 * dependency injection container and similar to the Symfony ContainerBuilder
 * class, but optimized for speed.
 *
 * It is based on a PHP array container definition dumped as a
 * performance-optimized machine-readable format.
 *
 * The best way to initialize this container is to use a Container Builder,
 * compile it and then retrieve the definition via
 * \Drupal\Component\DependencyInjection\Dumper\OptimizedPhpArrayDumper::getArray().
 *
 * The retrieved array can be cached safely and then passed to this container
 * via the constructor.
 *
 * As the container is unfrozen by default, a second parameter can be passed to
 * the container to "freeze" the parameter bag.
 *
 * This container is different in behavior from the default Symfony container in
 * the following ways:
 *
 * - It only allows lowercase service and parameter names, though it does only
 *   enforce it via assertions for performance reasons.
 * - The following functions, that are not part of the interface, are explicitly
 *   not supported: getParameterBag(), isFrozen(), compile(),
 *   getAServiceWithAnIdByCamelCase().
 * - The function getServiceIds() was added as it has a use-case in core and
 *   contrib.
 * - Scopes are explicitly not allowed, because Symfony 2.8 has deprecated
 *   them and they will be removed in Symfony 3.0.
 * - Synchronized services are explicitly not supported, because Symfony 2.8 has
 *   deprecated them and they will be removed in Symfony 3.0.
 *
 * @ingroup container
 */
class Container implements IntrospectableContainerInterface, ResettableContainerInterface {

  /**
   * The parameters of the container.
   *
   * @var array
   */
  protected $parameters = array();

  /**
   * The aliases of the container.
   *
   * @var array
   */
  protected $aliases = array();

  /**
   * The service definitions of the container.
   *
   * @var array
   */
  protected $serviceDefinitions = array();

  /**
   * The instantiated services.
   *
   * @var array
   */
  protected $services = array();

  /**
   * The instantiated private services.
   *
   * @var array
   */
  protected $privateServices = array();

  /**
   * The currently loading services.
   *
   * @var array
   */
  protected $loading = array();

  /**
   * Whether the container parameters can still be changed.
   *
   * For testing purposes the container needs to be changed.
   *
   * @var bool
   */
  protected $frozen = TRUE;

  /**
   * Constructs a new Container instance.
   *
   * @param array $container_definition
   *   An array containing the following keys:
   *   - aliases: The aliases of the container.
   *   - parameters: The parameters of the container.
   *   - services: The service definitions of the container.
   *   - frozen: Whether the container definition came from a frozen
   *     container builder or not.
   *   - machine_format: Whether this container definition uses the optimized
   *     machine-readable container format.
   */
  public function __construct(array $container_definition = array()) {
    if (!empty($container_definition) && (!isset($container_definition['machine_format']) || $container_definition['machine_format'] !== TRUE)) {
      throw new InvalidArgumentException('The non-optimized format is not supported by this class. Use an optimized machine-readable format instead, e.g. as produced by \Drupal\Component\DependencyInjection\Dumper\OptimizedPhpArrayDumper.');
    }

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
  public function get($id, $invalid_behavior = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE) {
    if (isset($this->aliases[$id])) {
      $id = $this->aliases[$id];
    }

    // Re-use shared service instance if it exists.
    if (isset($this->services[$id]) || ($invalid_behavior === ContainerInterface::NULL_ON_INVALID_REFERENCE && array_key_exists($id, $this->services))) {
      return $this->services[$id];
    }

    if (isset($this->loading[$id])) {
      throw new ServiceCircularReferenceException($id, array_keys($this->loading));
    }

    $definition = isset($this->serviceDefinitions[$id]) ? $this->serviceDefinitions[$id] : NULL;

    if (!$definition && $invalid_behavior === ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE) {
      if (!$id) {
        throw new ServiceNotFoundException($id);
      }

      throw new ServiceNotFoundException($id, NULL, NULL, $this->getServiceAlternatives($id));
    }

    // In case something else than ContainerInterface::NULL_ON_INVALID_REFERENCE
    // is used, the actual wanted behavior is to re-try getting the service at a
    // later point.
    if (!$definition) {
      return;
    }

    // Definition is a keyed array, so [0] is only defined when it is a
    // serialized string.
    if (isset($definition[0])) {
      $definition = unserialize($definition);
    }

    // Now create the service.
    $this->loading[$id] = TRUE;

    try {
      $service = $this->createService($definition, $id);
    }
    catch (\Exception $e) {
      unset($this->loading[$id]);
      unset($this->services[$id]);

      if (ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE !== $invalid_behavior) {
        return;
      }

      throw $e;
    }

    unset($this->loading[$id]);

    return $service;
  }

  /**
   * {@inheritdoc}
   */
  public function reset() {
    if (!empty($this->scopedServices)) {
      throw new LogicException('Resetting the container is not allowed when a scope is active.');
    }

    $this->services = [];
  }

  /**
   * Creates a service from a service definition.
   *
   * @param array $definition
   *   The service definition to create a service from.
   * @param string $id
   *   The service identifier, necessary so it can be shared if its public.
   *
   * @return object
   *   The service described by the service definition.
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\RuntimeException
   *   Thrown when the service is a synthetic service.
   * @throws \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
   *   Thrown when the configurator callable in $definition['configurator'] is
   *   not actually a callable.
   * @throws \ReflectionException
   *   Thrown when the service class takes more than 10 parameters to construct,
   *   and cannot be instantiated.
   */
  protected function createService(array $definition, $id) {
    if (isset($definition['synthetic']) && $definition['synthetic'] === TRUE) {
      throw new RuntimeException(sprintf('You have requested a synthetic service ("%s"). The service container does not know how to construct this service. The service will need to be set before it is first used.', $id));
    }

    $arguments = array();
    if (isset($definition['arguments'])) {
      $arguments = $definition['arguments'];

      if ($arguments instanceof \stdClass) {
        $arguments = $this->resolveServicesAndParameters($arguments);
      }
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
      // ReflectionClass is noticeably slow.
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
          if ($arguments instanceof \stdClass) {
            $arguments = $this->resolveServicesAndParameters($arguments);
          }
        }
        call_user_func_array(array($service, $method), $arguments);
      }
    }

    if (isset($definition['properties'])) {
      if ($definition['properties'] instanceof \stdClass) {
        $definition['properties'] = $this->resolveServicesAndParameters($definition['properties']);
      }
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
  public function set($id, $service, $scope = ContainerInterface::SCOPE_CONTAINER) {
    if (!in_array($scope, array('container', 'request')) || ('request' === $scope && 'request' !== $id)) {
      @trigger_error('The concept of container scopes is deprecated since version 2.8 and will be removed in 3.0. Omit the third parameter.', E_USER_DEPRECATED);
    }

    $this->services[$id] = $service;
  }

  /**
   * {@inheritdoc}
   */
  public function has($id) {
    return isset($this->aliases[$id]) || isset($this->services[$id]) || isset($this->serviceDefinitions[$id]);
  }

  /**
   * {@inheritdoc}
   */
  public function getParameter($name) {
    if (!(isset($this->parameters[$name]) || array_key_exists($name, $this->parameters))) {
      if (!$name) {
        throw new ParameterNotFoundException($name);
      }

      throw new ParameterNotFoundException($name, NULL, NULL, NULL, $this->getParameterAlternatives($name));
    }

    return $this->parameters[$name];
  }

  /**
   * {@inheritdoc}
   */
  public function hasParameter($name) {
    return isset($this->parameters[$name]) || array_key_exists($name, $this->parameters);
  }

  /**
   * {@inheritdoc}
   */
  public function setParameter($name, $value) {
    if ($this->frozen) {
      throw new LogicException('Impossible to call set() on a frozen ParameterBag.');
    }

    $this->parameters[$name] = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function initialized($id) {
    if (isset($this->aliases[$id])) {
      $id = $this->aliases[$id];
    }

    return isset($this->services[$id]) || array_key_exists($id, $this->services);
  }

  /**
   * Resolves arguments that represent services or variables to the real values.
   *
   * @param array|\stdClass $arguments
   *   The arguments to resolve.
   *
   * @return array
   *   The resolved arguments.
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\RuntimeException
   *   If a parameter/service could not be resolved.
   * @throws \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
   *   If an unknown type is met while resolving parameters and services.
   */
  protected function resolveServicesAndParameters($arguments) {
    // Check if this collection needs to be resolved.
    if ($arguments instanceof \stdClass) {
      if ($arguments->type !== 'collection') {
        throw new InvalidArgumentException(sprintf('Undefined type "%s" while resolving parameters and services.', $arguments->type));
      }
      // In case there is nothing to resolve, we are done here.
      if (!$arguments->resolve) {
        return $arguments->value;
      }
      $arguments = $arguments->value;
    }

    // Process the arguments.
    foreach ($arguments as $key => $argument) {
      // For this machine-optimized format, only \stdClass arguments are
      // processed and resolved. All other values are kept as is.
      if ($argument instanceof \stdClass) {
        $type = $argument->type;

        // Check for parameter.
        if ($type == 'parameter') {
          $name = $argument->name;
          if (!isset($this->parameters[$name])) {
            $arguments[$key] = $this->getParameter($name);
            // This can never be reached as getParameter() throws an Exception,
            // because we already checked that the parameter is not set above.
          }

          // Update argument.
          $argument = $arguments[$key] = $this->parameters[$name];

          // In case there is not a machine readable value (e.g. a service)
          // behind this resolved parameter, continue.
          if (!($argument instanceof \stdClass)) {
            continue;
          }

          // Fall through.
          $type = $argument->type;
        }

        // Create a service.
        if ($type == 'service') {
          $id = $argument->id;

          // Does the service already exist?
          if (isset($this->aliases[$id])) {
            $id = $this->aliases[$id];
          }

          if (isset($this->services[$id])) {
            $arguments[$key] = $this->services[$id];
            continue;
          }

          // Return the service.
          $arguments[$key] = $this->get($id, $argument->invalidBehavior);

          continue;
        }
        // Create private service.
        elseif ($type == 'private_service') {
          $id = $argument->id;

          // Does the private service already exist.
          if (isset($this->privateServices[$id])) {
            $arguments[$key] = $this->privateServices[$id];
            continue;
          }

          // Create the private service.
          $arguments[$key] = $this->createService($argument->value, $id);
          if ($argument->shared) {
            $this->privateServices[$id] = $arguments[$key];
          }

          continue;
        }
        // Check for collection.
        elseif ($type == 'collection') {
          $value = $argument->value;

          // Does this collection need resolving?
          if ($argument->resolve) {
            $arguments[$key] = $this->resolveServicesAndParameters($value);
          }
          else {
            $arguments[$key] = $value;
          }

          continue;
        }

        if ($type !== NULL) {
          throw new InvalidArgumentException(sprintf('Undefined type "%s" while resolving parameters and services.', $type));
        }
      }
    }

    return $arguments;
  }

  /**
   * Provides alternatives for a given array and key.
   *
   * @param string $search_key
   *   The search key to get alternatives for.
   * @param array $keys
   *   The search space to search for alternatives in.
   *
   * @return string[]
   *   An array of strings with suitable alternatives.
   */
  protected function getAlternatives($search_key, array $keys) {
    $alternatives = array();
    foreach ($keys as $key) {
      $lev = levenshtein($search_key, $key);
      if ($lev <= strlen($search_key) / 3 || strpos($key, $search_key) !== FALSE) {
        $alternatives[] = $key;
      }
    }

    return $alternatives;
  }

  /**
   * Provides alternatives in case a service was not found.
   *
   * @param string $id
   *   The service to get alternatives for.
   *
   * @return string[]
   *   An array of strings with suitable alternatives.
   */
  protected function getServiceAlternatives($id) {
    $all_service_keys = array_unique(array_merge(array_keys($this->services), array_keys($this->serviceDefinitions)));
    return $this->getAlternatives($id, $all_service_keys);
  }

  /**
   * Provides alternatives in case a parameter was not found.
   *
   * @param string $name
   *   The parameter to get alternatives for.
   *
   * @return string[]
   *   An array of strings with suitable alternatives.
   */
  protected function getParameterAlternatives($name) {
    return $this->getAlternatives($name, array_keys($this->parameters));
  }


  /**
   * {@inheritdoc}
   */
  public function enterScope($name) {
    if ('request' !== $name) {
      @trigger_error('The ' . __METHOD__ . ' method is deprecated since version 2.8 and will be removed in 3.0.', E_USER_DEPRECATED);
    }

    throw new \BadMethodCallException(sprintf("'%s' is not supported by Drupal 8.", __FUNCTION__));
  }

  /**
   * {@inheritdoc}
   */
  public function leaveScope($name) {
    if ('request' !== $name) {
      @trigger_error('The ' . __METHOD__ . ' method is deprecated since version 2.8 and will be removed in 3.0.', E_USER_DEPRECATED);
    }

    throw new \BadMethodCallException(sprintf("'%s' is not supported by Drupal 8.", __FUNCTION__));
  }

  /**
   * {@inheritdoc}
   */
  public function addScope(ScopeInterface $scope) {

    $name = $scope->getName();
    if ('request' !== $name) {
      @trigger_error('The ' . __METHOD__ . ' method is deprecated since version 2.8 and will be removed in 3.0.', E_USER_DEPRECATED);
    }
    throw new \BadMethodCallException(sprintf("'%s' is not supported by Drupal 8.", __FUNCTION__));
  }

  /**
   * {@inheritdoc}
   */
  public function hasScope($name) {
    if ('request' !== $name) {
      @trigger_error('The ' . __METHOD__ . ' method is deprecated since version 2.8 and will be removed in 3.0.', E_USER_DEPRECATED);
    }

    throw new \BadMethodCallException(sprintf("'%s' is not supported by Drupal 8.", __FUNCTION__));
  }

  /**
   * {@inheritdoc}
   */
  public function isScopeActive($name) {
    @trigger_error('The ' . __METHOD__ . ' method is deprecated since version 2.8 and will be removed in 3.0.', E_USER_DEPRECATED);

    throw new \BadMethodCallException(sprintf("'%s' is not supported by Drupal 8.", __FUNCTION__));
  }

  /**
   * Gets all defined service IDs.
   *
   * @return array
   *   An array of all defined service IDs.
   */
  public function getServiceIds() {
    return array_keys($this->serviceDefinitions + $this->services);
  }

  /**
   * Ensure that cloning doesn't work.
   */
  private function __clone()
  {
  }

}
