<?php

/**
 * @file
 * Describes hooks and plugins provided by the Views module.
 */

use Drupal\Core\Language\LanguageInterface;
use Drupal\views\Plugin\views\PluginBase;

/**
 * @defgroup views_overview Views overview
 * @{
 * Overview of the Views module API
 *
 * The Views module is a generalized query and display engine, which can be used
 * to make views (formatted lists, grids, feeds, and other output) of items
 * (often entities, but can be other types of data). Developers can interact
 * with Views in several ways:
 * - Provide plugins: Views plugins govern nearly every aspect of views,
 *   including querying (sorting, filtering, etc.) and display (at several
 *   levels of granularity, ranging from the entire view to the details of a
 *   field). See the @link views_plugins Views plugins topic @endlink for
 *   more information.
 * - Provide data: Data types can be provided to Views by implementing
 *   hook_views_data(), and data types provided by other modules can be altered
 *   by implementing hook_views_data_alter(). To provide views data for an
 *   entity, create a class implementing
 *   \Drupal\views\EntityViewsDataInterface and reference this in the
 *   "views_data" annotation in the entity class. You can autogenerate big parts
 *   of the ingration if you extend the \Drupal\views\EntityViewsData base
 *   class. See the @link entity_api Entity API topic @endlink for more
 *   information about entities.
 * - Implement hooks: A few operations in Views can be influenced by hooks.
 *   See the @link Views hooks topic @endlink for a list.
 * - Theming: See the @link views_templates Views templates topic @endlink
 *   for more information.
 *
 * @see \Drupal\views\ViewExecutable
 * @}
 */

/**
 * @defgroup views_plugins Views plugins
 * Overview of views plugins
 *
 * Views plugins are objects that are used to build and render the view.
 * See individual views plugin topics for more information about the
 * specifics of each plugin type, and the
 * @link plugin_api Plugin API topic @endlink for more information about
 * plugins in general.
 *
 * Some Views plugins are known as handlers. Handler plugins help build the
 * view query object: filtering, contextual filtering, sorting, relationships,
 * etc.
 *
 * @todo Document specific options on the appropriate plugin base classes.
 * @todo Add examples.
 *
 * @see \Drupal\views\Plugin\views\PluginBase
 * @see \Drupal\views\Plugin\views\HandlerBase
 * @see plugin_api
 * @see annotation
 */

/**
 * @defgroup views_hooks Views hooks
 * @{
 * Hooks that allow other modules to implement the Views API.
 */

/**
 * Analyze a view to provide warnings about its configuration.
 *
 * @param \Drupal\views\ViewExecutable $view
 *   The view being executed.
 *
 * @return array
 *   Array of warning messages built by Analyzer::formatMessage to be displayed
 *   to the user following analysis of the view.
 */
function hook_views_analyze(Drupal\views\ViewExecutable $view) {
  $messages = array();

  if ($view->display_handler->options['pager']['type'] == 'none') {
    $messages[] = Drupal\views\Analyzer::formatMessage(t('This view has no pager. This could cause performance issues when the view contains many items.'), 'warning');
  }

  return $messages;
}

/**
 * Describe data tables and fields (or the equivalent) to Views.
 *
 * The table and fields are processed in Views using various plugins. See
 * the @link views_plugins Views plugins topic @endlink for more information.
 *
 * To provide views data for an entity, instead of implementing this hook,
 * create a class implementing \Drupal\views\EntityViewsDataInterface and
 * reference this in the "views" annotation in the entity class. The return
 * value of the getViewsData() method on the interface is the same as this hook.
 * See the @link entity_api Entity API topic @endlink for more information about
 * entities.
 *
 * The data described with this hook is fetched and retrieved by
 * \Drupal\views\Views::viewsData()->get().
 *
 * @return array
 *   An associative array describing the structure of database tables and fields
 *   (and their equivalents) provided for use in Views. At the outermost level,
 *   the keys are the names used internally by Views for the tables (usually the
 *   actual table name). Each table's array describes the table itself, how to
 *   join to other tables, and the fields that are part of the table. The sample
 *   function body provides documentation of the details.
 *
 * @see hook_views_data_alter()
 */
