<?php

namespace Drupal\views\Ajax;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\views\ViewExecutable;

/**
 * Custom JSON response object for an ajax view response.
 *
 * We use a special response object to be able to fire a proper alter hook.
 */
class ViewAjaxResponse extends AjaxResponse {

  /**
   * The view executed on this ajax request.
   *
   * @var \Drupal\views\ViewExecutable
   */
  protected $view;

  /**
   * Sets the executed view of this response.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The View executed on this ajax request.
   */
  public function setView(ViewExecutable $view) {
    $this->view = $view;
  }

  /**
   * Gets the executed view of this response.
   *
   * @return \Drupal\views\ViewExecutable $view
   *   The View executed on this ajax request.
   */
  public function getView() {
    return $this->view;
  }

}
