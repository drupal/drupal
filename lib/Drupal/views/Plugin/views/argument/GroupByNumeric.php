<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\argument\GroupByNumeric.
 */

namespace Drupal\views\Plugin\views\argument;

use Drupal\Core\Annotation\Plugin;

/**
 * Simple handler for arguments using group by.
 *
 * @ingroup views_argument_handlers
 */

/**
 * @Plugin(
 *   id = "groupby_numeric"
 * )
 */
class GroupByNumeric extends ArgumentPluginBase  {
  function query($group_by = FALSE) {
    $this->ensure_my_table();
    $field = $this->get_field();
    $placeholder = $this->placeholder();

    $this->query->add_having_expression(0, "$field = $placeholder", array($placeholder => $this->argument));
  }

  function ui_name($short = FALSE) {
    return $this->get_field(parent::ui_name($short));
  }

  function get_sort_name() {
    return t('Numerical', array(), array('context' => 'Sort order'));
  }
}
