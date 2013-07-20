<?php

/**
 * @addtogroup hooks
 * @{
 */

use Drupal\Component\Utility\NestedArray;
use Drupal\field\FieldUpdateForbiddenException;

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
 * field_attach_view().
 *
 * @see hook_field_extra_fields_alter()
 *
 * @return array
 *   A nested array of 'pseudo-field' elements. Each list is nested within the
 *   following keys: entity type, bundle name, context (either 'form' or
 *   'display'). The keys are the name of the elements as appearing in the
 *   renderable array (either the entity form or the displayed entity). The
 *   value is an associative array:
 *   - label: The human readable name of the element.
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
  $module_language_enabled = module_exists('language');
  $description = t('Node module element');

  foreach (node_type_get_types() as $bundle) {
    if ($bundle->has_title) {
      $extra['node'][$bundle->type]['form']['title'] = array(
        'label' => $bundle->title_label,
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
 * A third kind of pluggable handler, storage backends, is defined by the
 * @link field_storage Field Storage API @endlink.
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
 * Drupal\field\Plugin\Type\Widget\WidgetPluginManager class. A widget is
 * implemented by providing a class that implements
 * Drupal\field\Plugin\Type\Widget\WidgetInterface (in most cases, by
 * subclassing Drupal\field\Plugin\Type\Widget\WidgetBase), and provides the
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
 *   - field_definition: The field definition.
 *   - entity: The entity.
 *   - langcode: The language associated with $items.
 *   - items: Array of default values for this field.
 *   - delta: The order of this item in the array of subelements (0, 1, 2, etc).
 *   - default: A boolean indicating whether the form is being shown as a dummy
 *     form to set default values.
 *
 * @see \Drupal\field\Plugin\Type\Widget\WidgetBase::formSingleElement()
 * @see hook_field_widget_WIDGET_TYPE_form_alter()
 */
function hook_field_widget_form_alter(&$element, &$form_state, $context) {
  // Add a css class to widget form elements for all fields of type mytype.
  if ($context['field']['type'] == 'mytype') {
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
 * @see \Drupal\field\Plugin\Type\Widget\WidgetBase::formSingleElement()
 * @see hook_field_widget_form_alter()
 */
function hook_field_widget_WIDGET_TYPE_form_alter(&$element, &$form_state, $context) {
  // Code here will only act on widgets of type WIDGET_TYPE.  For example,
  // hook_field_widget_mymodule_autocomplete_form_alter() will only act on
  // widgets of type 'mymodule_autocomplete'.
  $element['#autocomplete_path'] = 'mymodule/autocomplete_path';
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
 * Drupal\field\Plugin\Type\Formatter\FormatterPluginManager class. A formatter
 * is implemented by providing a class that implements
 * Drupal\field\Plugin\Type\Formatter\FormatterInterface (in most cases, by
 * subclassing Drupal\field\Plugin\Type\Formatter\FormatterBase), and provides
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
 * @deprecated as of Drupal 8.0. Use the entity system instead.
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
 * Act on field_attach_load().
 *
 * This hook is invoked after the field module has performed the operation.
 *
 * Unlike other field_attach hooks, this hook accounts for 'multiple loads'.
 * Instead of the usual $entity parameter, it accepts an array of entities,
 * indexed by entity ID. For performance reasons, information for all available
 * entities should be loaded in a single query where possible.
 *
 * The changes made to the entities' field values get cached by the field cache
 * for subsequent loads.
 *
 * See field_attach_load() for details and arguments.
 *
 * @deprecated as of Drupal 8.0. Use the entity system instead.
 */
function hook_field_attach_load($entity_type, $entities, $age, $options) {
  // @todo Needs function body.
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
 * @deprecated as of Drupal 8.0. Use the entity system instead.
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
 * Alter field_attach_preprocess() variables.
 *
 * This hook is invoked while preprocessing field templates in
 * field_attach_preprocess().
 *
 * @param $variables
 *   The variables array is passed by reference and will be populated with field
 *   values.
 * @param $context
 *   An associative array containing:
 *   - entity: The entity with fields to render.
 *   - element: The structured array containing the values ready for rendering.
 */
function hook_field_attach_preprocess_alter(&$variables, $context) {
  // @todo Needs function body.
}

/**
 * Act on field_purge_data().
 *
 * This hook is invoked in field_purge_data() and allows modules to act on
 * purging data from a single field pseudo-entity. For example, if a module
 * relates data in the field with its own data, it may purge its own data during
 * this process as well.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The pseudo-entity whose field data is being purged.
 * @param $field
 *   The (possibly deleted) field whose data is being purged.
 * @param $instance
 *   The deleted field instance whose data is being purged.
 *
 * @see @link field_purge Field API bulk data deletion @endlink
 * @see field_purge_data()
 */
function hook_field_attach_purge(\Drupal\Core\Entity\EntityInterface $entity, $field, $instance) {
  // find the corresponding data in mymodule and purge it
  if ($entity->entityType() == 'node' && $field->field_name == 'my_field_name') {
    mymodule_remove_mydata($entity->id());
  }
}

/**
 * Perform alterations on field_attach_view() or field_view_field().
 *
 * This hook is invoked after the field module has performed the operation.
 *
 * @param $output
 *   The structured content array tree for all of the entity's fields.
 * @param $context
 *   An associative array containing:
 *   - entity: The entity with fields to render.
 *   - view_mode: View mode; for example, 'full' or 'teaser'.
 *   - display_options: Either a view mode string or an array of display
 *     options. If this hook is being invoked from field_attach_view(), the
 *     'display_options' element is set to the view mode string. If this hook
 *     is being invoked from field_view_field(), this element is set to the
 *     $display_options argument and the view_mode element is set to '_custom'.
 *     See field_view_field() for more information on what its $display_options
 *     argument contains.
 *   - langcode: The language code used for rendering.
 *
 * @deprecated as of Drupal 8.0. Use the entity system instead.
 */
function hook_field_attach_view_alter(&$output, $context) {
  // Append RDF term mappings on displayed taxonomy links.
  foreach (element_children($output) as $field_name) {
    $element = &$output[$field_name];
    if ($element['#field_type'] == 'entity_reference' && $element['#formatter'] == 'entity_reference_label') {
      foreach ($element['#items'] as $delta => $item) {
        $term = $item['entity'];
        if (!empty($term->rdf_mapping['rdftype'])) {
          $element[$delta]['#options']['attributes']['typeof'] = $term->rdf_mapping['rdftype'];
        }
        if (!empty($term->rdf_mapping['name']['predicates'])) {
          $element[$delta]['#options']['attributes']['property'] = $term->rdf_mapping['name']['predicates'];
        }
      }
    }
  }
}

/**
 * Perform alterations on field_language() values.
 *
 * This hook is invoked to alter the array of display language codes for the
 * given entity.
 *
 * @param $display_langcode
 *   A reference to an array of language codes keyed by field name.
 * @param $context
 *   An associative array containing:
 *   - entity: The entity with fields to render.
 *   - langcode: The language code $entity has to be displayed in.
 */
function hook_field_language_alter(&$display_langcode, $context) {
  // Do not apply core language fallback rules if they are disabled or if Locale
  // is not registered as a translation handler.
  if (field_language_fallback_enabled() && field_has_translation_handler($context['entity']->entityType())) {
    field_language_fallback($display_langcode, $context['entity'], $context['langcode']);
  }
}

/**
 * Alter field_available_languages() values.
 *
 * This hook is invoked from field_available_languages() to allow modules to
 * alter the array of available language codes for the given field.
 *
 * @param $langcodes
 *   A reference to an array of language codes to be made available.
 * @param $context
 *   An associative array containing:
 *   - entity_type: The type of the entity the field is attached to.
 *   - field: A field data structure.
 */
function hook_field_available_languages_alter(&$langcodes, $context) {
  // Add an unavailable language code.
  $langcodes[] = 'xx';

  // Remove an available language code.
  $index = array_search('yy', $langcodes);
  unset($langcodes[$index]);
}

/**
 * @} End of "addtogroup field_attach".
 */

/**
 * @addtogroup field_storage
 * @{
 */

/**
 * Expose Field API storage backends.
 *
 * @return
 *   An array describing the storage backends implemented by the module. The
 *   keys are storage backend names. To avoid name clashes, storage backend
 *   names should be prefixed with the name of the module that exposes them. The
 *   values are arrays describing the storage backend, with the following
 *   key/value pairs:
 *   - label: The human-readable name of the storage backend.
 *   - description: A short description for the storage backend.
 *   - settings: An array whose keys are the names of the settings available to
 *     the storage backend, and whose values are the default values of those
 *     settings.
 */
function hook_field_storage_info() {
  return array(
    'field_sql_storage' => array(
      'label' => t('Default SQL storage'),
      'description' => t('Stores fields in the local SQL database, using per-field tables.'),
      'settings' => array(),
    ),
  );
}

/**
 * Perform alterations on Field API storage types.
 *
 * @param $info
 *   Array of informations on storage types exposed by
 *   hook_field_field_storage_info() implementations.
 */
function hook_field_storage_info_alter(&$info) {
  // Add a setting to a storage type.
  $info['field_sql_storage']['settings'] += array(
    'mymodule_additional_setting' => 'default value',
  );
}

/**
 * Reveal the internal details about the storage for a field.
 *
 * For example, an SQL storage module might return the Schema API structure for
 * the table. A key/value storage module might return the server name,
 * authentication credentials, and bin name.
 *
 * Field storage modules are not obligated to implement this hook. Modules that
 * rely on these details must only use them for read operations.
 *
 * @param $field
 *   A field structure.
 *
 * @return
 *   An array of details.
 *    - The first dimension is a store type (sql, solr, etc).
 *    - The second dimension indicates the age of the values in the store
 *      FIELD_LOAD_CURRENT or FIELD_LOAD_REVISION.
 *    - Other dimensions are specific to the field storage module.
 *
 * @see hook_field_storage_details_alter()
 */
function hook_field_storage_details($field) {
  $details = array();

  // Add field columns.
  foreach ((array) $field['columns'] as $column_name => $attributes) {
    $real_name = _field_sql_storage_columnname($field['field_name'], $column_name);
    $columns[$column_name] = $real_name;
  }
  return array(
    'sql' => array(
      FIELD_LOAD_CURRENT => array(
        _field_sql_storage_tablename($field) => $columns,
      ),
      FIELD_LOAD_REVISION => array(
        _field_sql_storage_revision_tablename($field) => $columns,
      ),
    ),
  );
}

/**
 * Perform alterations on Field API storage details.
 *
 * @param $details
 *   An array of storage details for fields as exposed by
 *   hook_field_storage_details() implementations.
 * @param $field
 *   A field structure.
 *
 * @see hook_field_storage_details()
 */
function hook_field_storage_details_alter(&$details, $field) {
  if ($field['field_name'] == 'field_of_interest') {
    $columns = array();
    foreach ((array) $field['columns'] as $column_name => $attributes) {
      $columns[$column_name] = $column_name;
    }
    $details['drupal_variables'] = array(
      FIELD_LOAD_CURRENT => array(
        'moon' => $columns,
      ),
      FIELD_LOAD_REVISION => array(
        'mars' => $columns,
      ),
    );
  }
}

/**
 * Load field data for a set of entities.
 *
 * This hook is invoked from field_attach_load() to ask the field storage module
 * to load field data.
 *
 * Modules implementing this hook should load field values and add them to
 * objects in $entities. Fields with no values should be added as empty arrays.
 *
 * @param $entity_type
 *   The type of entity, such as 'node' or 'user'.
 * @param $entities
 *   The array of entity objects to add fields to, keyed by entity ID.
 * @param $age
 *   FIELD_LOAD_CURRENT to load the most recent revision for all fields, or
 *   FIELD_LOAD_REVISION to load the version indicated by each entity.
 * @param $fields
 *   An array listing the fields to be loaded. The keys of the array are field
 *   UUIDs, and the values of the array are the entity IDs (or revision IDs,
 *   depending on the $age parameter) to add each field to.
 * @param $options
 *   An associative array of additional options, with the following keys:
 *   - deleted: If TRUE, deleted fields should be loaded as well as non-deleted
 *     fields. If unset or FALSE, only non-deleted fields should be loaded.
 */
function hook_field_storage_load($entity_type, $entities, $age, $fields, $options) {
  $load_current = $age == FIELD_LOAD_CURRENT;

  foreach ($fields as $field_id => $ids) {
    // By the time this hook runs, the relevant field definitions have been
    // populated and cached in FieldInfo, so calling field_info_field_by_id()
    // on each field individually is more efficient than loading all fields in
    // memory upfront with field_info_field_by_ids().
    $field = field_info_field_by_id($field_id);
    $field_name = $field['field_name'];
    $table = $load_current ? _field_sql_storage_tablename($field) : _field_sql_storage_revision_tablename($field);

    $query = db_select($table, 't')
      ->fields('t')
      ->condition('entity_type', $entity_type)
      ->condition($load_current ? 'entity_id' : 'revision_id', $ids, 'IN')
      ->condition('langcode', field_available_languages($entity_type, $field), 'IN')
      ->orderBy('delta');

    if (empty($options['deleted'])) {
      $query->condition('deleted', 0);
    }

    $results = $query->execute();

    $delta_count = array();
    foreach ($results as $row) {
      if (!isset($delta_count[$row->entity_id][$row->langcode])) {
        $delta_count[$row->entity_id][$row->langcode] = 0;
      }

      if ($field['cardinality'] == FIELD_CARDINALITY_UNLIMITED || $delta_count[$row->entity_id][$row->langcode] < $field['cardinality']) {
        $item = array();
        // For each column declared by the field, populate the item
        // from the prefixed database column.
        foreach ($field['columns'] as $column => $attributes) {
          $column_name = _field_sql_storage_columnname($field_name, $column);
          $item[$column] = $row->$column_name;
        }

        // Add the item to the field values for the entity.
        $entities[$row->entity_id]->{$field_name}[$row->langcode][] = $item;
        $delta_count[$row->entity_id][$row->langcode]++;
      }
    }
  }
}

/**
 * Write field data for an entity.
 *
 * This hook is invoked from field_attach_insert() and field_attach_update(), to
 * ask the field storage module to save field data.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity on which to operate.
 * @param $op
 *   FIELD_STORAGE_UPDATE when updating an existing entity,
 *   FIELD_STORAGE_INSERT when inserting a new entity.
 * @param $fields
 *   An array listing the fields to be written. The keys and values of the
 *   array are field UUIDs.
 */
function hook_field_storage_write(\Drupal\Core\Entity\EntityInterface $entity, $op, $fields) {
  $id = $entity->id();
  $vid = $entity->getRevisionId();
  $bundle = $entity->bundle();
  if (!isset($vid)) {
    $vid = $id;
  }

  foreach ($fields as $field_id) {
    $field = field_info_field_by_id($field_id);
    $field_name = $field['field_name'];
    $table_name = _field_sql_storage_tablename($field);
    $revision_name = _field_sql_storage_revision_tablename($field);

    $all_langcodes = field_available_languages($entity->entityType(), $field);
    $field_langcodes = array_intersect($all_langcodes, array_keys((array) $entity->$field_name));

    // Delete and insert, rather than update, in case a value was added.
    if ($op == FIELD_STORAGE_UPDATE) {
      // Delete language codes present in the incoming $entity->$field_name.
      // Delete all language codes if $entity->$field_name is empty.
      $langcodes = !empty($entity->$field_name) ? $field_langcodes : $all_langcodes;
      if ($langcodes) {
        db_delete($table_name)
          ->condition('entity_type', $entity->entityType())
          ->condition('entity_id', $id)
          ->condition('langcode', $langcodes, 'IN')
          ->execute();
        db_delete($revision_name)
          ->condition('entity_type', $entity->entityType())
          ->condition('entity_id', $id)
          ->condition('revision_id', $vid)
          ->condition('langcode', $langcodes, 'IN')
          ->execute();
      }
    }

    // Prepare the multi-insert query.
    $do_insert = FALSE;
    $columns = array('entity_type', 'entity_id', 'revision_id', 'bundle', 'delta', 'langcode');
    foreach ($field['columns'] as $column => $attributes) {
      $columns[] = _field_sql_storage_columnname($field_name, $column);
    }
    $query = db_insert($table_name)->fields($columns);
    $revision_query = db_insert($revision_name)->fields($columns);

    foreach ($field_langcodes as $langcode) {
      $items = (array) $entity->{$field_name}[$langcode];
      $delta_count = 0;
      foreach ($items as $delta => $item) {
        // We now know we have someting to insert.
        $do_insert = TRUE;
        $record = array(
          'entity_type' => $entity->entityType(),
          'entity_id' => $id,
          'revision_id' => $vid,
          'bundle' => $bundle,
          'delta' => $delta,
          'langcode' => $langcode,
        );
        foreach ($field['columns'] as $column => $attributes) {
          $record[_field_sql_storage_columnname($field_name, $column)] = isset($item[$column]) ? $item[$column] : NULL;
        }
        $query->values($record);
        if (isset($vid)) {
          $revision_query->values($record);
        }

        if ($field['cardinality'] != FIELD_CARDINALITY_UNLIMITED && ++$delta_count == $field['cardinality']) {
          break;
        }
      }
    }

    // Execute the query if we have values to insert.
    if ($do_insert) {
      $query->execute();
      $revision_query->execute();
    }
  }
}

/**
 * Delete all field data for an entity.
 *
 * This hook is invoked from field_attach_delete() to ask the field storage
 * module to delete field data.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity on which to operate.
 * @param $fields
 *   An array listing the fields to delete. The keys and values of the
 *   array are field UUIDs.
 */
function hook_field_storage_delete(\Drupal\Core\Entity\EntityInterface $entity, $fields) {
  foreach (field_info_instances($entity->entityType(), $entity->bundle()) as $instance) {
    if (isset($fields[$instance['field_id']])) {
      $field = field_info_field_by_id($instance['field_id']);
      field_sql_storage_field_storage_purge($entity, $field, $instance);
    }
  }
}

/**
 * Delete a single revision of field data for an entity.
 *
 * This hook is invoked from field_attach_delete_revision() to ask the field
 * storage module to delete field revision data.
 *
 * Deleting the current (most recently written) revision is not
 * allowed as has undefined results.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity on which to operate.
 * @param $fields
 *   An array listing the fields to delete. The keys and values of the
 *   array are field UUIDs.
 */
function hook_field_storage_delete_revision(\Drupal\Core\Entity\EntityInterface $entity, $fields) {
  $vid = $entity->getRevisionId();
  if (isset($vid)) {
    foreach ($fields as $field_id) {
      $field = field_info_field_by_id($field_id);
      $revision_name = _field_sql_storage_revision_tablename($field);
      db_delete($revision_name)
        ->condition('entity_type', $entity_type)
        ->condition('entity_id', $entity->id())
        ->condition('revision_id', $vid)
        ->execute();
    }
  }
}

/**
 * Execute a Drupal\Core\Entity\EntityFieldQuery.
 *
 * This hook is called to find the entities having certain entity and field
 * conditions and sort them in the given field order. If the field storage
 * engine also handles property sorts and orders, it should unset those
 * properties in the called object to signal that those have been handled.
 *
 * @param Drupal\Core\Entity\EntityFieldQuery $query
 *   An EntityFieldQuery.
 *
 * @return
 *   See Drupal\Core\Entity\EntityFieldQuery::execute() for the return values.
 */
function hook_field_storage_query($query) {
  $groups = array();
  if ($query->age == FIELD_LOAD_CURRENT) {
    $tablename_function = '_field_sql_storage_tablename';
    $id_key = 'entity_id';
  }
  else {
    $tablename_function = '_field_sql_storage_revision_tablename';
    $id_key = 'revision_id';
  }
  $table_aliases = array();
  // Add tables for the fields used.
  foreach ($query->fields as $key => $field) {
    $tablename = $tablename_function($field);
    // Every field needs a new table.
    $table_alias = $tablename . $key;
    $table_aliases[$key] = $table_alias;
    if ($key) {
      $select_query->join($tablename, $table_alias, "$table_alias.entity_type = $field_base_table.entity_type AND $table_alias.$id_key = $field_base_table.$id_key");
    }
    else {
      $select_query = db_select($tablename, $table_alias);
      $select_query->addTag('entity_field_access');
      $select_query->addMetaData('base_table', $tablename);
      $select_query->fields($table_alias, array('entity_type', 'entity_id', 'revision_id', 'bundle'));
      $field_base_table = $table_alias;
    }
    if ($field['cardinality'] != 1) {
      $select_query->distinct();
    }
  }

  // Add field conditions.
  foreach ($query->fieldConditions as $key => $condition) {
    $table_alias = $table_aliases[$key];
    $field = $condition['field'];
    // Add the specified condition.
    $sql_field = "$table_alias." . _field_sql_storage_columnname($field['field_name'], $condition['column']);
    $query->addCondition($select_query, $sql_field, $condition);
    // Add delta / language group conditions.
    foreach (array('delta', 'langcode') as $column) {
      if (isset($condition[$column . '_group'])) {
        $group_name = $condition[$column . '_group'];
        if (!isset($groups[$column][$group_name])) {
          $groups[$column][$group_name] = $table_alias;
        }
        else {
          $select_query->where("$table_alias.$column = " . $groups[$column][$group_name] . ".$column");
        }
      }
    }
  }

  if (isset($query->deleted)) {
    $select_query->condition("$field_base_table.deleted", (int) $query->deleted);
  }

  // Is there a need to sort the query by property?
  $has_property_order = FALSE;
  foreach ($query->order as $order) {
    if ($order['type'] == 'property') {
      $has_property_order = TRUE;
    }
  }

  if ($query->propertyConditions || $has_property_order) {
    if (empty($query->entityConditions['entity_type']['value'])) {
      throw new EntityFieldQueryException('Property conditions and orders must have an entity type defined.');
    }
    $entity_type = $query->entityConditions['entity_type']['value'];
    $entity_base_table = _field_sql_storage_query_join_entity($select_query, $entity_type, $field_base_table);
    $query->entityConditions['entity_type']['operator'] = '=';
    foreach ($query->propertyConditions as $property_condition) {
      $query->addCondition($select_query, "$entity_base_table." . $property_condition['column'], $property_condition);
    }
  }
  foreach ($query->entityConditions as $key => $condition) {
    $query->addCondition($select_query, "$field_base_table.$key", $condition);
  }

  // Order the query.
  foreach ($query->order as $order) {
    if ($order['type'] == 'entity') {
      $key = $order['specifier'];
      $select_query->orderBy("$field_base_table.$key", $order['direction']);
    }
    elseif ($order['type'] == 'field') {
      $specifier = $order['specifier'];
      $field = $specifier['field'];
      $table_alias = $table_aliases[$specifier['index']];
      $sql_field = "$table_alias." . _field_sql_storage_columnname($field['field_name'], $specifier['column']);
      $select_query->orderBy($sql_field, $order['direction']);
    }
    elseif ($order['type'] == 'property') {
      $select_query->orderBy("$entity_base_table." . $order['specifier'], $order['direction']);
    }
  }

  return $query->finishQuery($select_query, $id_key);
}

/**
 * Act on creation of a new field.
 *
 * This hook is invoked during the creation of a field to ask the field storage
 * module to save field information and prepare for storing field instances. If
 * there is a problem, the field storage module should throw an exception.
 *
 * @param $field
 *   The field structure being created.
 */
function hook_field_storage_create_field($field) {
  $schema = _field_sql_storage_schema($field);
  foreach ($schema as $name => $table) {
    db_create_table($name, $table);
  }
  drupal_get_schema(NULL, TRUE);
}

/**
 * Update the storage information for a field.
 *
 * This is invoked on the field's storage module when updating the field,
 * before the new definition is saved to the database. The field storage module
 * should update its storage tables according to the new field definition. If
 * there is a problem, the field storage module should throw an exception.
 *
 * @param $field
 *   The updated field structure to be saved.
 * @param $prior_field
 *   The previously-saved field structure.
 */
function hook_field_storage_update_field($field, $prior_field) {
  if (!$field->hasData()) {
    // There is no data. Re-create the tables completely.
    $prior_schema = _field_sql_storage_schema($prior_field);
    foreach ($prior_schema as $name => $table) {
      db_drop_table($name, $table);
    }
    $schema = _field_sql_storage_schema($field);
    foreach ($schema as $name => $table) {
      db_create_table($name, $table);
    }
  }
  else {
    // There is data. See field_sql_storage_field_storage_update_field() for
    // an example of what to do to modify the schema in place, preserving the
    // old data as much as possible.
  }
  drupal_get_schema(NULL, TRUE);
}

/**
 * Act on deletion of a field.
 *
 * This hook is invoked during the deletion of a field to ask the field storage
 * module to mark all information stored in the field for deletion.
 *
 * @param $field
 *   The field being deleted.
 */
function hook_field_storage_delete_field($field) {
  // Mark all data associated with the field for deletion.
  $field['deleted'] = FALSE;
  $table = _field_sql_storage_tablename($field);
  $revision_table = _field_sql_storage_revision_tablename($field);
  db_update($table)
    ->fields(array('deleted' => 1))
    ->execute();

  // Move the table to a unique name while the table contents are being deleted.
  $field['deleted'] = TRUE;
  $new_table = _field_sql_storage_tablename($field);
  $revision_new_table = _field_sql_storage_revision_tablename($field);
  db_rename_table($table, $new_table);
  db_rename_table($revision_table, $revision_new_table);
  drupal_get_schema(NULL, TRUE);
}

/**
 * Act on deletion of a field instance.
 *
 * This hook is invoked during the deletion of a field instance to ask the
 * field storage module to mark all information stored for the field instance
 * for deletion.
 *
 * @param $instance
 *   The instance being deleted.
 */
function hook_field_storage_delete_instance($instance) {
  $field = field_info_field($instance['field_name']);
  $table_name = _field_sql_storage_tablename($field);
  $revision_name = _field_sql_storage_revision_tablename($field);
  db_update($table_name)
    ->fields(array('deleted' => 1))
    ->condition('entity_type', $instance['entity_type'])
    ->condition('bundle', $instance['bundle'])
    ->execute();
  db_update($revision_name)
    ->fields(array('deleted' => 1))
    ->condition('entity_type', $instance['entity_type'])
    ->condition('bundle', $instance['bundle'])
    ->execute();
}

/**
 * Act before the storage backends load field data.
 *
 * This hook allows modules to load data before the Field Storage API,
 * optionally preventing the field storage module from doing so.
 *
 * This lets 3rd party modules override, mirror, share, or otherwise store a
 * subset of fields in a different way than the current storage engine. Possible
 * use cases include per-bundle storage, per-combo-field storage, etc.
 *
 * Modules implementing this hook should load field values and add them to
 * objects in $entities. Fields with no values should be added as empty arrays.
 * In addition, fields loaded should be added as keys to $skip_fields.
 *
 * @param $entity_type
 *   The type of entity, such as 'node' or 'user'.
 * @param $entities
 *   The array of entity objects to add fields to, keyed by entity ID.
 * @param $age
 *   FIELD_LOAD_CURRENT to load the most recent revision for all fields, or
 *   FIELD_LOAD_REVISION to load the version indicated by each entity.
 * @param $skip_fields
 *   An array keyed by field UUIDs whose data has already been loaded and
 *   therefore should not be loaded again. Add a key to this array to indicate
 *   that your module has already loaded a field.
 * @param $options
 *   An associative array of additional options, with the following keys:
 *   - field_id: The field UUID that should be loaded. If unset, all fields
 *     should be loaded.
 *   - deleted: If TRUE, deleted fields should be loaded as well as non-deleted
 *     fields. If unset or FALSE, only non-deleted fields should be loaded.
 */
function hook_field_storage_pre_load($entity_type, $entities, $age, &$skip_fields, $options) {
  // @todo Needs function body.
}

/**
 * Act before the storage backends insert field data.
 *
 * This hook allows modules to store data before the Field Storage API,
 * optionally preventing the field storage module from doing so.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity with fields to save.
 * @param $skip_fields
 *   An array keyed by field UUIDs whose data has already been written and
 *   therefore should not be written again. The values associated with these
 *   keys are not specified.
 * @return
 *   Saved field UUIDs are set as keys in $skip_fields.
 */
function hook_field_storage_pre_insert(\Drupal\Core\Entity\EntityInterface $entity, &$skip_fields) {
  if ($entity->entityType() == 'node' && $entity->status && _forum_node_check_node_type($entity)) {
    $query = db_insert('forum_index')->fields(array('nid', 'title', 'tid', 'sticky', 'created', 'comment_count', 'last_comment_timestamp'));
    foreach ($entity->taxonomy_forums as $language) {
      foreach ($language as $delta) {
        $query->values(array(
          'nid' => $entity->id(),
          'title' => $entity->title,
          'tid' => $delta['value'],
          'sticky' => $entity->sticky,
          'created' => $entity->created,
          'comment_count' => 0,
          'last_comment_timestamp' => $entity->created,
        ));
      }
    }
    $query->execute();
  }
}

/**
 * Act before the storage backends update field data.
 *
 * This hook allows modules to store data before the Field Storage API,
 * optionally preventing the field storage module from doing so.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity with fields to save.
 * @param $skip_fields
 *   An array keyed by field UUIDs whose data has already been written and
 *   therefore should not be written again. The values associated with these
 *   keys are not specified.
 * @return
 *   Saved field UUIDs are set as keys in $skip_fields.
 */
function hook_field_storage_pre_update(\Drupal\Core\Entity\EntityInterface $entity, &$skip_fields) {
  $first_call = &drupal_static(__FUNCTION__, array());

  if ($entity->entityType() == 'node' && $entity->status && _forum_node_check_node_type($entity)) {
    // We don't maintain data for old revisions, so clear all previous values
    // from the table. Since this hook runs once per field, per entity, make
    // sure we only wipe values once.
    if (!isset($first_call[$entity->id()])) {
      $first_call[$entity->id()] = FALSE;
      db_delete('forum_index')->condition('nid', $entity->id())->execute();
    }
    // Only save data to the table if the node is published.
    if ($entity->status) {
      $query = db_insert('forum_index')->fields(array('nid', 'title', 'tid', 'sticky', 'created', 'comment_count', 'last_comment_timestamp'));
      foreach ($entity->taxonomy_forums as $language) {
        foreach ($language as $delta) {
          $query->values(array(
            'nid' => $entity->id(),
            'title' => $entity->title,
            'tid' => $delta['value'],
            'sticky' => $entity->sticky,
            'created' => $entity->created,
            'comment_count' => 0,
            'last_comment_timestamp' => $entity->created,
          ));
        }
      }
      $query->execute();
      // The logic for determining last_comment_count is fairly complex, so
      // call _forum_update_forum_index() too.
      _forum_update_forum_index($entity->id());
    }
  }
}

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
 * @} End of "addtogroup field_storage".
 */

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
 * Drupal\field\FieldUpdateForbiddenException.
 *
 * @param $field
 *   The field as it will be post-update.
 * @param $prior_field
 *   The field as it is pre-update.
 */
function hook_field_update_forbid($field, $prior_field) {
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
      throw new FieldUpdateForbiddenException("Cannot update a list field not to include keys with existing data");
    }
  }
}

/**
 * Acts when a field record is being purged.
 *
 * In field_purge_field(), after the field configuration has been removed from
 * the database, the field storage module has had a chance to run its
 * hook_field_storage_purge_field(), and the field info cache has been cleared,
 * this hook is invoked on all modules to allow them to respond to the field
 * being purged.
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
 * In field_purge_instance(), after the field instance has been removed from the
 * database, the field storage module has had a chance to run its
 * hook_field_storage_purge_instance(), and the field info cache has been
 * cleared, this hook is invoked on all modules to allow them to respond to the
 * field instance being purged.
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
 * Remove field storage information when a field record is purged.
 *
 * Called from field_purge_field() to allow the field storage module to remove
 * field information when a field is being purged.
 *
 * @param $field
 *   The field being purged.
 */
function hook_field_storage_purge_field($field) {
  $table_name = _field_sql_storage_tablename($field);
  $revision_name = _field_sql_storage_revision_tablename($field);
  db_drop_table($table_name);
  db_drop_table($revision_name);
}

/**
 * Remove field storage information when a field instance is purged.
 *
 * Called from field_purge_instance() to allow the field storage module to
 * remove field instance information when a field instance is being purged.
 *
 * @param $instance
 *   The instance being purged.
 */
function hook_field_storage_purge_field_instance($instance) {
  db_delete('my_module_field_instance_info')
    ->condition('id', $instance['id'])
    ->execute();
}

/**
 * Remove field storage information when field data is purged.
 *
 * Called from field_purge_data() to allow the field storage module to delete
 * field data information.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The pseudo-entity whose field data to delete.
 * @param $field
 *   The (possibly deleted) field whose data is being purged.
 * @param $instance
 *   The deleted field instance whose data is being purged.
 */
function hook_field_storage_purge(\Drupal\Core\Entity\EntityInterface $entity, $field, $instance) {
  $table_name = _field_sql_storage_tablename($field);
  $revision_name = _field_sql_storage_revision_tablename($field);
  db_delete($table_name)
    ->condition('entity_type', $entity->entityType())
    ->condition('entity_id', $entity->id())
    ->execute();
  db_delete($revision_name)
    ->condition('entity_type', $entity->entityType())
    ->condition('entity_id', $entity->id())
    ->execute();
}

/**
 * @} End of "addtogroup field_crud".
 */

/**
 * Determine whether the user has access to a given field.
 *
 * This hook is invoked from field_access() to let modules block access to
 * operations on fields. If no module returns FALSE, the operation is allowed.
 *
 * @param $op
 *   The operation to be performed. Possible values: 'edit', 'view'.
 * @param \Drupal\field\FieldInterface $field
 *   The field on which the operation is to be performed.
 * @param $entity_type
 *   The type of $entity; for example, 'node' or 'user'.
 * @param $entity
 *   (optional) The entity for the operation.
 * @param $account
 *   (optional) The account to check; if not given use currently logged in user.
 *
 * @return
 *   TRUE if the operation is allowed, and FALSE if the operation is denied.
 */
function hook_field_access($op, \Drupal\field\FieldInterface $field, $entity_type, $entity, $account) {
  if ($field['field_name'] == 'field_of_interest' && $op == 'edit') {
    return user_access('edit field of interest', $account);
  }
  return TRUE;
}

/**
 * @} End of "addtogroup hooks".
 */
