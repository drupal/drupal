<?php
// $Id$

/**
 * @ingroup field_fieldable_type
 * @{
 */

/**
 * Inform the Field API about one or more fieldable types.
 *
 * Inform the Field API about one or more fieldable types (object types to
 * which fields can be attached).
 *
 * @return
 *   An array whose keys are fieldable object type names and
 *   whose values are arrays with the following key/value pairs:
 *   - label: The human-readable name of the type.
 *   - object keys: An array describing how the Field API can extract the
 *     informations it needs from the objects of the type.
 *     - id: The name of the property that contains the primary id of the
 *       object. Every object passed to the Field API must have this property
 *       and its value must be numeric.
 *     - revision: The name of the property that contains the revision id of
 *       the object. The Field API assumes that all revision ids are unique
 *       across all objects of a type.
 *       This element can be omitted if the objects of this type are not
 *       versionable.
 *     - bundle: The name of the property that contains the bundle name for the
 *       object. The bundle name defines which set of fields are attached to
 *       the object (e.g. what nodes call "content type").
 *       This element can be omitted if this type has no bundles (all objects
 *       have the same fields).
 *   - bundle keys: An array describing how the Field API can extract the
 *     informations it needs from the bundle objects for this type (e.g
 *     $vocabulary objects for terms; not applicable for nodes).
 *     This element can be omitted if this type's bundles do not exist as
 *     standalone objects.
 *     - bundle: The name of the property that contains the name of the bundle
 *       object.
 *   - cacheable: A boolean indicating whether Field API should cache
 *     loaded fields for each object, reducing the cost of
 *     field_attach_load().
 *   - bundles: An array describing all bundles for this object type.
 *     Keys are bundles machine names, as found in the objects' 'bundle'
 *     property (defined in the 'object keys' entry above).
 *     - label: The human-readable name of the bundle.
 *     - admin: An array of informations that allow Field UI pages (currently
 *       implemented in a contributed module) to attach themselves to the
 *       existing administration pages for the bundle.
 *       - path: the path of the bundle's main administration page, as defined
 *         in hook_menu(). If the path includes a placeholder for the bundle,
 *         the 'bundle argument', 'bundle helper' and 'real path' keys below
 *         are required.
 *       - bundle argument: The position of the placeholder in 'path', if any.
 *       - real path: The actual path (no placeholder) of the bundle's main
 *         administration page. This will be used to generate links.
 *       - access callback: As in hook_menu(). 'user_access' will be assumed if
 *         no value is provided.
 *       - access arguments: As in hook_menu().
 */
function hook_fieldable_info() {
  $return = array(
    'taxonomy_term' => array(
      'label' => t('Taxonomy term'),
      'object keys' => array(
        'id' => 'tid',
        'bundle' => 'vocabulary_machine_name',
      ),
      'bundle keys' => array(
        'bundle' => 'machine_name',
      ),
      'bundles' => array(),
    ),
  );
  foreach (taxonomy_get_vocabularies() as $vocabulary) {
    $return['taxonomy_term']['bundles'][$vocabulary->machine_name] = array(
      'label' => $vocabulary->name,
      'admin' => array(
        'path' => 'admin/structure/taxonomy/%taxonomy_vocabulary',
        'real path' => 'admin/structure/taxonomy/' . $vocabulary->vid,
        'bundle argument' => 3,
        'access arguments' => array('administer taxonomy'),
      ),
    );
  }
  return $return;
}

/**
 * Perform alterations on fieldable types.
 *
 * @param $info
 *   Array of informations on fieldable types exposed by hook_fieldable_info()
 *   implementations.
 */
function hook_fieldable_info_alter(&$info) {
  // A contributed module handling node-level caching would want to disable
  // field cache for nodes.
  $info['node']['cacheable'] = FALSE;
}

/**
 * @} End of "ingroup field_fieldable_type"
 */

/**
 * @defgroup field_types Field Types API
 * @{
 * Define field types, widget types, and display formatter types.
 *
 * The bulk of the Field Types API are related to field types. A
 * field type represents a particular data storage type (integer,
 * string, date, etc.) that can be attached to a fieldable object.
 * hook_field_info() defines the basic properties of a field type, and
 * a variety of other field hooks are called by the Field Attach API
 * to perform field-type-specific actions.
 *
 * The Field Types API also defines widget types via
 * hook_field_widget_info(). Widgets are Form API elements with
 * additional processing capabilities. A field module can define
 * widgets that work with its own field types or with any other
 * module's field types. Widget hooks are typically called by the
 * Field Attach API when creating the field form elements during
 * field_attach_form().
 *
 * TODO Display formatters.
 */

