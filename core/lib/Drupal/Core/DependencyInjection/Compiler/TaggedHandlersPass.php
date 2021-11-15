<?php

namespace Drupal\Core\DependencyInjection\Compiler;

use Drupal\Component\Utility\Reflection;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Collects services to add/inject them into a consumer service.
 *
 * This mechanism allows a service to get multiple processor services or just
 * their IDs injected, in order to establish an extensible architecture.
 *
 * The service collector differs from the factory pattern in that processors are
 * not lazily instantiated on demand; the consuming service receives instances
 * of all registered processors when it is instantiated. Unlike a factory
 * service, the consuming service is not ContainerAware. It differs from regular
 * service definition arguments (constructor injection) in that a consuming
 * service MAY allow further processors to be added dynamically at runtime. This
 * is why the called method (optionally) receives the priority of a processor as
 * second argument.
 *
 * To lazily instantiate services the service ID collector pattern can be used,
 * but the consumer service needs to also inject the 'class_resolver' service.
 * As constructor injection is used, processors cannot be added at runtime via
 * this method. However, a consuming service could have setter methods to allow
 * runtime additions.
 *
 * These differ from plugins in that all processors are explicitly registered by
 * service providers (driven by declarative configuration in code); the mere
 * availability of a processor (cf. plugin discovery) does not imply that a
 * processor ought to be registered and used.
 *
 * @see \Drupal\Core\DependencyInjection\Compiler\TaggedHandlersPass::process()
 */
class TaggedHandlersPass implements CompilerPassInterface {

  /**
   * Service tag information keyed by tag name.
   *
   * @var array
   */
  protected $tagCache = [];

  /**
   * {@inheritdoc}
   *
   * Finds services tagged with 'service_collector' or 'service_id_collector',
   * then finds all corresponding tagged services.
   *
   * The service collector adds a method call for each to the
   * consuming/collecting service definition.
   *
   * The service ID collector will collect an array of service IDs and add them
   * as a constructor argument.
   *
   * Supported tag attributes:
   * - tag: The tag name used by handler services to collect. Defaults to the
   *   service ID of the consumer.
   * - required: Boolean indicating if at least one handler service is required.
   *   Defaults to FALSE.
   *
   * Additional tag attributes supported by 'service_collector' only:
   *   - call: The method name to call on the consumer service. Defaults to
   *     'addHandler'. The called method receives at least one argument,
   *     optionally more:
   *     - The handler instance must be the first method parameter, and it must
   *       have a type declaration.
   *     - If the method has a parameter named $id, in any position, it will
   *       receive the value of service ID when called.
   *     - If the method has a parameter named $priority, in any position, it
   *       will receive the value of the tag's 'priority' attribute.
   *     - Any other method parameters whose names match the name of an
   *       attribute of the tag will receive the value of that tag attribute.The
   *       order of the method parameters and the order of the service tag
   *       attributes do not need to match.
   *
   * Example (YAML):
   * @code
   * tags:
   *   - { name: service_collector, tag: breadcrumb_builder, call: addBuilder }
   *   - { name: service_id_collector, tag: theme_negotiator }
   * @endcode
   *
   * Supported handler tag attributes:
   * - priority: An integer denoting the priority of the handler. Defaults to 0.
   *
   * Example (YAML):
   * @code
   * tags:
   *   - { name: breadcrumb_builder, priority: 100 }
   * @endcode
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\LogicException
   *   If the method of a consumer service to be called does not type-hint an
   *   interface.
   * @throws \Symfony\Component\DependencyInjection\Exception\LogicException
   *   If a tagged handler does not implement the required interface.
   * @throws \Symfony\Component\DependencyInjection\Exception\LogicException
   *   If at least one tagged service is required but none are found.
   */
  public function process(ContainerBuilder $container) {
    // Avoid using ContainerBuilder::findTaggedServiceIds() as that results in
    // additional iterations around all the service definitions.
    foreach ($container->getDefinitions() as $id => $definition) {
      foreach ($definition->getTags() as $name => $info) {
        $this->tagCache[$name][$id] = $info;
      }
    }

    foreach ($this->tagCache['service_collector'] ?? [] as $consumer_id => $tags) {
      foreach ($tags as $pass) {
        $this->processServiceCollectorPass($pass, $consumer_id, $container);
      }
    }
    foreach ($this->tagCache['service_id_collector'] ?? [] as $consumer_id => $tags) {
      foreach ($tags as $pass) {
        $this->processServiceIdCollectorPass($pass, $consumer_id, $container);
      }
    }
  }

