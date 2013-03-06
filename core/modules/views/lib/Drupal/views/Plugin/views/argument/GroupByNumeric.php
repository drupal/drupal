<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\argument\GroupByNumeric.
 */

namespace Drupal\views\Plugin\views\argument;

use Drupal\Component\Annotation\Plugin;

/**
 * Simple handler for arguments using group by.
 *
 * @ingroup views_argument_handlers
 *
 * @Plugin(
 *   id = "groupby_numeric"
 * )
 */
class GroupByNumeric extends ArgumentPluginBase {

  public function query($group_by = FALSE) {
    $this->ensureMyTable();
    $field = $this->getField();
    $placeholder = $this->placeholder();

    $this->query->add_having_expression(0, "$field = $placeholder", array($placeholder => $this->argument));
  }

  public function adminLabel($short = FALSE) {
    return $this->getField(parent::adminLabel($short));
  }

  function get_sort_name() {
    return t('Numerical', array(), array('context' => 'Sort order'));
  }

}
