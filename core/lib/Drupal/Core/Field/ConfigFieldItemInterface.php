<?php

/**
 * @file
 * Contains \Drupal\Core\Field\ConfigFieldItemInterface.
 */

namespace Drupal\Core\Field;

/**
 * Interface definition for 'configurable field type' plugins.
 */
interface ConfigFieldItemInterface extends FieldItemInterface {

  /**
   * Returns a form for the field-level settings.
   *
   * Invoked from \Drupal\field_ui\Form\FieldEditForm to allow administrators to
   * configure field-level settings.
   *
   * Field storage might reject field definition changes that affect the field
   * storage schema if the field already has data. When the $has_data parameter
   * is TRUE, the form should not allow changing the settings that take part in
   * the schema() method. It is recommended to set #access to FALSE on the
   * corresponding elements.
   *
   * @param array $form
   *   The form where the settings form is being included in.
   * @param array $form_state
   *   The form state of the (entire) configuration form.
   * @param bool $has_data
   *   TRUE if the field already has data, FALSE if not.
   *
   * @return
   *   The form definition for the field settings.
   */
  public function settingsForm(array $form, array &$form_state, $has_data);

  /**
   * Returns a form for the instance-level settings.
   *
   * Invoked from \Drupal\field_ui\Form\FieldInstanceEditForm to allow
   * administrators to configure instance-level settings.
   *
   * @param array $form
   *   The form where the settings form is being included in.
   * @param array $form_state
   *   The form state of the (entire) configuration form.
   *
   * @return array
   *   The form definition for the field instance settings.
   */
  public function instanceSettingsForm(array $form, array &$form_state);

}
