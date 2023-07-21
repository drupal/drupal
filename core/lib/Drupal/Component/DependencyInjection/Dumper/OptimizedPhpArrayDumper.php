<?php

namespace Drupal\Component\DependencyInjection\Dumper;

use Drupal\Component\Utility\Crypt;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;
use Symfony\Component\DependencyInjection\Dumper\Dumper;
use Symfony\Component\ExpressionLanguage\Expression;

/**
 * OptimizedPhpArrayDumper dumps a service container as a serialized PHP array.
 *
 * The format of this dumper is very similar to the internal structure of the
 * ContainerBuilder, but based on PHP arrays and \stdClass objects instead of
 * rich value objects for performance reasons.
 *
 * By removing the abstraction and optimizing some cases like deep collections,
 * fewer classes need to be loaded, fewer function calls need to be executed and
 * fewer run time checks need to be made.
 *
 * In addition to that, this container dumper treats private services as
 * strictly private with their own private services storage, whereas in the
 * Symfony service container builder and PHP dumper, shared private services can
 * still be retrieved via get() from the container.
 *
 * It is machine-optimized, for a human-readable version based on this one see
 * \Drupal\Component\DependencyInjection\Dumper\PhpArrayDumper.
 *
 * @see \Drupal\Component\DependencyInjection\Container
 */
class OptimizedPhpArrayDumper extends Dumper {

  /**
   * Whether to serialize service definitions or not.
   *
   * Service definitions are serialized by default to avoid having to
   * unserialize the whole container on loading time, which improves early
   * bootstrap performance for e.g. the page cache.
   *
   * @var bool
   */
  protected $serialize = TRUE;

  /**
   * A list of container aliases.
   *
   * @var array
   */
  protected $aliases;

  /**
   * {@inheritdoc}
   */
  public function dump(array $options = []): string|array {
    return serialize($this->getArray());
  }

  /**
   * Gets the service container definition as a PHP array.
   *
   * @return array
   *   A PHP array representation of the service container.
   */
  public function getArray() {
    $definition = [];
    // Warm aliases first.
    $this->aliases = $this->getAliases();
    $definition['aliases'] = $this->aliases;
    $definition['parameters'] = $this->getParameters();
    $definition['services'] = $this->getServiceDefinitions();
    $definition['frozen'] = $this->container->isCompiled();
    $definition['machine_format'] = $this->supportsMachineFormat();
    return $definition;
  }

  /**
   * Gets the aliases as a PHP array.
   *
   * @return array
   *   The aliases.
   */
  protected function getAliases() {
    $alias_definitions = [];

    $aliases = $this->container->getAliases();
    foreach ($aliases as $alias => $id) {
      $id = (string) $id;
      while (isset($aliases[$id])) {
        $id = (string) $aliases[$id];
      }
      $alias_definitions[$alias] = $id;
    }

    return $alias_definitions;
  }

  /**
   * Gets parameters of the container as a PHP array.
   *
   * @return array
   *   The escaped and prepared parameters of the container.
   */
  protected function getParameters() {
    if (!$this->container->getParameterBag()->all()) {
      return [];
    }

    $parameters = $this->container->getParameterBag()->all();
    $is_compiled = $this->container->isCompiled();
    return $this->prepareParameters($parameters, $is_compiled);
  }

  /**
   * Gets services of the container as a PHP array.
   *
   * @return array
   *   The service definitions.
   */
  protected function getServiceDefinitions() {
    if (!$this->container->getDefinitions()) {
      return [];
    }

    $services = [];
    foreach ($this->container->getDefinitions() as $id => $definition) {
      // Only store public service definitions, references to shared private
      // services are handled in ::getReferenceCall().
      if ($definition->isPublic()) {
        $service_definition = $this->getServiceDefinition($definition);
        $services[$id] = $this->serialize ? serialize($service_definition) : $service_definition;
      }
    }

    return $services;
  }

  /**
   * Prepares parameters for the PHP array dumping.
   *
   * @param array $parameters
   *   An array of parameters.
   * @param bool $escape
   *   Whether keys with '%' should be escaped or not.
   *
   * @return array
   *   An array of prepared parameters.
   */
  protected function prepareParameters(array $parameters, $escape = TRUE) {
    $filtered = [];
    foreach ($parameters as $key => $value) {
      if (is_array($value)) {
        $value = $this->prepareParameters($value, $escape);
      }

      $filtered[$key] = $value;
    }

    return $escape ? $this->escape($filtered) : $filtered;
  }

  /**
   * Escapes parameters.
   *
   * @param array $parameters
   *   The parameters to escape for '%' characters.
   *
   * @return array
   *   The escaped parameters.
   */
  protected function escape(array $parameters) {
    $args = [];

    foreach ($parameters as $key => $value) {
      if (is_array($value)) {
        $args[$key] = $this->escape($value);
      }
      elseif (is_string($value)) {
        $args[$key] = str_replace('%', '%%', $value);
      }
      else {
        $args[$key] = $value;
      }
    }

    return $args;
  }

