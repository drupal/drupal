<?php

namespace Drupal\views\Plugin\views\argument;

use Drupal\views\Attribute\ViewsArgument;

/**
 * Argument handler for a year plus month (CCYYMM)
  */
#[ViewsArgument(
  id: 'date_year_month',
)]
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
   * {@inheritdoc}
   */
  public function summaryName($data) {
    $created = $data->{$this->name_alias};
    return $this->dateFormatter->format(strtotime($created . "15 00:00:00 UTC"), 'custom', $this->format, 'UTC');
  }

  /**
   * {@inheritdoc}
   */
  public function title() {
    return $this->dateFormatter->format(strtotime($this->argument . "15 00:00:00 UTC"), 'custom', $this->format, 'UTC');
  }

}
