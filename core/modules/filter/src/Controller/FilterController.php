<?php

namespace Drupal\filter\Controller;

use Drupal\filter\FilterFormatInterface;

/**
 * Controller routines for filter routes.
 */
class FilterController {

  /**
   * Gets the label of a filter format.
   *
   * @param \Drupal\filter\FilterFormatInterface $filter_format
   *   The filter format.
   *
   * @return string
   *   The label of the filter format.
   */
  public function getLabel(FilterFormatInterface $filter_format) {
    return $filter_format->label();
  }

}
