<?php

/**
 * @file
 * Field API documentation.
 */

/**
 * @addtogroup hooks
 * @{
 */

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
 * Field types are plugins annotated with class
 * \Drupal\Core\Field\Annotation\FieldType, and implement plugin interface
 * \Drupal\Core\Field\FieldItemInterface. Field Type plugins are managed by the
 * \Drupal\Core\Field\FieldTypePluginManager class. Field type classes usually
 * extend base class \Drupal\Core\Field\FieldItemBase. Field-type plugins need
 * to be in the namespace \Drupal\{your_module}\Plugin\Field\FieldType. See the
 * @link plugin_api Plugin API topic @endlink for more information on how to
 * define plugins.
 *
 * The Field Types API also defines two kinds of pluggable handlers: widgets
 * and formatters. @link field_widget Widgets @endlink specify how the field
 * appears in edit forms, while @link field_formatter formatters @endlink
 * specify how the field appears in displayed entities.
 *
 * See @link field Field API @endlink for information about the other parts of
 * the Field API.
 *
 * @see field
 * @see field_widget
 * @see field_formatter
 * @see plugin_api
 */

/**
 * Perform alterations on Field API field types.
 *
 * @param $info
 *   Array of information on field types as collected by the "field type" plugin
 *   manager.
 */
function hook_field_info_alter(&$info) {
  // Change the default widget for fields of type 'foo'.
  if (isset($info['foo'])) {
    $info['foo']['default widget'] = 'mymodule_widget';
  }
}

/**
 * Perform alterations on preconfigured field options.
 *
 * @param array $options
 *   Array of options as returned from
 *   \Drupal\Core\Field\PreconfiguredFieldUiOptionsInterface::getPreconfiguredOptions().
 * @param string $field_type
 *   The field type plugin ID.
 *
 * @see \Drupal\Core\Field\PreconfiguredFieldUiOptionsInterface::getPreconfiguredOptions()
 */
function hook_field_ui_preconfigured_options_alter(array &$options, $field_type) {
  // If the field is not an "entity_reference"-based field, bail out.
  /** @var \Drupal\Core\Field\FieldTypePluginManager $field_type_manager */
  $field_type_manager = \Drupal::service('plugin.manager.field.field_type');
  $class = $field_type_manager->getPluginClass($field_type);
  if (!is_a($class, 'Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem', TRUE)) {
    return;
  }

  // Set the default formatter for media in entity reference fields to be the
  // "Rendered entity" formatter.
  if (!empty($options['media'])) {
    $options['media']['entity_view_display']['type'] = 'entity_reference_entity_view';
  }
}

/**
 * Forbid a field storage update from occurring.
 *
 * Any module may forbid any update for any reason. For example, the
 * field's storage module might forbid an update if it would change
 * the storage schema while data for the field exists. A field type
 * module might forbid an update if it would change existing data's
 * semantics, or if there are external dependencies on field settings
 * that cannot be updated.
 *
 * To forbid the update from occurring, throw a
 * \Drupal\Core\Entity\Exception\FieldStorageDefinitionUpdateForbiddenException.
 *
 * @param \Drupal\field\FieldStorageConfigInterface $field_storage
 *   The field storage as it will be post-update.
 * @param \Drupal\field\FieldStorageConfigInterface $prior_field_storage
 *   The field storage as it is pre-update.
 *
 * @see entity_crud
 */