function hook_views_data() {
  // This example describes how to write hook_views_data() for a table defined
  // like this:
  // CREATE TABLE example_table (
  //   nid INT(11) NOT NULL         COMMENT 'Primary key: {node}.nid.',
  //   plain_text_field VARCHAR(32) COMMENT 'Just a plain text field.',
  //   numeric_field INT(11)        COMMENT 'Just a numeric field.',
  //   boolean_field INT(1)         COMMENT 'Just an on/off field.',
  //   timestamp_field INT(8)       COMMENT 'Just a timestamp field.',
  //   PRIMARY KEY(nid)
  // );

  // Define the return array.
  $data = array();

  // The outermost keys of $data are Views table names, which should usually
  // be the same as the hook_schema() table names.
  $data['example_table'] = array();

  // The value corresponding to key 'table' gives properties of the table
  // itself.
  $data['example_table']['table'] = array();

  // Within 'table', the value of 'group' (translated string) is used as a
  // prefix in Views UI for this table's fields, filters, etc. When adding
  // a field, filter, etc. you can also filter by the group.
  $data['example_table']['table']['group'] = t('Example table');

  // Within 'table', the value of 'provider' is the module that provides schema
  // or the entity type that causes the table to exist. Setting this ensures
  // that views have the correct dependencies. This is automatically set to the
  // module that implements hook_views_data().
  $data['example_table']['table']['provider'] = 'example_module';

  // Some tables are "base" tables, meaning that they can be the base tables
  // for views. Non-base tables can only be brought in via relationships in
  // views based on other tables. To define a table to be a base table, add
  // key 'base' to the 'table' array:
  $data['example_table']['table']['base'] = array(
    // Identifier (primary) field in this table for Views.
    'field' => 'nid',
    // Label in the UI.
    'title' => t('Example table'),
    // Longer description in the UI. Required.
    'help' => t('Example table contains example content and can be related to nodes.'),
    'weight' => -10,
  );

  // Some tables have an implicit, automatic relationship to other tables,
  // meaning that when the other table is available in a view (either as the
  // base table or through a relationship), this table's fields, filters, etc.
  // are automatically made available without having to add an additional
  // relationship. To define an implicit relationship that will make your
  // table automatically available when another table is present, add a 'join'
  // section to your 'table' section. Note that it is usually only a good idea
  // to do this for one-to-one joins, because otherwise your automatic join
  // will add more rows to the view. It is also not a good idea to do this if
  // most views won't need your table -- if that is the case, define a
  // relationship instead (see the field section below).
  //
  // If you've decided an automatic join is a good idea, here's how to do it:
  $data['example_table']['table']['join'] = array(
    // Within the 'join' section, list one or more tables to automatically
    // join to. In this example, every time 'node' is available in a view,
    // 'example_table' will be too. The array keys here are the array keys
    // for the other tables, given in their hook_views_data() implementations.
    // If the table listed here is from another module's hook_views_data()
    // implementation, make sure your module depends on that other module.
    'node' => array(
      // Primary key field in node to use in the join.
      'left_field' => 'nid',
      // Foreign key field in example_table to use in the join.
      'field' => 'nid',
      // An array of extra conditions on the join.
      'extra' => array(
        0 => array(
          // Adds AND node.published = TRUE to the join.
          'field' => 'published',
          'value' => TRUE,
        ),
        1 => array(
          // Adds AND example_table.numeric_field = 1 to the join.
          'left_field' => 'numeric_field',
          'value' => 1,
          // If true, the value will not be surrounded in quotes.
          'numeric' => TRUE,
        ),
        2 => array(
          // Adds AND example_table.boolean_field <> node.published to the join.
          'field' => 'published',
          'left_field' => 'boolean_field',
          // The operator used, Defaults to "=".
          'operator' => '!=',
        ),
      ),
    ),
  );

  // Other array elements at the top level of your table's array describe
  // individual database table fields made available to Views. The array keys
  // are the names (unique within the table) used by Views for the fields,
  // usually equal to the database field names.
  //
  // Each field entry must have the following elements:
  // - title: Translated label for the field in the UI.
  // - help: Description of the field in the UI.
  //
  // Each field entry may also have one or more of the following elements,
  // describing "handlers" (plugins) for the field:
  // - relationship: Specifies a handler that allows this field to be used
  //   to define a relationship to another table in Views.
  // - field: Specifies a handler to make it available to Views as a field.
  // - filter: Specifies a handler to make it available to Views as a filter.
  // - sort: Specifies a handler to make it available to Views as a sort.
  // - argument: Specifies a handler to make it available to Views as an
  //   argument, or contextual filter as it is known in the UI.
  // - area: Specifies a handler to make it available to Views to add content
  //   to the header, footer, or as no result behavior.
  //
  // Note that when specifying handlers, you must give the handler plugin ID
  // and you may also specify overrides for various settings that make up the
  // plugin definition. See examples below; the Boolean example demonstrates
  // setting overrides.

  // Node ID field, exposed as relationship only, since it is a foreign key
  // in this table.
  $data['example_table']['nid'] = array(
    'title' => t('Example content'),
    'help' => t('Relate example content to the node content'),

    // Define a relationship to the node table, so views whose base table is
    // example_table can add a relationship to the node table. To make a
    // relationship in the other direction, you can:
    // - Use hook_views_data_alter() -- see the function body example on that
    //   hook for details.
    // - Use the implicit join method described above.
    'relationship' => array(
      // Views name of the table to join to for the relationship.
      'base' => 'node',
      // Database field name in the other table to join on.
      'base field' => 'nid',
      // ID of relationship handler plugin to use.
      'id' => 'standard',
      // Default label for relationship in the UI.
      'label' => t('Example node'),
    ),
  );

  // Plain text field, exposed as a field, sort, filter, and argument.
  $data['example_table']['plain_text_field'] = array(
    'title' => t('Plain text field'),
    'help' => t('Just a plain text field.'),

    'field' => array(
      // ID of field handler plugin to use.
      'id' => 'standard',
    ),

    'sort' => array(
      // ID of sort handler plugin to use.
      'id' => 'standard',
    ),

    'filter' => array(
      // ID of filter handler plugin to use.
      'id' => 'string',
    ),

    'argument' => array(
      // ID of argument handler plugin to use.
      'id' => 'string',
    ),
  );

  // Numeric field, exposed as a field, sort, filter, and argument.
  $data['example_table']['numeric_field'] = array(
    'title' => t('Numeric field'),
    'help' => t('Just a numeric field.'),

    'field' => array(
      // ID of field handler plugin to use.
      'id' => 'numeric',
    ),

    'sort' => array(
      // ID of sort handler plugin to use.
      'id' => 'standard',
    ),

    'filter' => array(
      // ID of filter handler plugin to use.
      'id' => 'numeric',
    ),

    'argument' => array(
      // ID of argument handler plugin to use.
      'id' => 'numeric',
    ),
  );

  // Boolean field, exposed as a field, sort, and filter. The filter section
  // illustrates overriding various settings.
  $data['example_table']['boolean_field'] = array(
    'title' => t('Boolean field'),
    'help' => t('Just an on/off field.'),

    'field' => array(
      // ID of field handler plugin to use.
      'id' => 'boolean',
    ),

    'sort' => array(
      // ID of sort handler plugin to use.
      'id' => 'standard',
    ),

    'filter' => array(
      // ID of filter handler plugin to use.
      'id' => 'boolean',
      // Override the generic field title, so that the filter uses a different
      // label in the UI.
      'label' => t('Published'),
      // Override the default BooleanOperator filter handler's 'type' setting,
      // to display this as a "Yes/No" filter instead of a "True/False" filter.
      'type' => 'yes-no',
      // Override the default Boolean filter handler's 'use_equal' setting, to
      // make the query use 'boolean_field = 1' instead of 'boolean_field <> 0'.
      'use_equal' => TRUE,
    ),
  );

  // Integer timestamp field, exposed as a field, sort, and filter.
  $data['example_table']['timestamp_field'] = array(
    'title' => t('Timestamp field'),
    'help' => t('Just a timestamp field.'),

    'field' => array(
      // ID of field handler plugin to use.
      'id' => 'date',
    ),

    'sort' => array(
      // ID of sort handler plugin to use.
      'id' => 'date',
    ),

    'filter' => array(
      // ID of filter handler plugin to use.
      'id' => 'date',
    ),
  );

  // Area example. Areas are not generally associated with actual data
  // tables and fields. This example is from views_views_data(), which defines
  // the "Global" table (not really a table, but a group of Fields, Filters,
  // etc. that are grouped into section "Global" in the UI). Here's the
  // definition of the generic "Text area":
  $data['views']['area'] = array(
    'title' => t('Text area'),
    'help' => t('Provide markup text for the area.'),
    'area' => array(
      // ID of the area handler plugin to use.
      'id' => 'text',
    ),
  );

  return $data;
}

