<?php

namespace Drupal\file\Plugin\views\filter;

use Drupal\file\FileInterface;
use Drupal\views\Attribute\ViewsFilter;
use Drupal\views\Plugin\views\filter\InOperator;

/**
 * Filter by file status.
 *
 * @ingroup views_filter_handlers
 */
#[ViewsFilter("file_status")]
class Status extends InOperator {

  public function getValueOptions() {
    if (!isset($this->valueOptions)) {
      $this->valueOptions = [
        0 => $this->t('Temporary'),
        FileInterface::STATUS_PERMANENT => $this->t('Permanent'),
      ];
    }
    return $this->valueOptions;
  }

}
