<?php

namespace Drupal\Core\Form;

/**
 * Provides an interface for the caching of a form and its form state.
 */
interface FormCacheInterface {

  /**
   * Fetches a form from the cache.
   *
   * @param string $form_build_id
   *   The unique form build ID.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function getCache($form_build_id, FormStateInterface $form_state);

  /**
   * Stores a form in the cache.
   *
   * @param string $form_build_id
   *   The unique form build ID.
   * @param array $form
   *   The form to cache.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function setCache($form_build_id, $form, FormStateInterface $form_state);

  /**
   * Deletes a form in the cache.
   *
   * @param string $form_build_id
   *   The unique form build ID.
   */
  public function deleteCache($form_build_id);

}