/**
 * Alter the table and field information from hook_views_data().
 *
 * @param array $data
 *   An array of all information about Views tables and fields, collected from
 *   hook_views_data(), passed by reference.
 *
 * @see hook_views_data()
 */
function hook_views_data_alter(array &$data) {
  // Alter the title of the node:nid field in the Views UI.
  $data['node']['nid']['title'] = t('Node-Nid');

  // Add an additional field to the users table.
  $data['users']['example_field'] = array(
    'title' => t('Example field'),
    'help' => t('Some example content that references a user'),

    'field' => array(
      // ID of the field handler to use.
      'id' => 'example_field',
    ),
  );

  // Change the handler of the node title field, presumably to a handler plugin
  // you define in your module. Give the ID of this plugin.
  $data['node']['title']['field']['id'] = 'node_title';

  // Add a relationship that will allow a view whose base table is 'foo' (from
  // another module) to have a relationship to 'example_table' (from my module),
  // via joining foo.fid to example_table.eid.
  //
  // This relationship has to be added to the 'foo' Views data, which my module
  // does not control, so it must be done in hook_views_data_alter(), not
  // hook_views_data().
  //
  // In Views data definitions, each field can have only one relationship. So
  // rather than adding this relationship directly to the $data['foo']['fid']
  // field entry, which could overwrite an existing relationship, we define
  // a dummy field key to handle the relationship.
  $data['foo']['unique_dummy_name'] = array(
    'title' => t('Title seen while adding relationship'),
    'help' => t('More information about the relationship'),

    'relationship' => array(
      // Views name of the table being joined to from foo.
      'base' => 'example_table',
      // Database field name in example_table for the join.
      'base field' => 'eid',
      // Real database field name in foo for the join, to override
      // 'unique_dummy_name'.
      'field' => 'fid',
      // ID of relationship handler plugin to use.
      'id' => 'standard',
      'label' => t('Default label for relationship'),
    ),
  );

  // Note that the $data array is not returned – it is modified by reference.
}