/**
 * Define Field API field types.
 *
 * @return
 *   An array whose keys are field type names and whose values are:
 *
 *   label: TODO
 *   description: TODO
 *   settings: TODO
 *   instance_settings: TODO
 *   default_widget: TODO
 *   default_formatter: TODO
 *   behaviors: TODO
 */
function hook_field_info() {
  return array(
    'text' => array(
      'label' => t('Text'),
      'description' => t('This field stores varchar text in the database.'),
      'settings' => array('max_length' => 255),
      'instance_settings' => array('text_processing' => 0),
      'default_widget' => 'text_textfield',
      'default_formatter' => 'text_default',
    ),
    'textarea' => array(
      'label' => t('Textarea'),
      'description' => t('This field stores long text in the database.'),
      'instance_settings' => array('text_processing' => 0),
      'default_widget' => 'text_textarea',
      'default_formatter' => 'text_default',
    ),
  );
}

/**
 * Perform alterations on Field API field types.
 *
 * @param $info
 *   Array of informations on widget types exposed by hook_field_info()
 *   implementations.
 */
function hook_field_info_alter(&$info) {
  // Add a setting to all field types.
  foreach ($info as $field_type => $field_type_info) {
    $info[$field_type]['settings'][] = array('mymodule_additional_setting' => 'default value');
  }

  // Change the default widget for fields of type 'foo'.
  if (isset($info['foo'])) {
    $info['foo']['default widget'] = 'mymodule_widget';
  }
}

/**
 * Define the Field API schema for a field structure.
 *
 * @param $field
 *   A field structure.
 * @return
 *   An associative array with the following keys:
 *   - 'columns': an array of Schema API column specifications, keyed by
 *     column name. This specifies what comprises a value for a given field.
 *     For example, a value for a number field is simply 'value', while a
 *     value for a formatted text field is the combination of 'value' and
 *     'format'.
 *     It is recommended to avoid having the columns definitions depend on
 *     field settings when possible.
 *     No assumptions should be made on how storage engines internally use the
 *     original column name to structure their storage.
 *   - 'indexes': an array of Schema API indexes definitions. Only columns that
 *     appear in the 'columns' array are allowed.
 *     Those indexes will be used as default indexes. Callers of
 *     field_create_field() can specify additional indexes, or, at their own
 *     risk, modify the default indexes specified by the field-type module.
 *     Some storage engines might not support indexes.
 */
function hook_field_schema($field) {
  if ($field['type'] == 'text_long') {
    $columns = array(
      'value' => array(
        'type' => 'text',
        'size' => 'big',
        'not null' => FALSE,
      ),
    );
  }
  else {
    $columns = array(
      'value' => array(
        'type' => 'varchar',
        'length' => $field['settings']['max_length'],
        'not null' => FALSE,
      ),
    );
  }
  $columns += array(
    'format' => array(
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => FALSE,
    ),
  );
  return array(
    'columns' => $columns,
    'indexes' => array(
      'format' => array('format'),
    ),
  );
}

/**
 * Define Field API widget types.
 *
 * @return
 *   An array whose keys are field type names and whose values are:
 *
 *   label: TODO
 *   description: TODO
 *   field types: TODO
 *   settings: TODO
 *   behaviors: TODO
 */
function hook_field_widget_info() {
}

/**
 * Perform alterations on Field API widget types.
 *
 * @param $info
 *   Array of informations on widget types exposed by hook_field_widget_info()
 *   implementations.
 */
function hook_field_widget_info_alter(&$info) {
  // Add a setting to a widget type.
  $info['text_textfield']['settings'][] = array('mymodule_additional_setting' => 'default value');

  // Let a new field type re-use an existing widget.
  $info['options_select']['field types'][] = 'my_field_type';
}

/*
 * Define Field API formatter types.
 *
 * @return
 *   An array whose keys are field type names and whose values are:
 *
 *   label: TODO
 *   description: TODO
 *   field types: TODO
 *   behaviors: TODO
 */
function hook_field_formatter_info() {
}

