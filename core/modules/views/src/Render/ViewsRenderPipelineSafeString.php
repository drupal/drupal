<?php

/**
 * @file
 * Contains \Drupal\views\Render\ViewsRenderPipelineSafeString.
 */

namespace Drupal\views\Render;

use Drupal\Component\Utility\SafeStringInterface;
use Drupal\Component\Utility\SafeStringTrait;

/**
 * Defines an object that passes safe strings through the Views render system.
 *
 * This object should only be constructed with a known safe string. If there is
 * any risk that the string contains user-entered data that has not been
 * filtered first, it must not be used.
 *
 * @internal
 *   This object is marked as internal because it should only be used in the
 *   Views render pipeline.
 *
 * @see \Drupal\Core\Render\SafeString
 */
final class ViewsRenderPipelineSafeString implements SafeStringInterface, \Countable {
  use SafeStringTrait;
}
