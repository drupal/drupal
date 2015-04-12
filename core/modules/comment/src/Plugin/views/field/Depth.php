<?php

/**
 * @file
 * Definition of Drupal\comment\Plugin\views\field\Depth.
 */

namespace Drupal\comment\Plugin\views\field;

use Drupal\views\Plugin\views\field\Field;
use Drupal\views\ResultRow;

/**
 * Field handler to display the depth of a comment.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("comment_depth")
 */
class Depth extends Field {

  /**
   * {@inheritdoc}
   */
  public function getItems(ResultRow $values) {
    $items = parent::getItems($values);

    foreach ($items as &$item) {
      // Work out the depth of this comment.
      $comment_thread = $item['rendered']['#markup'];
      $item['rendered']['#markup'] =  count(explode('.', $comment_thread)) - 1;
    }
    return $items;
  }

}
