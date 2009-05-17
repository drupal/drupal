<?php

/**
 * @file
 * Definition of views_handler_field_aggregator_xss.
 */

/**
 * Filters htmls tags from item.
 *
 * @ingroup views_field_handlers
 */
class views_handler_field_aggregator_xss extends views_handler_field {
  function render($values) {
    $value = $this->get_value($values);
    return aggregator_filter_xss($value);
  }
}