/**
 * Perform alterations on Field API formatter types.
 *
 * @param $info
 *   Array of informations on widget types exposed by
 *   hook_field_field_formatter_info() implementations.
 */
function hook_field_formatter_info_alter(&$info) {
  // Add a setting to a formatter type.
  $info['text_default']['settings'][] = array('mymodule_additional_setting' => 'default value');

  // Let a new field type re-use an existing formatter.
  $info['text_default']['field types'][] = 'my_field_type';
}

/**
 * Define custom load behavior for this module's field types.
 *
 * Unlike other field hooks, this hook operates on multiple objects. The
 * $objects, $instances and $items parameters are arrays keyed by object id.
 * For performance reasons, information for all available objects should be
 * loaded in a single query where possible.
 *
 * Note that the changes made to the field values get cached by the
 * field cache for subsequent loads.
 *
 * @param $obj_type
 *   The type of $object.
 * @param $objects
 *   Array of objects being loaded, keyed by object id.
 * @param $field
 *   The field structure for the operation.
 * @param $instances
 *   Array of instance structures for $field for each object, keyed by object id.
 * @param $items
 *   Array of field values already loaded for the objects, keyed by object id.
 * @param $age
 *   FIELD_LOAD_CURRENT to load the most recent revision for all fields, or
 *   FIELD_LOAD_REVISION to load the version indicated by each object.
 * @return
 *   Changes or additions to field values are done by altering the $items
 *   parameter by reference.
 */
function hook_field_load($obj_type, $objects, $field, $instances, &$items, $age) {
}

/**
 * Define custom validate behavior for this module's field types.
 *
 * @param $obj_type
 *   The type of $object.
 * @param $object
 *   The object for the operation.
 *   Note that this might not be a full-fledged 'object'. When invoked through
 *   field_attach_query(), the $object will only include properties that the
 *   Field API knows about: bundle, id, revision id, and field values (no node
 *   title, user name...).
 * @param $field
 *   The field structure for the operation.
 * @param $instance
 *   The instance structure for $field on $object's bundle.
 * @param $items
 *   $object->{$field['field_name']}, or an empty array if unset.
 * @param $errors
 *   The array of errors, keyed by field name and by value delta, that have
 *   already been reported for the object. The function should add its errors
 *   to this array. Each error is an associative array, with the following
 *   keys and values:
 *   - 'error': an error code (should be a string, prefixed with the module name)
 *   - 'message': the human readable message to be displayed.
 */
function hook_field_validate($obj_type, $object, $field, $instance, $items, &$errors) {
  foreach ($items as $delta => $item) {
    if (!empty($item['value'])) {
      if (!empty($field['settings']['max_length']) && drupal_strlen($item['value']) > $field['settings']['max_length']) {
        $errors[$field['field_name']][$delta][] = array(
          'error' => 'text_max_length',
          'message' => t('%name: the value may not be longer than %max characters.', array('%name' => $instance['label'], '%max' => $field['settings']['max_length'])),
        );
      }
    }
  }
}

/**
 * Define custom presave behavior for this module's field types.
 *
 * @param $obj_type
 *   The type of $object.
 * @param $object
 *   The object for the operation.
 * @param $field
 *   The field structure for the operation.
 * @param $instance
 *   The instance structure for $field on $object's bundle.
 * @param $items
 *   $object->{$field['field_name']}, or an empty array if unset.
 */
function hook_field_presave($obj_type, $object, $field, $instance, $items) {
}

/**
 * Define custom insert behavior for this module's field types.
 *
 * @param $obj_type
 *   The type of $object.
 * @param $object
 *   The object for the operation.
 * @param $field
 *   The field structure for the operation.
 * @param $instance
 *   The instance structure for $field on $object's bundle.
 * @param $items
 *   $object->{$field['field_name']}, or an empty array if unset.
 */
function hook_field_insert($obj_type, $object, $field, $instance, $items) {
}

/**
 * Define custom update behavior for this module's field types.
 *
 * @param $obj_type
 *   The type of $object.
 * @param $object
 *   The object for the operation.
 * @param $field
 *   The field structure for the operation.
 * @param $instance
 *   The instance structure for $field on $object's bundle.
 * @param $items
 *   $object->{$field['field_name']}, or an empty array if unset.
 */
function hook_field_update($obj_type, $object, $field, $instance, $items) {
}

