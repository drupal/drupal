<?php

/**
 * @file
 * Contains \Drupal\Component\Utility\SafeStringInterface.
 */

namespace Drupal\Component\Utility;

/**
 * Marks an object's __toString() method as returning safe markup.
 *
 * All objects that implement this interface should be marked @internal.
 *
 * This interface should only be used on objects that emit known safe strings
 * from their __toString() method. If there is any risk of the method returning
 * user-entered data that has not been filtered first, it must not be used.
 *
 * If the object is going to be used directly in Twig templates it should
 * implement \Countable so it can be used in if statements.
 *
 * @internal
 *   This interface is marked as internal because it should only be used by
 *   objects used during rendering. This interface should be used by modules if
 *   they interrupt the render pipeline and explicitly deal with SafeString
 *   objects created by the render system. Additionally, if a module reuses the
 *   regular render pipeline internally and passes processed data into it. For
 *   example, Views implements a custom render pipeline in order to render JSON
 *   and to fast render fields.
 *
 * @see \Drupal\Component\Utility\SafeStringTrait
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
