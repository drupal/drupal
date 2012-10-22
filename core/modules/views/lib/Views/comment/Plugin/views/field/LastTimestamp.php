<?php

/**
 * @file
 * Definition of Views\comment\Plugin\views\field\LastTimestamp.
 */

namespace Views\comment\Plugin\views\field;

use Drupal\views\Plugin\views\field\Date;
use Drupal\views\ViewExecutable;
use Drupal\Core\Annotation\Plugin;

/**
 * Field handler to display the timestamp of a comment with the count of comments.
 *
 * @ingroup views_field_handlers
 *
 * @Plugin(
 *   id = "comment_last_timestamp",
 *   module = "comment"
 * )
 */
class LastTimestamp extends Date {

  /**
   * Overrides Drupal\views\Plugin\views\field\FieldPluginBase::init().
   */
  public function init(ViewExecutable $view, &$options) {
    parent::init($view, $options);

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
