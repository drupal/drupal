<?php

/**
 * @file
 * Definition of views_handler_field_comment_depth.
 */

namespace Drupal\comment\Plugin\views\field;

use Drupal\Core\Annotation\Plugin;
use Drupal\views\Plugins\views\field\FieldPluginBase;

/**
 * Field handler to display the depth of a comment.
 *
 * @ingroup views_field_handlers
 */

/**
 * @Plugin(
 *   plugin_id = "comment_depth"
 * )
 */
class views_handler_field_comment_depth extends FieldPluginBase {
  /**
   * Work out the depth of this comment
   */
  function render($values) {
    $comment_thread = $this->get_value($values);
    return count(explode('.', $comment_thread)) - 1;
  }
}