/**
 * Define custom delete behavior for this module's field types.
 *
 * This hook is invoked just before the data is deleted from field storage.
 *
 * @param $obj_type
 *   The type of $object.
 * @param $object
 *   The object for the operation.
 * @param $field
 *   The field structure for the operation.
 * @param $instance
 *   The instance structure for $field on $object's bundle.
 * @param $items
 *   $object->{$field['field_name']}, or an empty array if unset.
 */
function hook_field_delete($obj_type, $object, $field, $instance, $items) {
}

/**
 * Define custom delete_revision behavior for this module's field types.
 *
 * This hook is invoked just before the data is deleted from field storage,
 * and will only be called for fieldable types that are versioned.
 *
 * @param $obj_type
 *   The type of $object.
 * @param $object
 *   The object for the operation.
 * @param $field
 *   The field structure for the operation.
 * @param $instance
 *   The instance structure for $field on $object's bundle.
 * @param $items
 *   $object->{$field['field_name']}, or an empty array if unset.
 */
function hook_field_delete_revision($obj_type, $object, $field, $instance, $items) {
}

/**
 * Define custom sanitize behavior for this module's field types.
 *
 * @param $obj_type
 *   The type of $object.
 * @param $object
 *   The object for the operation.
 * @param $field
 *   The field structure for the operation.
 * @param $instance
 *   The instance structure for $field on $object's bundle.
 * @param $items
 *   $object->{$field['field_name']}, or an empty array if unset.
 */
function hook_field_sanitize($obj_type, $object, $field, $instance, $items) {
}

/**
 * Define custom prepare_translation behavior for this module's field types.
 *
 * TODO: This hook may or may not survive in Field API.
 *
 * @param $obj_type
 *   The type of $object.
 * @param $object
 *   The object for the operation.
 * @param $field
 *   The field structure for the operation.
 * @param $instance
 *   The instance structure for $field on $object's bundle.
 * @param $items
 *   $object->{$field['field_name']}, or an empty array if unset.
 */
function hook_field_prepare_translation($obj_type, $object, $field, $instance, $items) {
}

/**
 * Return a single form element for a form.
 *
 * It will be built out and validated in the callback(s) listed in
 * hook_elements. We build it out in the callbacks rather than in
 * hook_field_widget so it can be plugged into any module that can
 * provide it with valid $field information.
 *
 * Field API will set the weight, field name and delta values for each
 * form element. If there are multiple values for this field, the
 * Field API will call this function as many times as needed.
 *
 * @param $form
 *   The entire form array.
 * @param $form_state
 *   The form_state, $form_state['values'][$field['field_name']]
 *   holds the field's form values.
 * @param $field
 *   The field structure.
 * @param $instance
 *   The field instance.
 * @param $items
 *   Array of default values for this field.
 * @param $delta
 *   The order of this item in the array of subelements (0, 1, 2, etc).
 * @return
 *   The form item for a single element for this field.
 */
function hook_field_widget(&$form, &$form_state, $field, $instance, $items, $delta = 0) {
  $element = array(
    '#type' => $instance['widget']['type'],
    '#default_value' => isset($items[$delta]) ? $items[$delta] : '',
  );
  return $element;
}

/**
 * Flag a field-level validation error.
 *
 * @param $element
 *   An array containing the form element for the widget. The error needs to be
 *   flagged on the right sub-element, according to the widget's internal
 *   structure.
 * @param $error
 *   An associative array with the following key-value pairs, as returned by
 *   hook_field_validate():
 *   - 'error': the error code. Complex widgets might need to report different
 *     errors to different form elements inside the widget.
 *   - 'message': the human readable message to be displayed.
 */
function hook_field_widget_error($element, $error) {
  form_error($element['value'], $error['message']);
}

/**
 * @} End of "ingroup field_type"
 */

/**
 * @ingroup field_attach
 * @{
 */

/**
 * Act on field_attach_form.
 *
 * This hook is invoked after the field module has performed the operation.
 *
 * See field_attach_form() for details and arguments.
 */
function hook_field_attach_form($obj_type, $object, &$form, &$form_state) {
}

