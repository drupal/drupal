<?php

namespace Drupal\views\Plugin\views\argument;

/**
 * Argument handler for a full date (CCYYMMDD)
 *
 * @ViewsArgument("date_fulldate")
 */
class FullDate extends Date {

  /**
   * {@inheritdoc}
   */
  protected $format = 'F j, Y';

  /**
   * {@inheritdoc}
   */
  protected $argFormat = 'Ymd';

  /**
   * {@inheritdoc}
   */
  public function summaryName($data) {
    $created = $data->{$this->name_alias};
    return $this->dateFormatter->format(strtotime($created . " 00:00:00 UTC"), 'custom', $this->format, 'UTC');
  }

  /**
   * {@inheritdoc}
   */
  public function title() {
    return $this->dateFormatter->format(strtotime($this->argument . " 00:00:00 UTC"), 'custom', $this->format, 'UTC');
  }

}
