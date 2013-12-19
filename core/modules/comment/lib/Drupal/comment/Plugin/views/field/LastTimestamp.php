<?php

/**
 * @file
 * Definition of Drupal\comment\Plugin\views\field\LastTimestamp.
 */

namespace Drupal\comment\Plugin\views\field;

use Drupal\views\Plugin\views\field\Date;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;

/**
 * Field handler to display the timestamp of a comment with the count of comments.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("comment_last_timestamp")
 */
class LastTimestamp extends Date {

  /**
   * Overrides Drupal\views\Plugin\views\field\FieldPluginBase::init().
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->additional_fields['comment_count'] = 'comment_count';
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $comment_count = $this->getValue($values, 'comment_count');
    if (empty($this->options['empty_zero']) || $comment_count) {
      return parent::render($values);
    }
    else {
      return NULL;
    }
  }

}