/**
 * Override the default Views data for a Field API field.
 *
 * The field module's implementation of hook_views_data() invokes this for each
 * field storage, in the module that defines the field type. It is not invoked
 * in other modules.
 *
 * If no hook implementation exists, hook_views_data() falls back to
 * views_field_default_views_data().
 *
 * @param \Drupal\field\FieldStorageConfigInterface $field_storage
 *   The field storage config entity.
 *
 * @return array
 *   An array of views data, in the same format as the return value of
 *   hook_views_data().
 *
 * @see views_views_data()
 * @see hook_field_views_data_alter()
 * @see hook_field_views_data_views_data_alter()
 */
function hook_field_views_data(\Drupal\field\FieldStorageConfigInterface $field_storage) {
  $data = views_field_default_views_data($field_storage);
  foreach ($data as $table_name => $table_data) {
    // Add the relationship only on the target_id field.
    $data[$table_name][$field_storage->getName() . '_target_id']['relationship'] = array(
      'id' => 'standard',
      'base' => 'file_managed',
      'base field' => 'target_id',
      'label' => t('image from !field_name', array('!field_name' => $field_storage->getName())),
    );
  }

  return $data;
}

/**
 * Alter the Views data for a single Field API field.
 *
 * This is called on all modules even if there is no hook_field_views_data()
 * implementation for the field, and therefore may be used to alter the
 * default data that views_field_default_views_data() supplies for the
 * field storage.
 *
 *  @param array $data
 *    The views data for the field storage. This has the same format as the
 *    return value of hook_views_data().
 *  @param \Drupal\field\FieldStorageConfigInterface $field_storage
 *    The field storage config entity.
 *
 * @see views_views_data()
 * @see hook_field_views_data()
 * @see hook_field_views_data_views_data_alter()
 */
function hook_field_views_data_alter(array &$data, \Drupal\field\FieldStorageConfigInterface $field_storage) {
  $entity_type_id = $field_storage->getTargetEntityTypeId();
  $field_name = $field_storage->getName();
  $entity_type = \Drupal::entityManager()->getDefinition($entity_type_id);
  $pseudo_field_name = 'reverse_' . $field_name . '_' . $entity_type_id;
  $table_mapping = \Drupal::entityManager()->getStorage($entity_type_id)->getTableMapping();

  list($label) = views_entity_field_label($entity_type_id, $field_name);

  $data['file_managed'][$pseudo_field_name]['relationship'] = array(
    'title' => t('@entity using @field', array('@entity' => $entity_type->getLabel(), '@field' => $label)),
    'help' => t('Relate each @entity with a @field set to the image.', array('@entity' => $entity_type->getLabel(), '@field' => $label)),
    'id' => 'entity_reverse',
    'field_name' => $field_name,
    'entity_type' => $entity_type_id,
    'field table' => $table_mapping->getDedicatedDataTableName($field_storage),
    'field field' => $field_name . '_target_id',
    'base' => $entity_type->getBaseTable(),
    'base field' => $entity_type->getKey('id'),
    'label' => t('!field_name', array('!field_name' => $field_name)),
    'join_extra' => array(
      0 => array(
        'field' => 'deleted',
        'value' => 0,
        'numeric' => TRUE,
      ),
    ),
  );
}

/**
 * Alter the Views data on a per field basis.
 *
 * The field module's implementation of hook_views_data_alter() invokes this for
 * each field storage, in the module that defines the field type. It is not
 * invoked in other modules.
 *
 * Unlike hook_field_views_data_alter(), this operates on the whole of the views
 * data. This allows a field type to add data that concerns its fields in
 * other tables, which would not yet be defined at the point when
 * hook_field_views_data() and hook_field_views_data_alter() are invoked. For
 * example, entityreference adds reverse relationships on the tables for the
 * entities which are referenced by entityreference fields.
 *
 * (Note: this is weirdly named so as not to conflict with
 * hook_field_views_data_alter().)
 *
 * @param array $data
 *   The views data.
 * @param \Drupal\field\FieldStorageConfigInterface $field
 *   The field storage config entity.
 *
 * @see hook_field_views_data()
 * @see hook_field_views_data_alter()
 * @see views_views_data_alter()
 */
function hook_field_views_data_views_data_alter(array &$data, \Drupal\field\FieldStorageConfigInterface $field) {
  $field_name = $field->getName();
  $data_key = 'field_data_' . $field_name;
  $entity_type_id = $field->entity_type;
  $entity_type = \Drupal::entityManager()->getDefinition($entity_type_id);
  $pseudo_field_name = 'reverse_' . $field_name . '_' . $entity_type_id;
  list($label) = views_entity_field_label($entity_type_id, $field_name);
  $table_mapping = \Drupal::entityManager()->getStorage($entity_type_id)->getTableMapping();

  // Views data for this field is in $data[$data_key].
  $data[$data_key][$pseudo_field_name]['relationship'] = array(
    'title' => t('@entity using @field', array('@entity' => $entity_type->getLabel(), '@field' => $label)),
    'help' => t('Relate each @entity with a @field set to the term.', array('@entity' => $entity_type->getLabel(), '@field' => $label)),
    'id' => 'entity_reverse',
    'field_name' => $field_name,
    'entity_type' => $entity_type_id,
    'field table' => $table_mapping->getDedicatedDataTableName($field),
    'field field' => $field_name . '_target_id',
    'base' => $entity_type->getBaseTable(),
    'base field' => $entity_type->getKey('id'),
    'label' => t('!field_name', array('!field_name' => $field_name)),
    'join_extra' => array(
      0 => array(
        'field' => 'deleted',
        'value' => 0,
        'numeric' => TRUE,
      ),
    ),
  );
}