function hook_field_storage_config_update_forbid(\Drupal\field\FieldStorageConfigInterface $field_storage, \Drupal\field\FieldStorageConfigInterface $prior_field_storage) {
  if ($field_storage->module == 'options' && $field_storage->hasData()) {
    // Forbid any update that removes allowed values with actual data.
    $allowed_values = $field_storage->getSetting('allowed_values');
    $prior_allowed_values = $prior_field_storage->getSetting('allowed_values');
    $lost_keys = array_keys(array_diff_key($prior_allowed_values, $allowed_values));
    if (_options_values_in_use($field_storage->getTargetEntityTypeId(), $field_storage->getName(), $lost_keys)) {
      throw new \Drupal\Core\Entity\Exception\FieldStorageDefinitionUpdateForbiddenException(t('A list field (@field_name) with existing data cannot have its keys changed.', ['@field_name' => $field_storage->getName()]));
    }
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
 * \Drupal\Core\Field\WidgetPluginManager class. A widget is a plugin annotated
 * with class \Drupal\Core\Field\Annotation\FieldWidget that implements
 * \Drupal\Core\Field\WidgetInterface (in most cases, by
 * subclassing \Drupal\Core\Field\WidgetBase). Widget plugins need to be in the
 * namespace \Drupal\{your_module}\Plugin\Field\FieldWidget.
 *
 * Widgets are @link form_api Form API @endlink elements with additional
 * processing capabilities. The methods of the WidgetInterface object are
 * typically called by respective methods in the
 * \Drupal\Core\Entity\Entity\EntityFormDisplay class.
 *
 * @see field
 * @see field_types
 * @see field_formatter
 * @see plugin_api
 */

/**
 * Perform alterations on Field API widget types.
 *
 * @param array $info
 *   An array of information on existing widget types, as collected by the
 *   annotation discovery mechanism.
 */
function hook_field_widget_info_alter(array &$info) {
  // Let a new field type re-use an existing widget.
  $info['options_select']['field_types'][] = 'my_field_type';
}

/**
 * Alter forms for field widgets provided by other modules.
 *
 * This hook can only modify individual elements within a field widget and
 * cannot alter the top level (parent element) for multi-value fields. In most
 * cases, you should use hook_field_widget_multivalue_form_alter() instead and
 * loop over the elements.
 *
 * @param $element
 *   The field widget form element as constructed by
 *   \Drupal\Core\Field\WidgetBaseInterface::form().
 * @param $form_state
 *   The current state of the form.
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
 * @see \Drupal\Core\Field\WidgetBaseInterface::form()
 * @see \Drupal\Core\Field\WidgetBase::formSingleElement()
 * @see hook_field_widget_WIDGET_TYPE_form_alter()
 * @see hook_field_widget_multivalue_form_alter()
 */
function hook_field_widget_form_alter(&$element, \Drupal\Core\Form\FormStateInterface $form_state, $context) {
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
 * This hook can only modify individual elements within a field widget and
 * cannot alter the top level (parent element) for multi-value fields. In most
 * cases, you should use hook_field_widget_multivalue_WIDGET_TYPE_form_alter()
 * instead and loop over the elements.
 *
 * @param $element
 *   The field widget form element as constructed by
 *   \Drupal\Core\Field\WidgetBaseInterface::form().
 * @param $form_state
 *   The current state of the form.
 * @param $context
 *   An associative array. See hook_field_widget_form_alter() for the structure
 *   and content of the array.
 *
 * @see \Drupal\Core\Field\WidgetBaseInterface::form()
 * @see \Drupal\Core\Field\WidgetBase::formSingleElement()
 * @see hook_field_widget_form_alter()
 * @see hook_field_widget_multivalue_WIDGET_TYPE_form_alter()
 */
function hook_field_widget_WIDGET_TYPE_form_alter(&$element, \Drupal\Core\Form\FormStateInterface $form_state, $context) {
  // Code here will only act on widgets of type WIDGET_TYPE.  For example,
  // hook_field_widget_mymodule_autocomplete_form_alter() will only act on
  // widgets of type 'mymodule_autocomplete'.
  $element['#autocomplete_route_name'] = 'mymodule.autocomplete_route';
}

/**
 * Alter forms for multi-value field widgets provided by other modules.
 *
 * To alter the individual elements within the widget, loop over
 * \Drupal\Core\Render\Element::children($elements).
 *
 * @param array $elements
 *   The field widget form elements as constructed by
 *   \Drupal\Core\Field\WidgetBase::formMultipleElements().
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The current state of the form.
 * @param array $context
 *   An associative array containing the following key-value pairs:
 *   - form: The form structure to which widgets are being attached. This may be
 *     a full form structure, or a sub-element of a larger form.
 *   - widget: The widget plugin instance.
 *   - items: The field values, as a
 *     \Drupal\Core\Field\FieldItemListInterface object.
 *   - default: A boolean indicating whether the form is being shown as a dummy
 *     form to set default values.
 *
 * @see \Drupal\Core\Field\WidgetBaseInterface::form()
 * @see \Drupal\Core\Field\WidgetBase::formMultipleElements()
 * @see hook_field_widget_multivalue_WIDGET_TYPE_form_alter()
 */
function hook_field_widget_multivalue_form_alter(array &$elements, \Drupal\Core\Form\FormStateInterface $form_state, array $context) {
  // Add a css class to widget form elements for all fields of type mytype.
  $field_definition = $context['items']->getFieldDefinition();
  if ($field_definition->getType() == 'mytype') {
    // Be sure not to overwrite existing attributes.
    $elements['#attributes']['class'][] = 'myclass';
  }
}

/**
 * Alter multi-value widget forms for a widget provided by another module.
 *
 * Modules can implement hook_field_widget_multivalue_WIDGET_TYPE_form_alter() to
 * modify a specific widget form, rather than using
 * hook_field_widget_form_alter() and checking the widget type.
 *
 * To alter the individual elements within the widget, loop over
 * \Drupal\Core\Render\Element::children($elements).
 *
 * @param array $elements
 *   The field widget form elements as constructed by
 *   \Drupal\Core\Field\WidgetBase::formMultipleElements().
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The current state of the form.
 * @param array $context
 *   An associative array. See hook_field_widget_multivalue_form_alter() for
 *   the structure and content of the array.
 *
 * @see \Drupal\Core\Field\WidgetBaseInterface::form()
 * @see \Drupal\Core\Field\WidgetBase::formMultipleElements()
 * @see hook_field_widget_multivalue_form_alter()
 */
function hook_field_widget_multivalue_WIDGET_TYPE_form_alter(array &$elements, \Drupal\Core\Form\FormStateInterface $form_state, array $context) {
  // Code here will only act on widgets of type WIDGET_TYPE. For example,
  // hook_field_widget_multivalue_mymodule_autocomplete_form_alter() will only
  // act on widgets of type 'mymodule_autocomplete'.
  // Change the autocomplete route for each autocomplete element within the
  // multivalue widget.
  foreach (Element::children($elements) as $delta => $element) {
    $elements[$delta]['#autocomplete_route_name'] = 'mymodule.autocomplete_route';
  }
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
 * \Drupal\Core\Field\FormatterPluginManager class. A formatter is a plugin
 * annotated with class \Drupal\Core\Field\Annotation\FieldFormatter that
 * implements \Drupal\Core\Field\FormatterInterface (in most cases, by
 * subclassing \Drupal\Core\Field\FormatterBase). Formatter plugins need to be
 * in the namespace \Drupal\{your_module}\Plugin\Field\FieldFormatter.
 *
 * @see field
 * @see field_types
 * @see field_widget
 * @see plugin_api
 */

/**
 * Perform alterations on Field API formatter types.
 *
 * @param array $info
 *   An array of information on existing formatter types, as collected by the
 *   annotation discovery mechanism.
 */
function hook_field_formatter_info_alter(array &$info) {
  // Let a new field type re-use an existing formatter.
  $info['text_default']['field_types'][] = 'my_field_type';
}

/**
 * @} End of "defgroup field_formatter".
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
 *
 * @ingroup field_info
 */
function hook_field_info_max_weight($entity_type, $bundle, $context, $context_mode) {
  $weights = [];

  foreach (my_module_entity_additions($entity_type, $bundle, $context, $context_mode) as $addition) {
    $weights[] = $addition['weight'];
  }

  return $weights ? max($weights) : NULL;
}

/**
 * @addtogroup field_purge
 * @{
 */

/**
 * Acts when a field storage definition is being purged.
 *
 * In field_purge_field_storage(), after the storage definition has been removed
 * from the system, the entity storage has purged stored field data, and the
 * field definitions cache has been cleared, this hook is invoked on all modules
 * to allow them to respond to the field storage being purged.
 *
 * @param $field_storage \Drupal\field\Entity\FieldStorageConfig
 *   The field storage being purged.
 */
function hook_field_purge_field_storage(\Drupal\field\Entity\FieldStorageConfig $field_storage) {
  \Drupal::database()->delete('my_module_field_storage_info')
    ->condition('uuid', $field_storage->uuid())
    ->execute();
}

/**
 * Acts when a field is being purged.
 *
 * In field_purge_field(), after the field definition has been removed
 * from the system, the entity storage has purged stored field data, and the
 * field info cache has been cleared, this hook is invoked on all modules to
 * allow them to respond to the field being purged.
 *
 * @param $field
 *   The field being purged.
 */
function hook_field_purge_field(\Drupal\field\Entity\FieldConfig $field) {
  \Drupal::database()->delete('my_module_field_info')
    ->condition('id', $field->id())
    ->execute();
}

/**
 * @} End of "addtogroup field_purge".
 */

/**
 * @} End of "addtogroup hooks".
 */
