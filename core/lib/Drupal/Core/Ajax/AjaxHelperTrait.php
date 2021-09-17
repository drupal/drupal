<?php

namespace Drupal\Core\Ajax;

use Drupal\Core\EventSubscriber\MainContentViewSubscriber;

/**
 * Provides a helper to determine if the current request is via AJAX.
 *
 * @internal
 */
trait AjaxHelperTrait {

  /**
   * Determines if the current request is via AJAX.
   *
   * @return bool
   *   TRUE if the current request is via AJAX, FALSE otherwise.
   */
  protected function isAjax() {
    $wrapper_format = $this->getRequestWrapperFormat() ?? '';
    return str_contains($wrapper_format, 'drupal_ajax') ||
      str_contains($wrapper_format, 'drupal_modal') ||
      str_contains($wrapper_format, 'drupal_dialog');
  }

  /**
   * Gets the wrapper format of the current request.
   *
   * @return string|null
   *   The wrapper format. NULL if the wrapper format is not set.
   */
  protected function getRequestWrapperFormat() {
    return \Drupal::request()->get(MainContentViewSubscriber::WRAPPER_FORMAT);
  }

}