/**
 * Replace special strings in the query before it is executed.
 *
 * The idea is that certain dynamic values can be placed in a query when it is
 * built, and substituted at run-time, allowing the query to be cached and
 * still work correctly when executed.
 *
 * @param \Drupal\views\ViewExecutable $view
 *   The View being executed.
 *
 * @return array
 *   An associative array where each key is a string to be replaced, and the
 *   corresponding value is its replacement. The strings to replace are often
 *   surrounded with '***', as illustrated in the example implementation, to
 *   avoid collisions with other values in the query.
 */
function hook_views_query_substitutions(ViewExecutable $view) {
  // Example from views_views_query_substitutions().
  return array(
    '***CURRENT_VERSION***' => \Drupal::VERSION,
    '***CURRENT_TIME***' => REQUEST_TIME,
    '***LANGUAGE_language_content***' => \Drupal::languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId(),
    PluginBase::VIEWS_QUERY_LANGUAGE_SITE_DEFAULT => \Drupal::languageManager()->getDefaultLanguage()->getId(),
  );
}

/**
 * Replace special strings when processing a view with form elements.
 *
 * @return array
 *   An associative array where each key is a string to be replaced, and the
 *   corresponding value is its replacement.
 */
function hook_views_form_substitutions() {
  return array(
    '<!--views-form-example-substitutions-->' => 'Example Substitution',
  );
}

/**
 * Alter a view at the very beginning of Views processing.
 *
 * Output can be added to the view by setting $view->attachment_before
 * and $view->attachment_after.
 *
 * @param \Drupal\views\ViewExecutable $view
 *   The view object about to be processed.
 * @param string $display_id
 *   The machine name of the active display.
 * @param array $args
 *   An array of arguments passed into the view.
 *
 * @see \Drupal\views\ViewExecutable
 */
function hook_views_pre_view(ViewExecutable $view, $display_id, array &$args) {

  // Modify contextual filters for my_special_view if user has 'my special permission'.
  $account = \Drupal::currentUser();

  if ($view->name == 'my_special_view' && $account->hasPermission('my special permission') && $display_id == 'public_display') {
    $args[0] = 'custom value';
  }
}

/**
 * Act on the view before the query is built, but after displays are attached.
 *
 * Output can be added to the view by setting $view->attachment_before
 * and $view->attachment_after.
 *
 * @param \Drupal\views\ViewExecutable $view
 *   The view object about to be processed.
 *
 * @see \Drupal\views\ViewExecutable
 */
function hook_views_pre_build(ViewExecutable $view) {
  // Because of some unexplicable business logic, we should remove all
  // attachments from all views on Mondays.
  // (This alter could be done later in the execution process as well.)
  if (date('D') == 'Mon') {
    unset($view->attachment_before);
    unset($view->attachment_after);
  }
}

/**
 * Act on the view immediately after the query is built.
 *
 * Output can be added to the view by setting $view->attachment_before
 * and $view->attachment_after.
 *
 * @param \Drupal\views\ViewExecutable $view
 *   The view object about to be processed.
 *
 * @see \Drupal\views\ViewExecutable
 */
function hook_views_post_build(ViewExecutable $view) {
  // If the exposed field 'type' is set, hide the column containing the content
  // type. (Note that this is a solution for a particular view, and makes
  // assumptions about both exposed filter settings and the fields in the view.
  // Also note that this alter could be done at any point before the view being
  // rendered.)
  if ($view->name == 'my_view' && isset($view->exposed_raw_input['type']) && $view->exposed_raw_input['type'] != 'All') {
    // 'Type' should be interpreted as content type.
    if (isset($view->field['type'])) {
      $view->field['type']->options['exclude'] = TRUE;
    }
  }
}

/**
 * Act on the view after the query is built and just before it is executed.
 *
 * Output can be added to the view by setting $view->attachment_before
 * and $view->attachment_after.
 *
 * @param \Drupal\views\ViewExecutable $view
 *   The view object about to be processed.
 *
 * @see \Drupal\views\ViewExecutable
 */
function hook_views_pre_execute(ViewExecutable $view) {
  // Whenever a view queries more than two tables, show a message that notifies
  // view administrators that the query might be heavy.
  // (This action could be performed later in the execution process, but not
  // earlier.)
  $account = \Drupal::currentUser();

  if (count($view->query->tables) > 2 && $account->hasPermission('administer views')) {
    drupal_set_message(t('The view %view may be heavy to execute.', array('%view' => $view->name)), 'warning');
  }
}

