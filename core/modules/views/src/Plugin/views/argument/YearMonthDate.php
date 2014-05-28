<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\argument\YearMonthDate.
 */

namespace Drupal\views\Plugin\views\argument;

/**
 * Argument handler for a year plus month (CCYYMM)
 *
 * @ViewsArgument("date_year_month")
 */
class YearMonthDate extends Date {

  /**
   * {@inheritdoc}
   */
  protected $format = 'F Y';

  /**
   * {@inheritdoc}
   */
  protected $argFormat = 'Ym';

  /**
   * Provide a link to the next level of the view
   */
  public function summaryName($data) {
    $created = $data->{$this->name_alias};
    return format_date(strtotime($created . "15" . " 00:00:00 UTC"), 'custom', $this->format, 'UTC');
  }

  /**
   * Provide a link to the next level of the view
   */
  function title() {
    return format_date(strtotime($this->argument . "15" . " 00:00:00 UTC"), 'custom', $this->format, 'UTC');
  }

}
