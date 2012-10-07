<?php

/**
 * @file
 * Definition of Drupal\node\Plugin\views\filter\Type.
 */

namespace Drupal\node\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\Core\Annotation\Plugin;

/**
 * Filter by node type.
 *
 * @ingroup views_filter_handlers
 *
 * @Plugin(
 *   id = "node_type",
 *   module = "node"
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