  /**
   * Gets a service definition as PHP array.
   *
   * @param \Symfony\Component\DependencyInjection\Definition $definition
   *   The definition to process.
   *
   * @return array
   *   The service definition as PHP array.
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
   *   Thrown when the definition is marked as decorated, or with an explicit
   *   scope different from SCOPE_CONTAINER and SCOPE_PROTOTYPE.
   */
  protected function getServiceDefinition(Definition $definition) {
    $service = [];
    if ($definition->getClass()) {
      $service['class'] = $definition->getClass();
    }

    if (!$definition->isPublic()) {
      $service['public'] = FALSE;
    }

    if ($definition->getFile()) {
      $service['file'] = $definition->getFile();
    }

    if ($definition->isSynthetic()) {
      $service['synthetic'] = TRUE;
    }

    if ($definition->isLazy()) {
      $service['lazy'] = TRUE;
    }

    if ($definition->getArguments()) {
      $arguments = $definition->getArguments();
      $service['arguments'] = $this->dumpCollection($arguments);
      $service['arguments_count'] = count($arguments);
    }
    else {
      $service['arguments_count'] = 0;
    }

    if ($definition->getProperties()) {
      $service['properties'] = $this->dumpCollection($definition->getProperties());
    }

    if ($definition->getMethodCalls()) {
      $service['calls'] = $this->dumpMethodCalls($definition->getMethodCalls());
    }

    // By default services are shared, so just provide the flag, when needed.
    if ($definition->isShared() === FALSE) {
      $service['shared'] = $definition->isShared();
    }

    if ($definition->getDecoratedService() !== NULL) {
      throw new InvalidArgumentException("The 'decorated' definition is not supported by the Drupal run-time container. The Container Builder should have resolved that during the DecoratorServicePass compiler pass.");
    }

    if ($callable = $definition->getFactory()) {
      $service['factory'] = $this->dumpCallable($callable);
    }

    if ($callable = $definition->getConfigurator()) {
      $service['configurator'] = $this->dumpCallable($callable);
    }

    return $service;
  }

  /**
   * Dumps method calls to a PHP array.
   *
   * @param array $calls
   *   An array of method calls.
   *
   * @return array
   *   The PHP array representation of the method calls.
   */
  protected function dumpMethodCalls(array $calls) {
    $code = [];

    foreach ($calls as $key => $call) {
      $method = $call[0];
      $arguments = [];
      if (!empty($call[1])) {
        $arguments = $this->dumpCollection($call[1]);
      }

      $code[$key] = [$method, $arguments];
    }

    return $code;
  }

  /**
   * Dumps a collection to a PHP array.
   *
   * @param mixed $collection
   *   A collection to process.
   * @param bool &$resolve
   *   Used for passing the information to the caller whether the given
   *   collection needed to be resolved or not. This is used for optimizing
   *   deep arrays that don't need to be traversed.
   *
   * @return object|array
   *   The collection in a suitable format.
   */
  protected function dumpCollection($collection, &$resolve = FALSE) {
    $code = [];

    foreach ($collection as $key => $value) {
      if (is_array($value)) {
        $resolve_collection = FALSE;
        $code[$key] = $this->dumpCollection($value, $resolve_collection);

        if ($resolve_collection) {
          $resolve = TRUE;
        }
      }
      else {
        $code[$key] = $this->dumpValue($value);
        if (is_object($code[$key])) {
          $resolve = TRUE;
        }
      }
    }

    if (!$resolve) {
      return $collection;
    }

    return (object) [
      'type' => 'collection',
      'value' => $code,
      'resolve' => $resolve,
    ];
  }

  /**
   * Dumps callable to a PHP array.
   *
   * @param array|callable $callable
   *   The callable to process.
   *
   * @return callable
   *   The processed callable.
   */
  protected function dumpCallable($callable) {
    if (is_array($callable)) {
      $callable[0] = $this->dumpValue($callable[0]);
      $callable = [$callable[0], $callable[1]];
    }

    return $callable;
  }

  /**
   * Gets a private service definition in a suitable format.
   *
   * @param string $id
   *   The ID of the service to get a private definition for.
   * @param \Symfony\Component\DependencyInjection\Definition $definition
   *   The definition to process.
   * @param bool $shared
   *   (optional) Whether the service will be shared with others.
   *   By default this parameter is FALSE.
   *
   * @return object
   *   A very lightweight private service value object.
   */
  protected function getPrivateServiceCall($id, Definition $definition, $shared = FALSE) {
    $service_definition = $this->getServiceDefinition($definition);
    if (!$id) {
      $hash = Crypt::hashBase64(serialize($service_definition));
      $id = 'private__' . $hash;
    }
    return (object) [
      'type' => 'private_service',
      'id' => $id,
      'value' => $service_definition,
      'shared' => $shared,
    ];
  }

