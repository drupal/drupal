<?php

declare(strict_types=1);

namespace Drupal\Core\Hook;

use Drupal\Core\DependencyInjection\ContainerBuilder;

/**
 * Helper methods to set priorities of hook implementations.
 */
class HookOrder {

  /**
   * Set a hook implementation to be first.
   *
   * @param \Drupal\Core\DependencyInjection\ContainerBuilder $container
   *   The container builder.
   * @param string $hook
   *   The name of the hook.
   * @param string $class_and_method
   *   Class and method separated by :: containing the hook implementation.
   *
   * @return void
   */
  public static function first(ContainerBuilder $container, string $hook, string $class_and_method): void {
    self::changePriority($container, $hook, $class_and_method, TRUE);
  }

  /**
   * Set a hook implementation to be last.
   *
   * @param \Drupal\Core\DependencyInjection\ContainerBuilder $container
   *   The container builder.
   * @param string $hook
   *   The name of the hook.
   * @param string $class_and_method
   *   Class and method separated by :: containing the hook implementation.
   *
   * @return void
   */
  public static function last(ContainerBuilder $container, string $hook, string $class_and_method): void {
    self::changePriority($container, $hook, $class_and_method, FALSE);
  }

  /**
   * Set a hook implementation to fire before others.
   *
   * This method tries to keep existing order as much as possible. For
   * example, if there are five hook implementations, A, B, C, D, E firing in
   * this order then moving D before B will set it after A.
   *
   * @param \Drupal\Core\DependencyInjection\ContainerBuilder $container
   *   The container builder.
   * @param string $hook
   *   The name of the hook.
   * @param string $class_and_method
   *   Class and method separated by :: containing the hook implementation which
   *   should be changed.
   * @param string ...$others
   *   A list specifying the other implementations this hook should fire
   *   before. Every list member is a class and method separated by ::.
   *
   * @return void
   */
  public static function before(ContainerBuilder $container, string $hook, string $class_and_method, string ...$others): void {
    self::changePriority($container, $hook, $class_and_method, TRUE, array_flip($others));
  }

  /**
   * Set a hook implementation to fire after others.
   *
   * This method tries to keep existing order as much as possible. For
   * example, if there are five hook implementations, A, B, C, D, E firing in
   * this order then moving B after D will set it before E.
   *
   * @param \Drupal\Core\DependencyInjection\ContainerBuilder $container
   *   The container builder.
   * @param string $hook
   *   The name of the hook.
   * @param string $class_and_method
   *   Class and method separated by :: containing the hook implementation which
   *   should be changed.
   * @param string ...$others
   *   A list specifying the other implementations this hook should fire
   *   before. Every list member is a class and method separated by ::.
   *
   * @return void
   */
  public static function after(ContainerBuilder $container, string $hook, string $class_and_method, string ...$others): void {
    self::changePriority($container, $hook, $class_and_method, FALSE, array_flip($others));
  }

  /**
   * Change the priority of a hook implementation.
   *
   * @param \Drupal\Core\DependencyInjection\ContainerBuilder $container
   *   The container builder.
   * @param string $hook
   *   The name of the hook.
   * @param string $class_and_method
   *   Class and method separated by :: containing the hook implementation which
   *   should be changed.
   * @param bool $should_be_larger
   *   TRUE for before/first, FALSE for after/last. Larger priority listeners
   *   fire first.
   * @param array|null $others
   *   Other hook implementations to compare to, if any. The array is keyed by
   *   string containing a class and method separated by ::, the value is not
   *   used.
   *
   * @return void
   */
  protected static function changePriority(ContainerBuilder $container, string $hook, string $class_and_method, bool $should_be_larger, ?array $others = NULL): void {
    $event = "drupal_hook.$hook";
    foreach ($container->findTaggedServiceIds('kernel.event_listener') as $id => $attributes) {
      foreach ($attributes as $key => $tag) {
        if ($tag['event'] === $event) {
          $index = "$id.$key";
          $priority = $tag['priority'];
          // Symfony documents event listener priorities to be integers,
          // HookCollectorPass sets them to be integers, ::setPriority() only
          // accepts integers.
          assert(is_int($priority));
          $priorities[$index] = $priority;
          $specifier = "$id::" . $tag['method'];
          if ($class_and_method === $specifier) {
            $index_this = $index;
          }
          // If $others is specified by ::before() / ::after() then for
          // comparison only the priority of those matter.
          // For ::first() / ::last() the priority of every other hook
          // matters.
          elseif (!isset($others) || isset($others[$specifier])) {
            $priorities_other[] = $priority;
          }
        }
      }
    }
    if (!isset($index_this) || !isset($priorities) || !isset($priorities_other)) {
      return;
    }
    // The priority of the hook being changed.
    $priority_this = $priorities[$index_this];
    // The priority of the hook being compared to.
    $priority_other = $should_be_larger ? max($priorities_other) : min($priorities_other);
    // If the order is correct there is nothing to do. If the two priorities
    // are the same then the order is undefined and so it can't be correct.
    // If they are not the same and $priority_this is already larger exactly
    // when $should_be_larger says then it's the correct order.
    if ($priority_this !== $priority_other && ($should_be_larger === ($priority_this > $priority_other))) {
      return;
    }
    $priority_new = $priority_other + ($should_be_larger ? 1 : -1);
    // For ::first() / ::last() this new priority is already larger/smaller
    // than all existing priorities but for ::before() / ::after() it might
    // belong to an already existing hook. In this case set the new priority
    // temporarily to be halfway between $priority_other and $priority_new
    // then give all hook implementations new, integer priorities keeping this
    // new order. This ensures the hook implementation being changed is in the
    // right order relative to both $priority_other and the hook whose
    // priority was $priority_new.
    if (in_array($priority_new, $priorities)) {
      $priorities[$index_this] = $priority_other + ($should_be_larger ? 0.5 : -0.5);
      asort($priorities);
      $changed_indexes = array_keys($priorities);
      $priorities = array_combine($changed_indexes, range(1, count($changed_indexes)));
    }
    else {
      $priorities[$index_this] = $priority_new;
      $changed_indexes = [$index_this];
    }
    foreach ($changed_indexes as $index) {
      [$id, $key] = explode('.', $index);
      self::setPriority($container, $id, (int) $key, $priorities[$index]);
    }
  }

  /**
   * Set the priority of a listener.
   *
   * @param \Drupal\Core\DependencyInjection\ContainerBuilder $container
   *   The container.
   * @param string $class
   *   The name of the class, this is the same as the service id.
   * @param int $key
   *   The key within the tags array of the 'kernel.event_listener' tag for the
   *   hook implementation to be changed.
   * @param int $priority
   *   The new priority.
   *
   * @return void
   */
  public static function setPriority(ContainerBuilder $container, string $class, int $key, int $priority): void {
    $definition = $container->getDefinition($class);
    $tags = $definition->getTags();
    $tags['kernel.event_listener'][$key]['priority'] = $priority;
    $definition->setTags($tags);
  }

}