/**
 * Act on the view immediately after the query has been executed.
 *
 * At this point the query has been executed, but the preRender() phase has
 * not yet happened for handlers.
 *
 * Output can be added to the view by setting $view->attachment_before
 * and $view->attachment_after.
 *
 * @param \Drupal\views\ViewExecutable $view
 *   The view object about to be processed.
 *
 * @see \Drupal\views\ViewExecutable
 */
function hook_views_post_execute(ViewExecutable $view) {
  // If there are more than 100 results, show a message that encourages the user
  // to change the filter settings.
  // (This action could be performed later in the execution process, but not
  // earlier.)
  if ($view->total_rows > 100) {
    drupal_set_message(t('You have more than 100 hits. Use the filter settings to narrow down your list.'));
  }
}

/**
 * Act on the view immediately before rendering it.
 *
 * At this point the query has been executed, and the preRender() phase has
 * already happened for handlers, so all data should be available. This hook
 * can be used by themes.
 *
 * Output can be added to the view by setting $view->attachment_before
 * and $view->attachment_after.
 *
 * @param \Drupal\views\ViewExecutable $view
 *   The view object about to be processed.
 *
 * @see \Drupal\views\ViewExecutable
 */
function hook_views_pre_render(ViewExecutable $view) {
  // Scramble the order of the rows shown on this result page.
  // Note that this could be done earlier, but not later in the view execution
  // process.
  shuffle($view->result);
}

/**
 * Post-process any rendered data.
 *
 * This can be valuable to be able to cache a view and still have some level of
 * dynamic output. In an ideal world, the actual output will include HTML
 * comment-based tokens, and then the post process can replace those tokens.
 * This hook can be used by themes.
 *
 * Example usage. If it is known that the view is a node view and that the
 * primary field will be a nid, you can do something like this:
 * @code
 *   <!--post-FIELD-NID-->
 * @encode
 * And then in the post-render, create an array with the text that should
 * go there:
 * @code
 *   strtr($output, array('<!--post-FIELD-1-->' => 'output for FIELD of nid 1');
 * @encode
 * All of the cached result data will be available in $view->result, as well,
 * so all ids used in the query should be discoverable.
 *
 * @param \Drupal\views\ViewExecutable $view
 *   The view object about to be processed.
 * @param string $output
 *   A flat string with the rendered output of the view.
 * @param CacheBackendInterface $cache
 *   The cache settings.
 *
 * @see \Drupal\views\ViewExecutable
 */
function hook_views_post_render(ViewExecutable $view, &$output, CacheBackendInterface $cache) {
  // When using full pager, disable any time-based caching if there are fewer
  // than 10 results.
  if ($view->pager instanceof Drupal\views\Plugin\views\pager\Full && $cache instanceof Drupal\views\Plugin\views\cache\Time && count($view->result) < 10) {
    $cache->options['results_lifespan'] = 0;
    $cache->options['output_lifespan'] = 0;
  }
}

/**
 * Alter the query before it is executed.
 *
 * @param \Drupal\views\ViewExecutable $view
 *   The view object about to be processed.
 * @param QueryPluginBase $query
 *   The query plugin object for the query.
 *
 * @see hook_views_query_substitutions()
 * @see \Drupal\views\Plugin\views\query\Sql
 */
function hook_views_query_alter(ViewExecutable $view, QueryPluginBase $query) {
  // (Example assuming a view with an exposed filter on node title.)
  // If the input for the title filter is a positive integer, filter against
  // node ID instead of node title.
  if ($view->name == 'my_view' && is_numeric($view->exposed_raw_input['title']) && $view->exposed_raw_input['title'] > 0) {
    // Traverse through the 'where' part of the query.
    foreach ($query->where as &$condition_group) {
      foreach ($condition_group['conditions'] as &$condition) {
        // If this is the part of the query filtering on title, chang the
        // condition to filter on node ID.
        if ($condition['field'] == 'node.title') {
          $condition = array(
            'field' => 'node.nid',
            'value' => $view->exposed_raw_input['title'],
            'operator' => '=',
          );
        }
      }
    }
  }
}

/**
 * Alter the view preview information.
 *
 * The view preview information is optionally displayed when a view is
 * previewed in the administrative UI. It includes query and performance
 * statistics.
 *
 * @param array $rows
 *   An associative array with two keys:
 *   - query: An array of rows suitable for '#type' => 'table', containing
 *     information about the query and the display title and path.
 *   - statistics: An array of rows suitable for '#type' => 'table',
 *     containing performance statistics.
 * @param \Drupal\views\ViewExecutable $view
 *   The view object.
 *
 * @see \Drupal\views_ui\ViewUI
 * @see table.html.twig
 */
function hook_views_preview_info_alter(array &$rows, ViewExecutable $view) {
  // Adds information about the tables being queried by the view to the query
  // part of the info box.
  $rows['query'][] = array(
    t('<strong>Table queue</strong>'),
    count($view->query->table_queue) . ': (' . implode(', ', array_keys($view->query->table_queue)) . ')',
  );
}