/**
 * Act on field_attach_pre_load.
 *
 * This hook allows modules to load data before the Field Storage API,
 * optionally preventing the field storage module from doing so.
 *
 * This lets 3rd party modules override, mirror, shard, or otherwise store a
 * subset of fields in a different way than the current storage engine.
 * Possible use cases include : per-bundle storage, per-combo-field storage...
 *
 * @param $obj_type
 *   The type of objects for which to load fields; e.g. 'node' or 'user'.
 * @param $objects
 *   An array of objects for which to load fields, keyed by object id.
 * @param $age
 *   FIELD_LOAD_CURRENT to load the most recent revision for all fields, or
 *   FIELD_LOAD_REVISION to load the version indicated by each object.
 * @param $skip_fields
 *   An array keyed by names of fields whose data has already been loaded and
 *   therefore should not be loaded again. The values associated to these keys
 *   are not specified.
 * @return
 *   - Loaded field values are added to $objects. Fields with no values should be
 *   set as an empty array.
 *   - Loaded field names are set as keys in $skip_fields.
 */
function hook_field_attach_pre_load($obj_type, $objects, $age, &$skip_fields) {
}

/**
 * Act on field_attach_load.
 *
 * This hook is invoked after the field module has performed the operation.
 *
 * Unlike other field_attach hooks, this hook accounts for 'multiple loads'.
 * Instead of the usual $object parameter, it accepts an array of objects,
 * indexed by object id. For performance reasons, information for all available
 * objects should be loaded in a single query where possible.
 *
 * Note that $objects might not be full-fledged 'objects'. When invoked through
 * field_attach_query(), each object only includes properties that the Field
 * API knows about: bundle, id, revision id, and field values (no node title,
 * user name...)

 * The changes made to the objects' field values get cached by the field cache
 * for subsequent loads.
 *
 * See field_attach_load() for details and arguments.
 */
function hook_field_attach_load($obj_type, $objects, $age) {
}

/**
 * Act on field_attach_validate.
 *
 * This hook is invoked after the field module has performed the operation.
 *
 * See field_attach_validate() for details and arguments.
 */
function hook_field_attach_validate($obj_type, $object, &$errors) {
}

/**
 * Act on field_attach_submit.
 *
 * This hook is invoked after the field module has performed the operation.
 *
 * See field_attach_submit() for details and arguments.
 */
function hook_field_attach_submit($obj_type, $object, $form, &$form_state) {
}

/**
 * Act on field_attach_presave.
 *
 * This hook is invoked after the field module has performed the operation.
 *
 * See field_attach_presave() for details and arguments.
 */
function hook_field_attach_presave($obj_type, $object) {
}

/**
 * Act on field_attach_insert.
 *
 * This hook allows modules to store data before the Field Storage
 * API, optionally preventing the field storage module from doing so.
 *
 * @param $obj_type
 *   The type of $object; e.g. 'node' or 'user'.
 * @param $object
 *   The object with fields to save.
 * @param $skip_fields
 *   An array keyed by names of fields whose data has already been written and
 *   therefore should not be written again. The values associated to these keys
 *   are not specified.
 * @return
 *   Saved field names are set set as keys in $skip_fields.
 */
function hook_field_attach_pre_insert($obj_type, $object, &$skip_fields) {
}

/**
 * Act on field_attach_update.
 *
 * This hook allows modules to store data before the Field Storage
 * API, optionally preventing the field storage module from doing so.
 *
 * @param $obj_type
 *   The type of $object; e.g. 'node' or 'user'.
 * @param $object
 *   The object with fields to save.
 * @param $skip_fields
 *   An array keyed by names of fields whose data has already been written and
 *   therefore should not be written again. The values associated to these keys
 *   are not specified.
 * @return
 *   Saved field names are set set as keys in $skip_fields.
 */
function hook_field_attach_pre_update($obj_type, $object, &$skip_fields) {
}

/**
 * Act on field_attach_pre_query.
 *
 * This hook should be implemented by modules that use
 * hook_field_attach_pre_load(), hook_field_attach_pre_insert() and
 * hook_field_attach_pre_update() to bypass the regular storage engine, to
 * handle field queries.
 *
 * @param $field_name
 *   The name of the field to query.
 * @param $conditions
 *   See field_attach_query().
 *   A storage module that doesn't support querying a given column should raise
 *   a FieldQueryException. Incompatibilities should be mentioned on the module
 *   project page.
 * @param $count
 *   See field_attach_query().
 * @param $cursor
 *   See field_attach_query().
 * @param $age
 *   - FIELD_LOAD_CURRENT: query the most recent revisions for all
 *     objects. The results will be keyed by object type and object id.
 *   - FIELD_LOAD_REVISION: query all revisions. The results will be keyed by
 *     object type and object revision id.
 * @param $skip_field
 *   Boolean, always coming as FALSE.
 * @return
 *   See field_attach_query().
 *   The $skip_field parameter should be set to TRUE if the query has been
 *   handled.
 */
