<?php

/**
 * @file
 * Definition of views_handler_field_aggregator_xss.
 */

use Drupal\views\Plugins\views\field\FieldPluginBase;

/**
 * Filters htmls tags from item.
 *
 * @ingroup views_field_handlers
 */
class views_handler_field_aggregator_xss extends FieldPluginBase {
  function render($values) {
    $value = $this->get_value($values);
    return aggregator_filter_xss($value);
  }
}