/**
 * Alter the links displayed at the top of the view edit form.
 *
 * @param array $links
 *   A renderable array of links which will be displayed at the top of the
 *   view edit form. Each entry will be in a form suitable for
 *   '#theme' => 'links'.
 * @param \Drupal\views\ViewExecutable $view
 *   The view object being edited.
 * @param string $display_id
 *   The ID of the display being edited, e.g. 'default' or 'page_1'.
 *
 * @see \Drupal\views_ui\ViewUI::renderDisplayTop()
 */
function hook_views_ui_display_top_links_alter(array &$links, ViewExecutable $view, $display_id) {
  // Put the export link first in the list.
  if (isset($links['export'])) {
    $links = array('export' => $links['export']) + $links;
  }
}

// @todo Describe how to alter a view ajax response with event listeners.

/**
 * Allow modules to respond to the invalidation of the Views cache.
 *
 * This hook will fire whenever a view is enabled, disabled, created,
 * updated, or deleted.
 *
 * @see views_invalidate_cache()
 */
function hook_views_invalidate_cache() {
  \Drupal\Core\Cache\Cache::invalidateTags(array('views'));
}

/**
 * Modify the list of available views access plugins.
 *
 * This hook may be used to modify plugin properties after they have been
 * specified by other modules.
 *
 * @param array $plugins
 *   An array of all the existing plugin definitions, passed by reference.
 *
 * @see \Drupal\views\Plugin\ViewsPluginManager
 */
function hook_views_plugins_access_alter(array &$plugins) {
  // Remove the available plugin because the users should not have access to it.
  unset($plugins['role']);
}

/**
 * Modify the list of available views default argument plugins.
 *
 * This hook may be used to modify plugin properties after they have been
 * specified by other modules.
 *
 * @param array $plugins
 *   An array of all the existing plugin definitions, passed by reference.
 *
 * @see \Drupal\views\Plugin\ViewsPluginManager
 */
function hook_views_plugins_argument_default_alter(array &$plugins) {
  // Remove the available plugin because the users should not have access to it.
  unset($plugins['php']);
}

/**
 * Modify the list of available views argument validation plugins.
 *
 * This hook may be used to modify plugin properties after they have been
 * specified by other modules.
 *
 * @param array $plugins
 *   An array of all the existing plugin definitions, passed by reference.
 *
 * @see \Drupal\views\Plugin\ViewsPluginManager
 */
function hook_views_plugins_argument_validator_alter(array &$plugins) {
  // Remove the available plugin because the users should not have access to it.
  unset($plugins['php']);
}

/**
 * Modify the list of available views cache plugins.
 *
 * This hook may be used to modify plugin properties after they have been
 * specified by other modules.
 *
 * @param array $plugins
 *   An array of all the existing plugin definitions, passed by reference.
 *
 * @see \Drupal\views\Plugin\ViewsPluginManager
 */
function hook_views_plugins_cache_alter(array &$plugins) {
  // Change the title.
  $plugins['time']['title'] = t('Custom title');
}

/**
 * Modify the list of available views display extender plugins.
 *
 * This hook may be used to modify plugin properties after they have been
 * specified by other modules.
 *
 * @param array $plugins
 *   An array of all the existing plugin definitions, passed by reference.
 *
 * @see \Drupal\views\Plugin\ViewsPluginManager
 */
function hook_views_plugins_display_extenders_alter(array &$plugins) {
  // Alter the title of an existing plugin.
  $plugins['time']['title'] = t('Custom title');
}

/**
 * Modify the list of available views display plugins.
 *
 * This hook may be used to modify plugin properties after they have been
 * specified by other modules.
 *
 * @param array $plugins
 *   An array of all the existing plugin definitions, passed by reference.
 *
 * @see \Drupal\views\Plugin\ViewsPluginManager
 */
function hook_views_plugins_display_alter(array &$plugins) {
  // Alter the title of an existing plugin.
  $plugins['rest_export']['title'] = t('Export');
}

/**
 * Modify the list of available views exposed form plugins.
 *
 * This hook may be used to modify plugin properties after they have been
 * specified by other modules.
 *
 * @param array $plugins
 *   An array of all the existing plugin definitions, passed by reference.
 *
 * @see \Drupal\views\Plugin\ViewsPluginManager
 */
function hook_views_plugins_exposed_form_alter(array &$plugins) {
  // Remove the available plugin because the users should not have access to it.
  unset($plugins['input_required']);
}

/**
 * Modify the list of available views join plugins.
 *
 * This hook may be used to modify plugin properties after they have been
 * specified by other modules.
 *
 * @param array $plugins
 *   An array of all the existing plugin definitions, passed by reference.
 *
 * @see \Drupal\views\Plugin\ViewsPluginManager
 */
function hook_views_plugins_join_alter(array &$plugins) {
  // Print out all join plugin names for debugging purposes.
  debug($plugins);
}

/**
 * Modify the list of available views join plugins.
 *
 * This hook may be used to modify plugin properties after they have been
 * specified by other modules.
 *
 * @param array $plugins
 *   An array of all the existing plugin definitions, passed by reference.
 *
 * @see \Drupal\views\Plugin\ViewsPluginManager
 */
