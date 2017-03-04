<?php

namespace Drupal\views;

/**
 * Caches exposed forms, as they are heavy to generate.
 *
 * @see \Drupal\views\Form\ViewsExposedForm
 */
class ExposedFormCache {

  /**
   * Stores the exposed form data.
   *
   * @var array
   */
  protected $cache = [];

  /**
   * Save the Views exposed form for later use.
   *
   * @param string $view_id
   *   The views ID.
   * @param string $display_id
   *   The current view display name.
   * @param array $form_output
   *   The form structure. Only needed when inserting the value.
   */
  public function setForm($view_id, $display_id, array $form_output) {
    // Save the form output.
    $views_exposed[$view_id][$display_id] = $form_output;
  }

  /**
   * Retrieves the views exposed form from cache.
   *
   * @param string $view_id
   *   The views ID.
   * @param string $display_id
   *   The current view display name.
   *
   * @return array|bool
   *   The form structure, if any, otherwise FALSE.
   */
  public function getForm($view_id, $display_id) {
    // Return the form output, if any.
    if (empty($this->cache[$view_id][$display_id])) {
      return FALSE;
    }
    else {
      return $this->cache[$view_id][$display_id];
    }
  }

  /**
   * Rests the form cache.
   */
  public function reset() {
    $this->cache = [];
  }

}
