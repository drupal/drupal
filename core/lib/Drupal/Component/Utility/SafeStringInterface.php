<?php

/**
 * @file
 * Contains \Drupal\Component\Utility\SafeStringInterface.
 */

namespace Drupal\Component\Utility;

/**
 * Marks an object's __toString() method as returning safe markup.
 *
 * This interface should only be used on objects that emit known safe strings
 * from their __toString() method. If there is any risk of the method returning
 * user-entered data that has not been filtered first, it must not be used.
 *
 * @internal
 *   This interface is marked as internal because it should only be used by
 *   objects used during rendering. Currently, there is no use case for this
 *   interface in contrib or custom code.
 *
 * @see \Drupal\Component\Utility\SafeMarkup::set()
 * @see \Drupal\Component\Utility\SafeMarkup::isSafe()
 * @see \Drupal\Core\Template\TwigExtension::escapeFilter()
 */
interface SafeStringInterface {

  /**
   * Returns a safe string.
   *
   * @return string
   *   The safe string.
   */
  public function __toString();

}
