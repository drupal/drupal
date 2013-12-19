<?php

/**
 * @file
 * Definition of Drupal\comment\Plugin\views\field\Depth.
 */

namespace Drupal\comment\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to display the depth of a comment.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("comment_depth")
 */
class Depth extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    // Work out the depth of this comment.
    $comment_thread = $this->getValue($values);
    return count(explode('.', $comment_thread)) - 1;
  }

}
