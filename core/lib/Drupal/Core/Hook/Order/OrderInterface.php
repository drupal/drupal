<?php

declare(strict_types = 1);

namespace Drupal\Core\Hook\Order;

use Drupal\Core\Hook\OrderOperation\OrderOperation;

/**
 * Interface for order specifiers used in hook attributes.
 *
 * Objects implementing this interface allow for relative ordering of hooks.
 * These objects are passed as an order parameter to a Hook or ReorderHook
 * attribute.
 * Order::First and Order::Last are simple order operations that move the hook
 * implementation to the first or last position of hooks at the time the order
 * directive is executed.
 *   @code
 *   #[Hook('custom_hook', order: Order::First)]
 *   @endcode
 * OrderBefore and OrderAfter take additional parameters
 * for ordering. See Drupal\Core\Hook\Order\RelativeOrderBase.
 *   @code
 *   #[Hook('custom_hook', order: new OrderBefore(['other_module']))]
 *   @endcode
 */
interface OrderInterface {

  /**
   * Gets order operations specified by this object.
   *
   * @param string $identifier
   *   Identifier of the implementation to move to a new position. The format
   *   is the class followed by "::" then the method name. For example,
   *   "Drupal\my_module\Hook\MyModuleHooks::methodName".
   *
   * @return \Drupal\Core\Hook\OrderOperation\OrderOperation
   *   Order operation to apply to a hook implementation list.
   */
  public function getOperation(string $identifier): OrderOperation;

}
