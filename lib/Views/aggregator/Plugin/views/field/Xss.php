<?php

/**
 * @file
 * Definition of views_handler_field_aggregator_xss.
 */

namespace Views\aggregator\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\Core\Annotation\Plugin;

/**
 * Filters htmls tags from item.
 *
 * @ingroup views_field_handlers
 */

/**
 * @Plugin(
 *   plugin_id = "aggregator_xss"
 * )
 */
class Xss extends FieldPluginBase {
  function render($values) {
    $value = $this->get_value($values);
    return aggregator_filter_xss($value);
  }
}
