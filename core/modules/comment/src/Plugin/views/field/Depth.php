<?php

namespace Drupal\comment\Plugin\views\field;

use Drupal\views\Plugin\views\field\EntityField;
use Drupal\views\ResultRow;

/**
 * Field handler to display the depth of a comment.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("comment_depth")
 */
class Depth extends EntityField {

  /**
   * {@inheritdoc}
   */
  public function getItems(ResultRow $values) {
    $items = parent::getItems($values);

    foreach ($items as &$item) {
      // Work out the depth of this comment.
      $comment_thread = $item['rendered']['#context']['value'];
      $item['rendered']['#context']['value'] = count(explode('.', $comment_thread)) - 1;
    }
    return $items;
  }

}
