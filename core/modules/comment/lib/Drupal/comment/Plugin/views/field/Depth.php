<?php

/**
 * @file
 * Definition of Drupal\comment\Plugin\views\field\Depth.
 */

namespace Drupal\comment\Plugin\views\field;

use Drupal\Component\Annotation\PluginID;
use Drupal\views\Plugin\views\field\FieldPluginBase;

/**
 * Field handler to display the depth of a comment.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("comment_depth")
 */
class Depth extends FieldPluginBase {

  /**
   * Work out the depth of this comment
   */
  public function render($values) {
    $comment_thread = $this->getValue($values);
    return count(explode('.', $comment_thread)) - 1;
  }

}
