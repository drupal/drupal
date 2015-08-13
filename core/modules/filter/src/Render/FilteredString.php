<?php

/**
 * @file
 * Contains \Drupal\filter\Render\FilteredString.
 */

namespace Drupal\filter\Render;

use Drupal\Component\Utility\SafeStringInterface;
use Drupal\Component\Utility\SafeStringTrait;

/**
 * Defines an object that passes safe strings through the Filter system.
 *
 * This object should only be constructed with a known safe string. If there is
 * any risk that the string contains user-entered data that has not been
 * filtered first, it must not be used.
 *
 * @internal
 *   This object is marked as internal because it should only be used in the
 *   Filter module on strings that have already been been filtered and sanitized
 *   in \Drupal\filter\Plugin\FilterInterface.
 *
 * @see \Drupal\Core\Render\SafeString
 */
final class FilteredString implements SafeStringInterface, \Countable {
  use SafeStringTrait;
}
