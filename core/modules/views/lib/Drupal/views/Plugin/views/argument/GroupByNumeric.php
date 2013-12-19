<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\argument\GroupByNumeric.
 */

namespace Drupal\views\Plugin\views\argument;

/**
 * Simple handler for arguments using group by.
 *
 * @ingroup views_argument_handlers
 *
 * @PluginID("groupby_numeric")
 */
class GroupByNumeric extends ArgumentPluginBase {

  public function query($group_by = FALSE) {
    $this->ensureMyTable();
    $field = $this->getField();
    $placeholder = $this->placeholder();

    $this->query->addHavingExpression(0, "$field = $placeholder", array($placeholder => $this->argument));
  }

  public function adminLabel($short = FALSE) {
    return $this->getField(parent::adminLabel($short));
  }

  /**
   * {@inheritdoc}
   */
  public function getSortName() {
    return t('Numerical', array(), array('context' => 'Sort order'));
  }

}
