<?php

/**
 * @file
 * Contains \Drupal\aggregator\Plugin\views\field\Xss.
 */

namespace Drupal\aggregator\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Filters HTML tags from item.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("aggregator_xss")
 */
class Xss extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    return aggregator_filter_xss($this->getValue($values));
  }

}
