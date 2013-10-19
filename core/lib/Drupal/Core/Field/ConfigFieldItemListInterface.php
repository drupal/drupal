<?php

/**
 * @file
 * Contains \Drupal\Core\Field\ConfigFieldItemListInterface.
 */

namespace Drupal\Core\Field;

/**
 * Interface definition for "configurable fields".
 */
interface ConfigFieldItemListInterface extends FieldItemListInterface {

  /**
   * Returns a form for the default value input.
   *
   * Invoked from \Drupal\field_ui\Form\FieldInstanceEditForm to allow
   * administrators to configure instance-level default value.
   *
   * @param array $form
   *   The form where the settings form is being included in.
   * @param array $form_state
   *   The form state of the (entire) configuration form.
   *
   * @return array
   *   The form definition for the field instance default value.
   */
  public function defaultValuesForm(array &$form, array &$form_state);

  /**
   * Validates the submitted default value.
   *
   * Invoked from \Drupal\field_ui\Form\FieldInstanceEditForm to allow
   * administrators to configure instance-level default value.
   *
   * @param array $element
   *   The default value form element.
   * @param array $form
   *   The form where the settings form is being included in.
   * @param array $form_state
   *   The form state of the (entire) configuration form.
   */
  public function defaultValuesFormValidate(array $element, array &$form, array &$form_state);

  /**
   * Processes the submitted default value.
   *
   * Invoked from \Drupal\field_ui\Form\FieldInstanceEditForm to allow
   * administrators to configure instance-level default value.
   *
   * @param array $element
   *   The default value form element.
   * @param array $form
   *   The form where the settings form is being included in.
   * @param array $form_state
   *   The form state of the (entire) configuration form.
   *
   * @return array
   *   The field instance default value.
   */
  public function defaultValuesFormSubmit(array $element, array &$form, array &$form_state);

}
