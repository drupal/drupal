<?php

/**
 * @file
 * Contains \Drupal\Core\Render\Markup.
 */

namespace Drupal\Core\Render;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Render\MarkupTrait;

/**
 * Defines an object that passes safe strings through the render system.
 *
 * This object should only be constructed with a known safe string. If there is
 * any risk that the string contains user-entered data that has not been
 * filtered first, it must not be used.
 *
 * @internal
 *   This object is marked as internal because it should only be used whilst
 *   rendering.
 *
 * @see \Drupal\Core\Template\TwigExtension::escapeFilter
 * @see \Twig_Markup
 * @see \Drupal\Component\Utility\SafeMarkup
 */
final class Markup implements MarkupInterface, \Countable {
  use MarkupTrait;
}
