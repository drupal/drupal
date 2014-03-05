<?php

/**
 * @addtogroup hooks
 * @{
 */

use Drupal\Component\Utility\NestedArray;
use Drupal\field\FieldConfigUpdateForbiddenException;

/**
 * Exposes "pseudo-field" components on fieldable entities.
 *
 * Field UI's "Manage fields" and "Manage display" pages let users re-order
 * fields, but also non-field components. For nodes, these include the title
 * and other elements exposed by modules through hook_form_alter().
 *
 * Fieldable entities or modules that want to have their components supported
 * should expose them using this hook. The user-defined settings (weight,
 * visible) are automatically applied on rendered forms and displayed entities
 * in a #pre_render callback added by field_attach_form() and
 * EntityViewBuilder::viewMultiple().
 *
 * @see hook_field_extra_fields_alter()
 *
 * @return array
 *   A nested array of 'pseudo-field' elements. Each list is nested within the
 *   following keys: entity type, bundle name, context (either 'form' or
 *   'display'). The keys are the name of the elements as appearing in the
 *   renderable array (either the entity form or the displayed entity). The
 *   value is an associative array:
 *   - label: The human readable name of the element. Make sure you sanitize
 *     this appropriately.
 *   - description: A short description of the element contents.
 *   - weight: The default weight of the element.
 *   - visible: (optional) The default visibility of the element. Defaults to
 *     TRUE.
 *   - edit: (optional) String containing markup (normally a link) used as the
 *     element's 'edit' operation in the administration interface. Only for
 *     'form' context.
 *   - delete: (optional) String containing markup (normally a link) used as the
 *     element's 'delete' operation in the administration interface. Only for
 *     'form' context.
 */
function hook_field_extra_fields() {
  $extra = array();
  $module_language_enabled = \Drupal::moduleHandler()->moduleExists('language');
  $description = t('Node module element');

  foreach (node_type_get_types() as $bundle) {
    if ($bundle->has_title) {
      $extra['node'][$bundle->type]['form']['title'] = array(
        'label' => check_plain($bundle->title_label),
        'description' => $description,
        'weight' => -5,
      );
    }

    // Add also the 'language' select if Language module is enabled and the
    // bundle has multilingual support.
    // Visibility of the ordering of the language selector is the same as on the
    // node/add form.
    if ($module_language_enabled) {
      $configuration = language_get_default_configuration('node', $bundle->type);
      if ($configuration['language_show']) {
        $extra['node'][$bundle->type]['form']['language'] = array(
          'label' => t('Language'),
          'description' => $description,
          'weight' => 0,
        );
      }
    }
    $extra['node'][$bundle->type]['display']['language'] = array(
      'label' => t('Language'),
      'description' => $description,
      'weight' => 0,
      'visible' => FALSE,
    );
  }

  return $extra;
}

/**
 * Alter "pseudo-field" components on fieldable entities.
 *
 * @param $info
 *   The associative array of 'pseudo-field' components.
 *
 * @see hook_field_extra_fields()
 */
function hook_field_extra_fields_alter(&$info) {
  // Force node title to always be at the top of the list by default.
  foreach (node_type_get_types() as $bundle) {
    if (isset($info['node'][$bundle->type]['form']['title'])) {
      $info['node'][$bundle->type]['form']['title']['weight'] = -20;
    }
  }
}

/**
 * @defgroup field_types Field Types API
 * @{
 * Defines field, widget, display formatter, and storage types.
 *
 * In the Field API, each field has a type, which determines what kind of data
 * (integer, string, date, etc.) the field can hold, which settings it provides,
 * and so on. The data type(s) accepted by a field are defined in
 * hook_field_schema().
 *
 * The Field Types API also defines two kinds of pluggable handlers: widgets
 * and formatters. @link field_widget Widgets @endlink specify how the field
 * appears in edit forms, while @link field_formatter formatters @endlink
 * specify how the field appears in displayed entities.
 *
 * See @link field Field API @endlink for information about the other parts of
 * the Field API.
 */


