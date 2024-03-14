<?php

namespace Drupal\views\Plugin\views\argument;

use Drupal\views\Attribute\ViewsArgument;

/**
 * Argument handler for a week.
 */
#[ViewsArgument(
  id: 'date_week',
)]
class WeekDate extends Date {

  /**
   * {@inheritdoc}
   */
  protected $argFormat = 'W';

  /**
   * Provide a link to the next level of the view.
   */
  public function summaryName($data) {
    $created = $data->{$this->name_alias};
    return $this->t('Week @week', ['@week' => $created]);
  }

}
