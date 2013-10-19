<?php

/**
 * @file
 * Contains \Drupal\Core\Field\ConfigFieldItemInterface.
 */

namespace Drupal\Core\Field;

use Drupal\field\FieldInterface;

/**
 * Interface definition for 'configurable field type' plugins.
 */
interface ConfigFieldItemInterface extends FieldItemInterface {

  /**
   * Returns the schema for the field.
   *
   * This method is static, because the field schema information is needed on
   * creation of the field. No field instances exist by then, and it is not
   * possible to instantiate a FieldItemInterface object yet.
   *
   * @param \Drupal\field\FieldInterface $field
   *   The field definition.
   *
   * @return array
   *   An associative array with the following key/value pairs:
   *   - columns: An array of Schema API column specifications, keyed by column
   *     name. This specifies what comprises a value for a given field. For
   *     example, a value for a number field is simply 'value', while a value
   *     for a formatted text field is the combination of 'value' and 'format'.
   *     It is recommended to avoid having the column definitions depend on
   *     field settings when possible. No assumptions should be made on how
   *     storage engines internally use the original column name to structure
   *     their storage.
   *   - indexes: (optional) An array of Schema API index definitions. Only
   *     columns that appear in the 'columns' array are allowed. Those indexes
   *     will be used as default indexes. Callers of field_create_field() can
   *     specify additional indexes or, at their own risk, modify the default
   *     indexes specified by the field-type module. Some storage engines might
   *     not support indexes.
   *   - foreign keys: (optional) An array of Schema API foreign key
   *     definitions. Note, however, that the field data is not necessarily
   *     stored in SQL. Also, the possible usage is limited, as you cannot
   *     specify another field as related, only existing SQL tables,
   *     such as {taxonomy_term_data}.
   */
  public static function schema(FieldInterface $field);

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