/**
 * Perform alterations on Field API field types.
 *
 * @param $info
 *   Array of information on field types as collected by the "field type" plugin
 *   manager.
 */
function hook_field_info_alter(&$info) {
  // Add a setting to all field types.
  foreach ($info as $field_type => $field_type_info) {
    $info[$field_type]['settings'] += array(
      'mymodule_additional_setting' => 'default value',
    );
  }

  // Change the default widget for fields of type 'foo'.
  if (isset($info['foo'])) {
    $info['foo']['default widget'] = 'mymodule_widget';
  }
}

/**
 * @} End of "defgroup field_types".
 */

/**
 * @defgroup field_widget Field Widget API
 * @{
 * Define Field API widget types.
 *
 * Field API widgets specify how fields are displayed in edit forms. Fields of a
 * given @link field_types field type @endlink may be edited using more than one
 * widget. In this case, the Field UI module allows the site builder to choose
 * which widget to use.
 *
 * Widgets are Plugins managed by the
 * Drupal\Core\Field\WidgetPluginManager class. A widget is
 * implemented by providing a class that implements
 * Drupal\Core\Field\WidgetInterface (in most cases, by
 * subclassing Drupal\Core\Field\WidgetBase), and provides the
 * proper annotation block.
 *
 * Widgets are @link forms_api_reference.html Form API @endlink
 * elements with additional processing capabilities. The methods of the
 * WidgetInterface object are typically called by the Field Attach API during
 * the creation of the field form structure with field_attach_form().
 *
 * @see field
 * @see field_types
 * @see field_formatter
 */

/**
 * Perform alterations on Field API widget types.
 *
 * @param array $info
 *   An array of informations on existing widget types, as collected by the
 *   annotation discovery mechanism.
 */
function hook_field_widget_info_alter(array &$info) {
  // Add a setting to a widget type.
  $info['text_textfield']['settings'] += array(
    'mymodule_additional_setting' => 'default value',
  );

  // Let a new field type re-use an existing widget.
  $info['options_select']['field_types'][] = 'my_field_type';
}

/**
 * Alter forms for field widgets provided by other modules.
 *
 * @param $element
 *   The field widget form element as constructed by hook_field_widget_form().
 * @param $form_state
 *   An associative array containing the current state of the form.
 * @param $context
 *   An associative array containing the following key-value pairs:
 *   - form: The form structure to which widgets are being attached. This may be
 *     a full form structure, or a sub-element of a larger form.
 *   - widget: The widget plugin instance.
 *   - items: The field values, as a
 *     \Drupal\Core\Field\FieldItemListInterface object.
 *   - delta: The order of this item in the array of subelements (0, 1, 2, etc).
 *   - default: A boolean indicating whether the form is being shown as a dummy
 *     form to set default values.
 *
 * @see \Drupal\Core\Field\WidgetBase::formSingleElement()
 * @see hook_field_widget_WIDGET_TYPE_form_alter()
 */
function hook_field_widget_form_alter(&$element, &$form_state, $context) {
  // Add a css class to widget form elements for all fields of type mytype.
  $field_definition = $context['items']->getFieldDefinition();
  if ($field_definition->getType() == 'mytype') {
    // Be sure not to overwrite existing attributes.
    $element['#attributes']['class'][] = 'myclass';
  }
}

/**
 * Alter widget forms for a specific widget provided by another module.
 *
 * Modules can implement hook_field_widget_WIDGET_TYPE_form_alter() to modify a
 * specific widget form, rather than using hook_field_widget_form_alter() and
 * checking the widget type.
 *
 * @param $element
 *   The field widget form element as constructed by hook_field_widget_form().
 * @param $form_state
 *   An associative array containing the current state of the form.
 * @param $context
 *   An associative array. See hook_field_widget_form_alter() for the structure
 *   and content of the array.
 *
 * @see \Drupal\Core\Field\WidgetBase::formSingleElement()
 * @see hook_field_widget_form_alter()
 */
