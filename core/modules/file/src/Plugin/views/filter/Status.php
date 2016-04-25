<?php

namespace Drupal\file\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\InOperator;

/**
 * Filter by file status.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("file_status")
 */
class Status extends InOperator {

  public function getValueOptions() {
    if (!isset($this->valueOptions)) {
      $this->valueOptions = _views_file_status();
    }
    return $this->valueOptions;
  }

}
