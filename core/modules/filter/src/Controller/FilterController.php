<?php

namespace Drupal\filter\Controller;

use Drupal\filter\FilterFormatInterface;

/**
 * Controller routines for filter routes.
 */
class FilterController {

  /**
   * Displays a page with long filter tips.
   *
   * @param \Drupal\filter\FilterFormatInterface|null $filter_format
   *   (optional) A filter format, or NULL to show tips for all formats.
   *   Defaults to NULL.
   *
   * @return array
   *   A renderable array.
   *
   * @see template_preprocess_filter_tips()
   */
  public function filterTips(?FilterFormatInterface $filter_format = NULL) {
    $tips = $filter_format ? $filter_format->id() : -1;

    $build = [
      '#theme' => 'filter_tips',
      '#long' => TRUE,
      '#tips' => _filter_tips($tips, TRUE),
    ];

    return $build;
  }

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
