<?php

namespace Drupal\views\Plugin\views\argument;

/**
 * Argument handler for a week.
 *
 * @ViewsArgument("date_week")
 */
class WeekDate extends Date {

  /**
   * {@inheritdoc}
   */
  protected $argFormat = 'W';

  /**
   * Provide a link to the next level of the view
   */
  public function summaryName($data) {
    $created = $data->{$this->name_alias};
    return $this->t('Week @week', array('@week' => $created));
  }

}