function hook_field_attach_pre_query($field_name, $conditions, $count, &$cursor = NULL, $age, &$skip_field) {
}

/**
 * Act on field_attach_delete.
 *
 * This hook is invoked after the field module has performed the operation.
 *
 * See field_attach_delete() for details and arguments.
 */
function hook_field_attach_delete($obj_type, $object) {
}

/**
 * Act on field_attach_delete_revision.
 *
 * This hook is invoked after the field module has performed the operation.
 *
 * See field_attach_delete_revision() for details and arguments.
 */
function hook_field_attach_delete_revision($obj_type, $object) {
}

/**
 * Act on field_attach_view.
 *
 * This hook is invoked after the field module has performed the operation.
 *
 * @param $output
 *  The structured content array tree for all of $object's fields.
 * @param $obj_type
 *   The type of $object; e.g. 'node' or 'user'.
 * @param $object
 *   The object with fields to render.
 * @param $build_mode
 *   Build mode, e.g. 'full', 'teaser'...
 */
function hook_field_attach_view_alter($output, $obj_type, $object, $build_mode) {
}

/**
 * Act on field_attach_create_bundle.
 *
 * This hook is invoked after the field module has performed the operation.
 *
 * See field_attach_create_bundle() for details and arguments.
 */
function hook_field_attach_create_bundle($bundle) {
}

/**
 * Act on field_attach_rename_bundle.
 *
 * This hook is invoked after the field module has performed the operation.
 *
 * See field_attach_rename_bundle() for details and arguments.
 */
function hook_field_attach_rename_bundle($bundle_old, $bundle_new) {
}

/**
 * Act on field_attach_delete_bundle.
 *
 * This hook is invoked after the field module has performed the operation.
 *
 * @param $bundle
 *   The bundle that was just deleted.
 * @param $instances
 *   An array of all instances that existed for $bundle before it was
 *   deleted.
 */
function hook_field_attach_delete_bundle($bundle, $instances) {
}

/**
 * @} End of "ingroup field_attach"
 */

/**********************************************************************
 * Field Storage API
 **********************************************************************/

/**
 * @ingroup field_storage
 * @{
 */

/**
 * Load field data for a set of objects.
 *
 * @param $obj_type
 *   The entity type of object, such as 'node' or 'user'.
 * @param $objects
 *   The array of objects for which to load data, keyed by object id.
 * @param $age
 *   FIELD_LOAD_CURRENT to load the most recent revision for all
 *   fields, or FIELD_LOAD_REVISION to load the version indicated by
 *   each object.
 * @param $skip_fields
 *   An array keyed by names of fields whose data has already been loaded and
 *   therefore should not be loaded again. The values associated to these keys
 *   are not specified.
 * @return
 *   Loaded field values are added to $objects. Fields with no values should be
 *   set as an empty array.
 */
function hook_field_storage_load($obj_type, $objects, $age, $skip_fields) {
}

/**
 * Write field data for an object.
 *
 * @param $obj_type
 *   The entity type of object, such as 'node' or 'user'.
 * @param $object
 *   The object on which to operate.
 * @param $op
 *   FIELD_STORAGE_UPDATE when updating an existing object,
 *   FIELD_STORAGE_INSERT when inserting a new object.
 * @param $skip_fields
 *   An array keyed by names of fields whose data has already been written and
 *   therefore should not be written again. The values associated to these keys
 *   are not specified.
 */
function hook_field_storage_write($obj_type, $object, $op, $skip_fields) {
}

/**
 * Delete all field data for an object.
 *
 * @param $obj_type
 *   The entity type of object, such as 'node' or 'user'.
 * @param $object
 *   The object on which to operate.
 */
function hook_field_storage_delete($obj_type, $object) {
}

/**
 * Delete a single revision of field data for an object.
 *
 * Deleting the current (most recently written) revision is not
 * allowed as has undefined results.
 *
 * @param $obj_type
 *   The entity type of object, such as 'node' or 'user'.
 * @param $object
 *   The object on which to operate. The revision to delete is
 *   indicated by the object's revision id property, as identified by
 *   hook_fieldable_info() for $obj_type.
 */
