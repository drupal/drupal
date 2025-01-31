<?php

namespace Drupal\views\Plugin\views\argument;

use Drupal\views\Attribute\ViewsArgument;

/**
 * Simple handler for arguments using group by.
 *
 * @ingroup views_argument_handlers
 */
#[ViewsArgument(
  id: 'groupby_numeric',
)]
class GroupByNumeric extends ArgumentPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query($group_by = FALSE) {
    $this->ensureMyTable();
    $field = $this->getField();
    $placeholder = $this->placeholder();

    $this->query->addHavingExpression(0, "$field = $placeholder", [$placeholder => $this->argument]);
  }

  /**
   * {@inheritdoc}
   */
  public function adminLabel($short = FALSE) {
    return $this->getField(parent::adminLabel($short));
  }

  /**
   * {@inheritdoc}
   */
  public function getSortName() {
    return $this->t('Numerical', [], ['context' => 'Sort order']);
  }

}
