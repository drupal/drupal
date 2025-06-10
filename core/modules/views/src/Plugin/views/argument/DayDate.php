<?php

namespace Drupal\views\Plugin\views\argument;

use Drupal\views\Attribute\ViewsArgument;

/**
 * Argument handler for a day (DD)
  */
#[ViewsArgument(
  id: 'date_day',
)]
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
   * {@inheritdoc}
   */
  public function summaryName($data) {
    $day = str_pad($data->{$this->name_alias}, 2, '0', STR_PAD_LEFT);
    // strtotime() respects server timezone, so we need to set the time fixed
    // as utc time
    return $this->dateFormatter->format(strtotime("200505" . $day . " 00:00:00 UTC"), 'custom', $this->format, 'UTC');
  }

  /**
   * {@inheritdoc}
   */
  public function title() {
    $day = str_pad($this->argument, 2, '0', STR_PAD_LEFT);
    return $this->dateFormatter->format(strtotime("200505" . $day . " 00:00:00 UTC"), 'custom', $this->format, 'UTC');
  }

  /**
   * {@inheritdoc}
   */
  public function summaryArgument($data) {
    // Make sure the argument contains leading zeroes.
    return str_pad($data->{$this->base_alias}, 2, '0', STR_PAD_LEFT);
  }

}
