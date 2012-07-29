<?php

/**
 * @file
 * Definition of views_handler_filter_node_type.
 */

namespace Drupal\node\Plugin\views\filter;

use Drupal\views\Plugins\views\filter\InOperator;

/**
 * Filter by node type.
 *
 * @ingroup views_filter_handlers
 */

/**
 * @Plugin(
 *   plugin_id = "node_type"
 * )
 */
class Type extends InOperator {
  function get_value_options() {
    if (!isset($this->value_options)) {
      $this->value_title = t('Content types');
      $types = node_type_get_types();
      $options = array();
      foreach ($types as $type => $info) {
        $options[$type] = t($info->name);
      }
      asort($options);
      $this->value_options = $options;
    }
  }
}