function hook_field_widget_WIDGET_TYPE_form_alter(&$element, &$form_state, $context) {
  // Code here will only act on widgets of type WIDGET_TYPE.  For example,
  // hook_field_widget_mymodule_autocomplete_form_alter() will only act on
  // widgets of type 'mymodule_autocomplete'.
  $element['#autocomplete_route_name'] = 'mymodule.autocomplete_route';
}

/**
 * @} End of "defgroup field_widget".
 */


/**
 * @defgroup field_formatter Field Formatter API
 * @{
 * Define Field API formatter types.
 *
 * Field API formatters specify how fields are displayed when the entity to
 * which the field is attached is displayed. Fields of a given
 * @link field_types field type @endlink may be displayed using more than one
 * formatter. In this case, the Field UI module allows the site builder to
 * choose which formatter to use.
 *
 * Formatters are Plugins managed by the
 * Drupal\Core\Field\FormatterPluginManager class. A formatter
 * is implemented by providing a class that implements
 * Drupal\Core\Field\FormatterInterface (in most cases, by
 * subclassing Drupal\Core\Field\FormatterBase), and provides
 * the proper annotation block.
 *
 * @see field
 * @see field_types
 * @see field_widget
 */

/**
 * Perform alterations on Field API formatter types.
 *
 * @param array $info
 *   An array of informations on existing formatter types, as collected by the
 *   annotation discovery mechanism.
 */
function hook_field_formatter_info_alter(array &$info) {
  // Add a setting to a formatter type.
  $info['text_default']['settings'] += array(
    'mymodule_additional_setting' => 'default value',
  );

  // Let a new field type re-use an existing formatter.
  $info['text_default']['field types'][] = 'my_field_type';
}

/**
 * @} End of "defgroup field_formatter".
 */

/**
 * @addtogroup field_attach
 * @{
 */

/**
 * Act on field_attach_form().
 *
 * This hook is invoked after the field module has performed the operation.
 * Implementing modules should alter the $form or $form_state parameters.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity for which an edit form is being built.
 * @param $form
 *   The form structure field elements are attached to. This might be a full
 *   form structure, or a sub-element of a larger form. The $form['#parents']
 *   property can be used to identify the corresponding part of
 *   $form_state['values']. Hook implementations that need to act on the
 *   top-level properties of the global form (like #submit, #validate...) can
 *   add a #process callback to the array received in the $form parameter, and
 *   act on the $complete_form parameter in the process callback.
 * @param $form_state
 *   An associative array containing the current state of the form.
 * @param $langcode
 *   The language the field values are going to be entered in. If no language is
 *   provided the default site language will be used.
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use the entity system instead, see https://drupal.org/developing/api/entity
 */
function hook_field_attach_form(\Drupal\Core\Entity\EntityInterface $entity, &$form, &$form_state, $langcode) {
  // Add a checkbox allowing a given field to be emptied.
  // See hook_field_attach_extract_form_values() for the corresponding
  // processing code.
  $form['empty_field_foo'] = array(
    '#type' => 'checkbox',
    '#title' => t("Empty the 'field_foo' field"),
  );
}

/**
 * Act on field_attach_extract_form_values().
 *
 * This hook is invoked after the field module has performed the operation.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity for which an edit form is being submitted. The incoming form
 *   values have been extracted as field values of the $entity object.
 * @param $form
 *   The form structure field elements are attached to. This might be a full
 *   form structure, or a sub-part of a larger form. The $form['#parents']
 *   property can be used to identify the corresponding part of
 *   $form_state['values'].
 * @param $form_state
 *   An associative array containing the current state of the form.
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use the entity system instead, see https://drupal.org/developing/api/entity
 */
function hook_field_attach_extract_form_values(\Drupal\Core\Entity\EntityInterface $entity, $form, &$form_state) {
  // Sample case of an 'Empty the field' checkbox added on the form, allowing
  // a given field to be emptied.
  $values = NestedArray::getValue($form_state['values'], $form['#parents']);
  if (!empty($values['empty_field_foo'])) {
    unset($entity->field_foo);
  }
}