function hook_field_storage_delete_revision($obj_type, $object) {
}

/**
 * Handle a field query.
 *
 * @param $field_name
 *   The name of the field to query.
 * @param $conditions
 *   See field_attach_query().
 *   A storage module that doesn't support querying a given column should raise
 *   a FieldQueryException. Incompatibilities should be mentioned on the module
 *   project page.
 * @param $count
 *   See field_attach_query().
 * @param $cursor
 *   See field_attach_query().
 * @param $age
 *   See field_attach_query().
 * @return
 *   See field_attach_query().
 */
function hook_field_storage_query($field_name, $conditions, $count, &$cursor = NULL, $age) {
}

/**
 * Act on creation of a new bundle.
 *
 * @param $bundle
 *   The name of the bundle being created.
 */
function hook_field_storage_create_bundle($bundle) {
}

/**
 * Act on a bundle being renamed.
 *
 * @param $bundle_old
 *   The old name of the bundle.
 * @param $bundle_new
 *   The new name of the bundle.
 */
function hook_field_storage_rename_bundle($bundle_old, $bundle_new) {
}

/**
 * Act on creation of a new field.
 *
 * @param $field
 *   The field structure being created.
 */
function hook_field_storage_create_field($field) {
}

/**
 * Act on deletion of a field.
 *
 * @param $field_name
 *   The name of the field being deleted.
 */
function hook_field_storage_delete_field($field_name) {
}

/**
 * Act on deletion of a field instance.
 *
 * @param $field_name
 *   The name of the field in the new instance.
 * @param $bundle
 *   The name of the bundle in the new instance.
 */
function hook_field_storage_delete_instance($field_name, $bundle) {
}

/**
 * @} End of "ingroup field_storage"
 */

/**********************************************************************
 * Field CRUD API
 **********************************************************************/

/**
 * @ingroup field_crud
 * @{
 */

/**
 * Act on a field being created.
 *
 * This hook is invoked after the field is created and so it cannot modify the
 * field itself.
 *
 * TODO: Not implemented.
 *
 * @param $field
 *   The field just created.
 */
function hook_field_create_field($field) {
}

/**
 * Act on a field instance being created.
 *
 * This hook is invoked after the instance record is saved and so it cannot
 * modify the instance itself.
 *
 * @param $instance
 *   The instance just created.
 */
function hook_field_create_instance($instance) {
}

/**
 * Act on a field being deleted.
 *
 * This hook is invoked just before the field is deleted.
 *
 * TODO: Not implemented.
 *
 * @param $field
 *   The field being deleted.
 */
function hook_field_delete_field($field) {
}


/**
 * Act on a field instance being updated.
 *
 * This hook is invoked after the instance record is saved and so it cannot
 * modify the instance itself.
 *
 * TODO: Not implemented.
 *
 * @param $instance
 *   The instance just updated.
 */
function hook_field_update_instance($instance) {
}

/**
 * Act on a field instance being deleted.
 *
 * This hook is invoked just before the instance is deleted.
 *
 * TODO: Not implemented.
 *
 * @param $instance
 *   The instance just updated.
 */
function hook_field_delete_instance($instance) {
}

/**
 * Act on field records being read from the database.
 *
 * @param $field
 *   The field record just read from the database.
 */
function hook_field_read_field($field) {
}

/**
 * Act on a field record being read from the database.
 *
 * @param $instance
 *   The instance record just read from the database.
 */
function hook_field_read_instance($instance) {
}

/**
 * @} End of "ingroup field_crud"
 */

/**********************************************************************
 * TODO: I'm not sure where these belong yet.
 **********************************************************************/

/**
 * TODO
 *
 * Note : Right now this belongs to the "Fieldable Type API".
 * Whether 'build modes' is actually a 'fields' concept is to be debated
 * in a separate overhaul patch for core.
 */
function hook_field_build_modes($obj_type) {
}

/**
 * Determine whether the user has access to a given field.
 *
 * @param $op
 *   The operation to be performed. Possible values:
 *   - "edit"
 *   - "view"
 * @param $field
 *   The field on which the operation is to be performed.
 * @param $account
 *   (optional) The account to check, if not given use currently logged in user.
 * @return
 *   TRUE if the operation is allowed;
 *   FALSE if the operation is denied.
 */
function hook_field_access($op, $field, $account) {
}