function hook_views_plugins_pager_alter(array &$plugins) {
  // Remove the sql based plugin to force good performance.
  unset($plugins['full']);
}

/**
 * Modify the list of available views query plugins.
 *
 * This hook may be used to modify plugin properties after they have been
 * specified by other modules.
 *
 * @param array $plugins
 *   An array of all the existing plugin definitions, passed by reference.
 *
 * @see \Drupal\views\Plugin\ViewsPluginManager
 */
function hook_views_plugins_query_alter(array &$plugins) {
  // Print out all query plugin names for debugging purposes.
  debug($plugins);
}

/**
 * Modify the list of available views row plugins.
 *
 * This hook may be used to modify plugin properties after they have been
 * specified by other modules.
 *
 * @param array $plugins
 *   An array of all the existing plugin definitions, passed by reference.
 *
 * @see \Drupal\views\Plugin\ViewsPluginManager
 */
function hook_views_plugins_row_alter(array &$plugins) {
  // Change the used class of a plugin.
  $plugins['entity:node']['class'] = 'Drupal\node\Plugin\views\row\NodeRow';
  $plugins['entity:node']['module'] = 'node';
}

/**
 * Modify the list of available views style plugins.
 *
 * This hook may be used to modify plugin properties after they have been
 * specified by other modules.
 *
 * @param array $plugins
 *   An array of all the existing plugin definitions, passed by reference.
 *
 * @see \Drupal\views\Plugin\ViewsPluginManager
 */
function hook_views_plugins_style_alter(array &$plugins) {
  // Change the theme hook of a plugin.
  $plugins['html_list']['theme'] = 'custom_views_view_list';
}

/**
 * Modify the list of available views wizard plugins.
 *
 * This hook may be used to modify plugin properties after they have been
 * specified by other modules.
 *
 * @param array $plugins
 *   An array of all the existing plugin definitions, passed by reference.
 *
 * @see \Drupal\views\Plugin\ViewsPluginManager
 */
function hook_views_plugins_wizard_alter(array &$plugins) {
  // Change the title of a plugin.
  $plugins['node_revision']['title'] = t('Node revision wizard');
}

/**
 * Modify the list of available views area handler plugins.
 *
 * This hook may be used to modify handler properties after they have been
 * specified by other modules.
 *
 * @param array $plugins
 *   An array of all the existing handler definitions, passed by reference.
 *
 * @see \Drupal\views\Plugin\ViewsHandlerManager
 */
function hook_views_plugins_area_alter(array &$plugins) {
  // Change the 'title' handler class.
  $plugins['title']['class'] = 'Drupal\\example\\ExampleClass';
}

/**
 * Modify the list of available views argument handler plugins.
 *
 * This hook may be used to modify handler properties after they have been
 * specified by other modules.
 *
 * @param array $plugins
 *   An array of all the existing handler definitions, passed by reference.
 *
 * @see \Drupal\views\Plugin\ViewsHandlerManager
 */
function hook_views_plugins_argument_alter(array &$plugins) {
  // Change the 'title' handler class.
  $plugins['title']['class'] = 'Drupal\\example\\ExampleClass';
}

/**
 * Modify the list of available views field handler plugins.
 *
 * This hook may be used to modify handler properties after they have been
 * specified by other modules.
 *
 * @param array $plugins
 *   An array of all the existing handler definitions, passed by reference.
 *
 * @see \Drupal\views\Plugin\ViewsHandlerManager
 */
function hook_views_plugins_field_alter(array &$plugins) {
  // Change the 'title' handler class.
  $plugins['title']['class'] = 'Drupal\\example\\ExampleClass';
}

/**
 * Modify the list of available views filter handler plugins.
 *
 * This hook may be used to modify handler properties after they have been
 * specified by other modules.
 *
 * @param array $plugins
 *   An array of all the existing handler definitions, passed by reference.
 *
 * @see \Drupal\views\Plugin\ViewsHandlerManager
 */
function hook_views_plugins_filter_alter(array &$plugins) {
  // Change the 'title' handler class.
  $plugins['title']['class'] = 'Drupal\\example\\ExampleClass';
}

/**
 * Modify the list of available views relationship handler plugins.
 *
 * This hook may be used to modify handler properties after they have been
 * specified by other modules.
 *
 * @param array $plugins
 *   An array of all the existing handler definitions, passed by reference.
 *
 * @see \Drupal\views\Plugin\ViewsHandlerManager
 */
function hook_views_plugins_relationship_alter(array &$plugins) {
  // Change the 'title' handler class.
  $plugins['title']['class'] = 'Drupal\\example\\ExampleClass';
}

/**
 * Modify the list of available views sort handler plugins.
 *
 * This hook may be used to modify handler properties after they have been
 * specified by other modules.
 *
 * @param array $plugins
 *   An array of all the existing handler definitions, passed by reference.
 *
 * @see \Drupal\views\Plugin\ViewsHandlerManager
 */
function hook_views_plugins_sort_alter(array &$plugins) {
  // Change the 'title' handler class.
  $plugins['title']['class'] = 'Drupal\\example\\ExampleClass';
}

/**
 * @}
 */