  /**
   * Processes a service collector service pass.
   *
   * @param array $pass
   *   The service collector pass data.
   * @param string $consumer_id
   *   The consumer service ID.
   * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
   *   The service container.
   */
  protected function processServiceCollectorPass(array $pass, $consumer_id, ContainerBuilder $container) {
    $tag = $pass['tag'] ?? $consumer_id;
    $method_name = $pass['call'] ?? 'addHandler';
    $required = $pass['required'] ?? FALSE;

    // Determine parameters.
    $consumer = $container->getDefinition($consumer_id);
    $method = new \ReflectionMethod($consumer->getClass(), $method_name);
    $params = $method->getParameters();

    $interface_pos = 0;
    $id_pos = NULL;
    $priority_pos = NULL;
    $extra_params = [];
    foreach ($params as $pos => $param) {
      $class = Reflection::getParameterClassName($param);
      if ($class !== NULL) {
        $interface = $class;
      }
      elseif ($param->getName() === 'id') {
        $id_pos = $pos;
      }
      elseif ($param->getName() === 'priority') {
        $priority_pos = $pos;
      }
      else {
        $extra_params[$param->getName()] = $pos;
      }
    }
    // Determine the ID.

    if (!isset($interface)) {
      throw new LogicException(vsprintf("Service consumer '%s' class method %s::%s() has to type-hint an interface.", [
        $consumer_id,
        $consumer->getClass(),
        $method_name,
      ]));
    }

    // Find all tagged handlers.
    $handlers = [];
    $extra_arguments = [];
    foreach ($this->tagCache[$tag] ?? [] as $id => $attributes) {
      // Validate the interface.
      $handler = $container->getDefinition($id);
      if (!is_subclass_of($handler->getClass(), $interface)) {
        throw new LogicException("Service '$id' for consumer '$consumer_id' does not implement $interface.");
      }
      $handlers[$id] = $attributes[0]['priority'] ?? 0;
      // Keep track of other tagged handlers arguments.
      foreach ($extra_params as $name => $pos) {
        $extra_arguments[$id][$pos] = $attributes[0][$name] ?? $params[$pos]->getDefaultValue();
      }
    }

    if ($required && empty($handlers)) {
      throw new LogicException(sprintf("At least one service tagged with '%s' is required.", $tag));
    }

    // Sort all handlers by priority.
    arsort($handlers, SORT_NUMERIC);

    // Add a method call for each handler to the consumer service
    // definition.
    foreach ($handlers as $id => $priority) {
      $arguments = [];
      $arguments[$interface_pos] = new Reference($id);
      if (isset($priority_pos)) {
        $arguments[$priority_pos] = $priority;
      }
      if (isset($id_pos)) {
        $arguments[$id_pos] = $id;
      }
      // Add in extra arguments.
      if (isset($extra_arguments[$id])) {
        // Place extra arguments in their right positions.
        $arguments += $extra_arguments[$id];
      }
      // Sort the arguments by position.
      ksort($arguments);
      $consumer->addMethodCall($method_name, $arguments);
    }
  }

  /**
   * Processes a service collector ID service pass.
   *
   * @param array $pass
   *   The service collector pass data.
   * @param string $consumer_id
   *   The consumer service ID.
   * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
   *   The service container.
   */
  protected function processServiceIdCollectorPass(array $pass, $consumer_id, ContainerBuilder $container) {
    $tag = $pass['tag'] ?? $consumer_id;
    $required = $pass['required'] ?? FALSE;

    $consumer = $container->getDefinition($consumer_id);

    // Find all tagged handlers.
    $handlers = [];
    foreach ($this->tagCache[$tag] ?? [] as $id => $attributes) {
      $handlers[$id] = $attributes[0]['priority'] ?? 0;
    }

    if ($required && empty($handlers)) {
      throw new LogicException(sprintf("At least one service tagged with '%s' is required.", $tag));
    }

    // Sort all handlers by priority.
    arsort($handlers, SORT_NUMERIC);

    $consumer->addArgument(array_keys($handlers));
  }

}
