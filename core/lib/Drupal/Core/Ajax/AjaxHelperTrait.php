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
    foreach (['drupal_ajax', 'drupal_modal', 'drupal_dialog'] as $wrapper) {
      if (strpos($this->getRequestWrapperFormat(), $wrapper) !== FALSE) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Gets the wrapper format of the current request.
   *
   * @string
   *   The wrapper format.
   */
  protected function getRequestWrapperFormat() {
    return \Drupal::request()->get(MainContentViewSubscriber::WRAPPER_FORMAT);
  }

}
