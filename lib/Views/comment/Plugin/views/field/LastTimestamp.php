<?php

/**
 * @file
 * Definition of views_handler_field_last_comment_timestamp.
 */

namespace Views\comment\Plugin\views\field;

use Drupal\views\Plugin\views\field\Date;
use Drupal\Core\Annotation\Plugin;

/**
 * Field handler to display the timestamp of a comment with the count of comments.
 *
 * @ingroup views_field_handlers
 */

/**
 * @Plugin(
 *   plugin_id = "comment_last_timestamp"
 * )
 */
class LastTimestamp extends Date {
  function construct() {
    parent::construct();
    $this->additional_fields['comment_count'] = 'comment_count';
  }

  function render($values) {
    $comment_count = $this->get_value($values, 'comment_count');
    if (empty($this->options['empty_zero']) || $comment_count) {
      return parent::render($values);
    }
    else {
      return NULL;
    }
  }
}