  /**
   * Dumps the value to PHP array format.
   *
   * @param mixed $value
   *   The value to dump.
   *
   * @return mixed
   *   The dumped value in a suitable format.
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\RuntimeException
   *   When trying to dump object or resource.
   */
  protected function dumpValue($value) {
    if (is_array($value)) {
      $code = [];
      foreach ($value as $k => $v) {
        $code[$k] = $this->dumpValue($v);
      }

      return $code;
    }
    elseif ($value instanceof Reference) {
      return $this->getReferenceCall((string) $value, $value);
    }
    elseif ($value instanceof Definition) {
      return $this->getPrivateServiceCall(NULL, $value);
    }
    elseif ($value instanceof Parameter) {
      return $this->getParameterCall((string) $value);
    }
    elseif (is_string($value) && str_contains($value, '%')) {
      if (preg_match('/^%([^%]+)%$/', $value, $matches)) {
        return $this->getParameterCall($matches[1]);
      }
      else {
        $replaceParameters = function ($matches) {
          return $this->getParameterCall($matches[2]);
        };

        // We cannot directly return the string value because it would
        // potentially not always be resolved in the dumpCollection() method.
        return (object) [
          'type' => 'raw',
          'value' => str_replace('%%', '%', preg_replace_callback('/(?<!%)(%)([^%]+)\1/', $replaceParameters, $value)),
        ];
      }
    }
    elseif ($value instanceof Expression) {
      throw new RuntimeException('Unable to use expressions as the Symfony ExpressionLanguage component is not installed.');
    }
    elseif ($value instanceof ServiceClosureArgument) {
      $reference = $value->getValues();
      /** @var \Symfony\Component\DependencyInjection\Reference $reference */
      $reference = reset($reference);

      return $this->getServiceClosureCall((string) $reference, $reference->getInvalidBehavior());
    }
    elseif (is_object($value)) {
      // Drupal specific: Instantiated objects have a _serviceId parameter.
      if (isset($value->_serviceId)) {
        @trigger_error('_serviceId is deprecated in drupal:9.5.0 and is removed from drupal:11.0.0. Use \Drupal\Core\DrupalKernelInterface::getServiceIdMapping() instead. See https://www.drupal.org/node/3292540', E_USER_DEPRECATED);
        return $this->getReferenceCall($value->_serviceId);
      }
      throw new RuntimeException('Unable to dump a service container if a parameter is an object without _serviceId.');
    }
    elseif (is_resource($value)) {
      throw new RuntimeException('Unable to dump a service container if a parameter is a resource.');
    }

    return $value;
  }

  /**
   * Gets a service reference for a reference in a suitable PHP array format.
   *
   * The main difference is that this function treats references to private
   * services differently and returns a private service reference instead of
   * a normal reference.
   *
   * @param string $id
   *   The ID of the service to get a reference for.
   * @param \Symfony\Component\DependencyInjection\Reference|null $reference
   *   (optional) The reference object to process; needed to get the invalid
   *   behavior value.
   *
   * @return string|object
   *   A suitable representation of the service reference.
   */
  protected function getReferenceCall($id, Reference $reference = NULL) {
    $invalid_behavior = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE;

    if ($reference !== NULL) {
      $invalid_behavior = $reference->getInvalidBehavior();
    }

    // Private shared service.
    if (isset($this->aliases[$id])) {
      $id = $this->aliases[$id];
    }
    $definition = $this->container->getDefinition($id);
    if (!$definition->isPublic()) {
      // The ContainerBuilder does not share a private service, but this means a
      // new service is instantiated every time. Use a private shared service to
      // circumvent the problem.
      return $this->getPrivateServiceCall($id, $definition, TRUE);
    }

    return $this->getServiceCall($id, $invalid_behavior);
  }

  /**
   * Gets a service reference for an ID in a suitable PHP array format.
   *
   * @param string $id
   *   The ID of the service to get a reference for.
   * @param int $invalid_behavior
   *   (optional) The invalid behavior of the service.
   *
   * @return string|object
   *   A suitable representation of the service reference.
   */
  protected function getServiceCall($id, $invalid_behavior = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE) {
    return (object) [
      'type' => 'service',
      'id' => $id,
      'invalidBehavior' => $invalid_behavior,
    ];
  }

  /**
   * Gets a parameter reference in a suitable PHP array format.
   *
   * @param string $name
   *   The name of the parameter to get a reference for.
   *
   * @return string|object
   *   A suitable representation of the parameter reference.
   */
  protected function getParameterCall($name) {
    return (object) [
      'type' => 'parameter',
      'name' => $name,
    ];
  }

  /**
   * Whether this supports the machine-optimized format or not.
   *
   * @return bool
   *   TRUE if this supports machine-optimized format, FALSE otherwise.
   */
  protected function supportsMachineFormat() {
    return TRUE;
  }

  /**
   * Gets a service closure reference in a suitable PHP array format.
   *
   * @param string $id
   *   The ID of the service to get a reference for.
   * @param int $invalid_behavior
   *   (optional) The invalid behavior of the service.
   *
   * @return string|object
   *   A suitable representation of the service closure reference.
   */
  protected function getServiceClosureCall(string $id, int $invalid_behavior = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE) {
    return (object) [
      'type' => 'service_closure',
      'id' => $id,
      'invalidBehavior' => $invalid_behavior,
    ];
  }

}
