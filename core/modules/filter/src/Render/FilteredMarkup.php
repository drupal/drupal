<?php

namespace Drupal\filter\Render;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Render\MarkupTrait;

/**
 * Defines an object that passes markup through the Filter system.
 *
 * This object should only be constructed with markup that is safe to render. If
 * there is any risk that the string contains user-entered data that has not
 * been filtered first, it must not be used.
 *
 * @internal
 *   This object is marked as internal because it should only be used in the
 *   Filter module on strings that have already been been filtered and sanitized
 *   in \Drupal\filter\Plugin\FilterInterface.
 *
 * @see \Drupal\Core\Render\Markup
 */
final class FilteredMarkup implements MarkupInterface, \Countable {
  use MarkupTrait;
}
