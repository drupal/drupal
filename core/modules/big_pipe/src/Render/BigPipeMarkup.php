<?php

namespace Drupal\big_pipe\Render;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Render\MarkupTrait;

/**
 * Defines an object that passes safe strings through BigPipe's render pipeline.
 *
 * This object should only be constructed with a known safe string. If there is
 * any risk that the string contains user-entered data that has not been
 * filtered first, it must not be used.
 *
 * @internal
 *   This object is marked as internal because it should only be used in the
 *   BigPipe render pipeline.
 *
 * @see \Drupal\Core\Render\Markup
 */
final class BigPipeMarkup implements MarkupInterface, \Countable {
  use MarkupTrait;
}
