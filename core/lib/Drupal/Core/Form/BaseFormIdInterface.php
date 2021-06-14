<?php

namespace Drupal\Core\Form;

/**
 * Provides an interface for a Form that has a base form ID.
 *
 * This will become the $form_state->getBuildInfo()['base_form_id'] used to
 * generate the name of hook_form_BASE_FORM_ID_alter().
 */
interface BaseFormIdInterface extends FormInterface {

  /**
   * Returns a string identifying the base form.
   *
   * @return string|null
   *   The string identifying the base form or NULL if this is not a base form.
   */
  public function getBaseFormId();

}
