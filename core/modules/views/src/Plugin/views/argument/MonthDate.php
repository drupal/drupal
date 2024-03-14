<?php

namespace Drupal\views\Plugin\views\argument;

use Drupal\views\Attribute\ViewsArgument;

/**
 * Argument handler for a month (MM)
 */
#[ViewsArgument(
  id: 'date_month',
)]
class MonthDate extends Date {

  /**
   * {@inheritdoc}
   */
  protected $format = 'F';

  /**
   * {@inheritdoc}
   */
  protected $argFormat = 'm';

  /**
   * {@inheritdoc}
   */
  public function summaryName($data) {
    $month = str_pad($data->{$this->name_alias}, 2, '0', STR_PAD_LEFT);
    try {
      return $this->dateFormatter->format(strtotime("2005" . $month . "15" . " 00:00:00 UTC"), 'custom', $this->format, 'UTC');
    }
    catch (\InvalidArgumentException $e) {
      return parent::summaryName($data);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function title() {
    $month = str_pad($this->argument, 2, '0', STR_PAD_LEFT);
    try {
      return $this->dateFormatter->format(strtotime("2005" . $month . "15" . " 00:00:00 UTC"), 'custom', $this->format, 'UTC');
    }
    catch (\InvalidArgumentException $e) {
      return parent::title();
    }
  }

  public function summaryArgument($data) {
    // Make sure the argument contains leading zeroes.
    return str_pad($data->{$this->base_alias}, 2, '0', STR_PAD_LEFT);
  }

}
