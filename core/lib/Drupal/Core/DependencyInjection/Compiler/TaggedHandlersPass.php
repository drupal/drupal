<?php

namespace Drupal\Core\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Collects services to add/inject them into a consumer service.
 *
 * This mechanism allows a service to get multiple processor services injected,
 * in order to establish an extensible architecture.
 *
 * It differs from the factory pattern in that processors are not lazily
 * instantiated on demand; the consuming service receives instances of all
 * registered processors when it is instantiated. Unlike a factory service, the
 * consuming service is not ContainerAware.
 *
 * It differs from plugins in that all processors are explicitly registered by
 * service providers (driven by declarative configuration in code); the mere
 * availability of a processor (cf. plugin discovery) does not imply that a
 * processor ought to be registered and used.
 *
 * It differs from regular service definition arguments (constructor injection)
 * in that a consuming service MAY allow further processors to be added
 * dynamically at runtime. This is why the called method (optionally) receives
 * the priority of a processor as second argument.
 *
 * @see \Drupal\Core\DependencyInjection\Compiler\TaggedHandlersPass::process()
 */
class TaggedHandlersPass implements CompilerPassInterface {

  /**
   * {@inheritdoc}
   *
   * Finds services tagged with 'service_collector', then finds all
   * corresponding tagged services and adds a method call for each to the
   * consuming/collecting service definition.
   *
   * Supported 'service_collector' tag attributes:
   * - tag: The tag name used by handler services to collect. Defaults to the
   *   service ID of the consumer.
   * - call: The method name to call on the consumer service. Defaults to
   *   'addHandler'. The called method receives two arguments:
   *   - The handler instance as first argument.
   *   - Optionally the handler's priority as second argument, if the method
   *     accepts a second parameter and its name is "priority". In any case, all
   *     handlers registered at compile time are sorted already.
   * - required: Boolean indicating if at least one handler service is required.
   *   Defaults to FALSE.
   *
   * Example (YAML):
   * @code
   * tags:
   *   - { name: service_collector, tag: breadcrumb_builder, call: addBuilder }
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
    foreach ($container->findTaggedServiceIds('service_collector') as $consumer_id => $passes) {
      foreach ($passes as $pass) {
        $interface = NULL;
        $tag = isset($pass['tag']) ? $pass['tag'] : $consumer_id;
        $method_name = isset($pass['call']) ? $pass['call'] : 'addHandler';
        $required = isset($pass['required']) ? $pass['required'] : FALSE;

        // Determine parameters.
        $consumer = $container->getDefinition($consumer_id);
        $method = new \ReflectionMethod($consumer->getClass(), $method_name);
        $params = $method->getParameters();

        $interface_pos = 0;
        $id_pos = NULL;
        $priority_pos = NULL;
        $extra_params = [];
        foreach ($params as $pos => $param) {
          if ($param->getClass()) {
            $interface = $param->getClass();
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
        $interface = $interface->getName();

        // Find all tagged handlers.
        $handlers = [];
        $extra_arguments = [];
        foreach ($container->findTaggedServiceIds($tag) as $id => $attributes) {
          // Validate the interface.
          $handler = $container->getDefinition($id);
          if (!is_subclass_of($handler->getClass(), $interface)) {
            throw new LogicException("Service '$id' for consumer '$consumer_id' does not implement $interface.");
          }
          $handlers[$id] = isset($attributes[0]['priority']) ? $attributes[0]['priority'] : 0;
          // Keep track of other tagged handlers arguments.
          foreach ($extra_params as $name => $pos) {
            $extra_arguments[$id][$pos] = isset($attributes[0][$name]) ? $attributes[0][$name] : $params[$pos]->getDefaultValue();
          }
        }
        if (empty($handlers)) {
          if ($required) {
            throw new LogicException(sprintf("At least one service tagged with '%s' is required.", $tag));
          }
          continue;
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
    }
  }

}
