<?php

namespace Drupal\views\Plugin\views\argument;

/**
 * Argument handler for a day (DD)
 *
 * @ViewsArgument("date_day")
 */
class DayDate extends Date {

  /**
   * {@inheritdoc}
   */
  protected $format = 'j';

  /**
   * {@inheritdoc}
   */
  protected $argFormat = 'd';

  /**
   * Provide a link to the next level of the view
   */
  public function summaryName($data) {
    $day = str_pad($data->{$this->name_alias}, 2, '0', STR_PAD_LEFT);
    // strtotime respects server timezone, so we need to set the time fixed as utc time
    return format_date(strtotime("2005" . "05" . $day . " 00:00:00 UTC"), 'custom', $this->format, 'UTC');
  }

  /**
   * Provide a link to the next level of the view
   */
  public function title() {
    $day = str_pad($this->argument, 2, '0', STR_PAD_LEFT);
    return format_date(strtotime("2005" . "05" . $day . " 00:00:00 UTC"), 'custom', $this->format, 'UTC');
  }

  public function summaryArgument($data) {
    // Make sure the argument contains leading zeroes.
    return str_pad($data->{$this->base_alias}, 2, '0', STR_PAD_LEFT);
  }

}
