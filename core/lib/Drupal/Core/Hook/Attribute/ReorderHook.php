<?php

declare(strict_types=1);

namespace Drupal\Core\Hook\Attribute;

use Drupal\Core\Hook\Order\OrderInterface;

/**
 * Sets the order of an already existing implementation.
 *
 * The effect of this attribute is independent from the specific class or method
 * on which it is placed.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class ReorderHook implements HookAttributeInterface {

  /**
   * Constructs a ReorderHook object.
   *
   * @param string $hook
   *   The hook for which to reorder an implementation.
   * @param class-string $class
   *   The class of the targeted hook implementation.
   * @param string $method
   *   The method name of the targeted hook implementation.
   *   If the #[Hook] attribute is on the class itself, this should be
   *   '__invoke'.
   * @param \Drupal\Core\Hook\Order\OrderInterface $order
   *   Specifies a new position for the targeted hook implementation relative to
   *   other implementations.
   */
  public function __construct(
    public string $hook,
    public string $class,
    public string $method,
    public OrderInterface $order,
  ) {}

}