/**
 * @} End of "addtogroup field_attach".
 */

/**
 * Returns the maximum weight for the entity components handled by the module.
 *
 * Field API takes care of fields and 'extra_fields'. This hook is intended for
 * third-party modules adding other entity components (e.g. field_group).
 *
 * @param string $entity_type
 *   The type of entity; e.g. 'node' or 'user'.
 * @param string $bundle
 *   The bundle name.
 * @param string $context
 *   The context for which the maximum weight is requested. Either 'form' or
 *   'display'.
 * @param string $context_mode
 *   The view or form mode name.
 *
 * @return int
 *   The maximum weight of the entity's components, or NULL if no components
 *   were found.
 */
function hook_field_info_max_weight($entity_type, $bundle, $context, $context_mode) {
  $weights = array();

  foreach (my_module_entity_additions($entity_type, $bundle, $context, $context_mode) as $addition) {
    $weights[] = $addition['weight'];
  }

  return $weights ? max($weights) : NULL;
}

/**
 * @addtogroup field_crud
 * @{
 */

/**
 * Forbid a field update from occurring.
 *
 * Any module may forbid any update for any reason. For example, the
 * field's storage module might forbid an update if it would change
 * the storage schema while data for the field exists. A field type
 * module might forbid an update if it would change existing data's
 * semantics, or if there are external dependencies on field settings
 * that cannot be updated.
 *
 * To forbid the update from occurring, throw a
 * Drupal\field\FieldConfigUpdateForbiddenException.
 *
 * @param \Drupal\field\FieldConfigInterface $field
 *   The field as it will be post-update.
 * @param \Drupal\field\FieldConfigInterface $prior_field
 *   The field as it is pre-update.
 */
function hook_field_config_update_forbid(\Drupal\field\FieldConfigInterface $field, \Drupal\field\FieldConfigInterface $prior_field) {
  // A 'list' field stores integer keys mapped to display values. If
  // the new field will have fewer values, and any data exists for the
  // abandoned keys, the field will have no way to display them. So,
  // forbid such an update.
  if ($field->hasData() && count($field['settings']['allowed_values']) < count($prior_field['settings']['allowed_values'])) {
    // Identify the keys that will be lost.
    $lost_keys = array_diff(array_keys($field['settings']['allowed_values']), array_keys($prior_field['settings']['allowed_values']));
    // If any data exist for those keys, forbid the update.
    $query = new EntityFieldQuery();
    $found = $query
      ->fieldCondition($prior_field['field_name'], 'value', $lost_keys)
      ->range(0, 1)
      ->execute();
    if ($found) {
      throw new FieldConfigUpdateForbiddenException("Cannot update a list field not to include keys with existing data");
    }
  }
}

/**
 * Acts when a field record is being purged.
 *
 * In field_purge_field(), after the field definition has been removed from the
 * the system, the entity storage has purged stored field data, and the field
 * info cache has been cleared, this hook is invoked on all modules to allow
 * them to respond to the field being purged.
 *
 * @param $field
 *   The field being purged.
 */
function hook_field_purge_field($field) {
  db_delete('my_module_field_info')
    ->condition('id', $field['id'])
    ->execute();
}

/**
 * Acts when a field instance is being purged.
 *
 * In field_purge_instance(), after the instance definition has been removed
 * from the the system, the entity storage has purged stored field data, and the
 * field info cache has been cleared, this hook is invoked on all modules to
 * allow them to respond to the field instance being purged.
 *
 * @param $instance
 *   The instance being purged.
 */
function hook_field_purge_instance($instance) {
  db_delete('my_module_field_instance_info')
    ->condition('id', $instance['id'])
    ->execute();
}

/**
 * @} End of "addtogroup field_crud".
 */

/**
 * @} End of "addtogroup hooks".
 */
