<?php

/**
 * @file
 * Describes hooks and plugins provided by the Views module.
 */

/**
 * @defgroup views_plugins Views plugins
 *
 * Views plugins are objects that are used to build and render the view.
 * Plugins are registered by extending one of the Views base plugin classes
 * and defining settings in the plugin annotation.
 *
 * Views has the following types of plugins:
 * - Access: Access plugins are responsible for controlling access to the
 *   view. Views includes plugins for checking user roles and individual
 *   permissions. Access plugins extend
 *   \Drupal\views\Plugin\views\access\AccessPluginBase.
 * - Argument default: Argument default plugins allow pluggable ways of
 *   providing default values for contextual filters. This is useful for
 *   blocks and other display types lacking a natural argument input.
 *   Examples are plugins to extract node and user IDs from the URL. Argument
 *   default plugins extend
 *   \Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase.
 * - Argument validator: Validator plugins can ensure arguments are valid,
 *   and even do transformations on the arguments. They can also provide
 *   replacement patterns for the view title. For example, the 'content'
 *   validator verifies verifies that the argument value corresponds to a
 *   node, loads that node and provides the node title as a replacement
 *   pattern. Argument validator plugins extend
 *   \Drupal\views\Plugin\views\argument_validator\ArgumentValidatorPluginBase.
 * - Cache: Cache plugins control the storage and loading of caches.
 *   Currently they can do both result and render caching. It might also be
 *   possible to cache the generated query. Cache plugins extend
 *   \Drupal\views\Plugin\views\cache\CachePluginBase.
 * - Display: Display plugins are responsible for controlling where a View is
 *   rendered; that is, how it is exposed to other parts of Drupal. 'Page'
 *   and 'block' are the most commonly used display plugins. Each View also
 *   has a 'master' (or 'default') display that includes information shared
 *   between all its displays. (See
 *   \Drupal\views\Plugin\views\display\DefaultDisplay.) Display plugins extend
 *   \Drupal\views\Plugin\views\display\DisplayPluginBase.
 * - Display extender: Display extender plugins allow additional options or
 *   configurations to added to views across all display types. For example,
 *   if you wanted to allow site users to add certain metadata to the rendered
 *   output of every view display regardless of display type, you could provide
 *   this option as a display extender. Display extender plugins extend
 *   \Drupal\views\Plugin\views\display_extender\DisplayExtenderPluginBase.
 * - Exposed form: Exposed form plugins are responsible for building,
 *   rendering, and controlling exposed forms. Exposed form plugins extend
 *   \Drupal\views\Plugin\views\display\DisplayPluginBase.
 * - Handlers: Handler plugins help build the view query object that the query
 *   plugin then executes to retrieve the data from the storage backend (see
 *   below). There are several types of handlers:
 *   - Area handlers: Extend \Drupal\views\Plugin\views\area\AreaHandlerBase
 *   - Argument handlers: Extend
 *     \Drupal\views\Plugin\views\argument\ArgumentHandlerBase
 *   - Field handlers: Extend \Drupal\views\Plugin\views\field\FieldHandlerBase
 *   - Filter handlers: Extend
 *     \Drupal\views\Plugin\views\filter\FilterHandlerBase
 *   - Relationship handlers:
 *     Extend \Drupal\views\Plugin\views\relationship\RelationshipHandlerBase
 *   - Sort handlers: Extend \Drupal\views\Plugin\views\sort:SortHandlerBase
 * - Pager: Pager plugins take care of everything regarding pagers, including
 *   getting setting the total number of items to render the pager and
 *   setting the global pager arrays. Pager plugins extend
 *   \Drupal\views\Plugin\views\pager\PagerPluginBase.
 * - Query: Query plugins generate and execute a built query object against a
 *   particular storage backend, converting the Views query object into an
 *   actual query. The only default implementation is SQL. (Note that most
 *   handler plugins currently rely on the SQL query plugin.) Query plugins
 *   extend \Drupal\views\Plugin\views\query\QueryPluginBase.
 * - Row style: Row styles handle rendering each individual record from the
 *   main view table. The two default implementations render the entire entity
 *   (nodes only), or selected fields. Row style plugins extend
 *   \Drupal\views\Plugin\views\row\RowPluginBase).
 * - Style: Style plugins control how a view is displayed. For the most part
 *   they are object wrappers around theme templates. Examples of styles
 *   include HTML lists, tables, etc. Style plugins extend
 *   \Drupal\views\Plugin\views\style\StylePluginBase.
 *
 * @todo Add an explanation for each type of handler.
 * @todo Document how to use annotations and what goes in them.
 * @todo Add @ingroup to all the base plugins for this group.
 * @todo Add a separate @ingroup for all plugins?
 * @todo Document specific options on the appropriate plugin base classes.
 * @todo Add examples.
 *
 * @see \Drupal\views\Plugin\views\PluginBase
 * @see \Drupal\views\Plugin\views\HandlerBase
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
 * Describe data tables (or the equivalent) to Views.
 *
 * The data described with this hook is fetched and retrieved by
 * \Drupal\views\Views::viewsData()->get().
 *
 * @return array
 *   An associative array describing the data structure. Primary key is the
 *   name used internally by Views for the table(s) – usually the actual table
 *   name. The values for the key entries are described in detail below.
 */
function hook_views_data() {
  // This example describes how to write hook_views_data() for the following
  // table:
  //
  // CREATE TABLE example_table (
  //   nid INT(11) NOT NULL         COMMENT 'Primary key; refers to {node}.nid.',
  //   plain_text_field VARCHAR(32) COMMENT 'Just a plain text field.',
  //   numeric_field INT(11)        COMMENT 'Just a numeric field.',
  //   boolean_field INT(1)         COMMENT 'Just an on/off field.',
  //   timestamp_field INT(8)       COMMENT 'Just a timestamp field.',
  //   PRIMARY KEY(nid)
  // );

  // First, the entry $data['example_table']['table'] describes properties of
  // the actual table – not its content.

  // The 'group' index will be used as a prefix in the UI for any of this
  // table's fields, sort criteria, etc. so it's easy to tell where they came
  // from.
  $data['example_table']['table']['group'] = t('Example table');

  // Define this as a base table – a table that can be described in itself by
  // views (and not just being brought in as a relationship). In reality this
  // is not very useful for this table, as it isn't really a distinct object of
  // its own, but it makes a good example.
  $data['example_table']['table']['base'] = array(
    'field' => 'nid', // This is the identifier field for the view.
    'title' => t('Example table'),
    'help' => t('Example table contains example content and can be related to nodes.'),
    'weight' => -10,
  );

  // This table references the {node} table. The declaration below creates an
  // 'implicit' relationship to the node table, so that when 'node' is the base
  // table, the fields are automatically available.
  $data['example_table']['table']['join'] = array(
    // Index this array by the table name to which this table refers.
    // 'left_field' is the primary key in the referenced table.
    // 'field' is the foreign key in this table.
    'node' => array(
      'left_field' => 'nid',
      'field' => 'nid',
    ),
  );

  // Next, describe each of the individual fields in this table to Views. This
  // is done by describing $data['example_table']['FIELD_NAME']. This part of
  // the array may then have further entries:
  //   - title: The label for the table field, as presented in Views.
  //   - help: The description text for the table field.
  //   - relationship: A description of any relationship handler for the table
  //     field.
  //   - field: A description of any field handler for the table field.
  //   - sort: A description of any sort handler for the table field.
  //   - filter: A description of any filter handler for the table field.
  //   - argument: A description of any argument handler for the table field.
  //   - area: A description of any handler for adding content to header,
  //     footer or as no result behavior.
  //
  // The handler descriptions are described with examples below.

  // Node ID table field.
  $data['example_table']['nid'] = array(
    'title' => t('Example content'),
    'help' => t('Some example content that references a node.'),
    // Define a relationship to the {node} table, so example_table views can
    // add a relationship to nodes. If you want to define a relationship the
    // other direction, use hook_views_data_alter(), or use the 'implicit' join
    // method described above.
    'relationship' => array(
      'base' => 'node', // The name of the table to join with
      'field' => 'nid', // The name of the field to join with
      'id' => 'standard',
      'label' => t('Example node'),
    ),
  );

  // Example plain text field.
  $data['example_table']['plain_text_field'] = array(
    'title' => t('Plain text field'),
    'help' => t('Just a plain text field.'),
    'field' => array(
      'id' => 'standard',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
  );

  // Example numeric text field.
  $data['example_table']['numeric_field'] = array(
    'title' => t('Numeric field'),
    'help' => t('Just a numeric field.'),
    'field' => array(
      'id' => 'numeric',
     ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  // Example boolean field.
  $data['example_table']['boolean_field'] = array(
    'title' => t('Boolean field'),
    'help' => t('Just an on/off field.'),
    'field' => array(
      'id' => 'boolean',
    ),
    'filter' => array(
      'id' => 'boolean',
      // Note that you can override the field-wide label:
      'label' => t('Published'),
      // This setting is used by the boolean filter handler, as possible option.
      'type' => 'yes-no',
      // use boolean_field = 1 instead of boolean_field <> 0 in WHERE statement.
      'use_equal' => TRUE,
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  // Example timestamp field.
  $data['example_table']['timestamp_field'] = array(
    'title' => t('Timestamp field'),
    'help' => t('Just a timestamp field.'),
    'field' => array(
      'id' => 'date',
    ),
    'sort' => array(
      'id' => 'date',
    ),
    'filter' => array(
      'id' => 'date',
    ),
  );

  return $data;
}

/**
 * Alter the table structure defined by hook_views_data().
 *
 * @param array $data
 *   An array of all Views data, passed by reference. See hook_views_data() for
 *   structure.
 *
 * @see hook_views_data()
 */
function hook_views_data_alter(array &$data) {
  // This example alters the title of the node:nid field in the Views UI.
  $data['node']['nid']['title'] = t('Node-Nid');

  // This example adds an example field to the users table.
  $data['users']['example_field'] = array(
    'title' => t('Example field'),
    'help' => t('Some example content that references a user'),
    'handler' => 'hook_handlers_field_example_field',
    'field' => array(
      'id' => 'example_field',
    ),
  );

  // This example changes the handler of the node title field.
  // In this handler you could do stuff, like preview of the node when clicking
  // the node title.
  $data['node']['title']['field']['id'] = 'node_title';

  // This example adds a relationship to table {foo}, so that 'foo' views can
  // add this table using a relationship. Because we don't want to write over
  // the primary key field definition for the {foo}.fid field, we use a dummy
  // field name as the key.
  $data['foo']['dummy_name'] = array(
    'title' => t('Example relationship'),
    'help' => t('Example help'),
    'relationship' => array(
      'base' => 'example_table', // Table we're joining to.
      'base field' => 'eid', // Field on the joined table.
      'field' => 'fid', // Real field name on the 'foo' table.
      'id' => 'standard',
      'label' => t('Default label for relationship'),
      'title' => t('Title seen when adding relationship'),
      'help' => t('More information about relationship.'),
    ),
  );

  // Note that the $data array is not returned – it is modified by reference.
}

/**
 * Replace special strings in the query before it is executed.
 *
 * @param \Drupal\views\ViewExecutable $view
 *   The View being executed.
 * @return array
 *   An associative array where each key is a string to be replaced, and the
 *   corresponding value is its replacement. The strings to replace are often
 *   surrounded with '***', as illustrated in the example implementation.
 */
function hook_views_query_substitutions(ViewExecutable $view) {
  // Example from views_views_query_substitutions().
  return array(
    '***CURRENT_VERSION***' => \Drupal::VERSION,
    '***CURRENT_TIME***' => REQUEST_TIME,
    '***CURRENT_LANGUAGE***' => \Drupal::languageManager()->getCurrentLanguage(\Drupal\Core\Language\Language::TYPE_CONTENT)->id,
    '***DEFAULT_LANGUAGE***' => \Drupal::languageManager()->getDefaultLanguage()->id,
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
  if ($view->name == 'my_special_view' && user_access('my special permission')) {
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
  if (count($view->query->tables) > 2 && user_access('administer views')) {
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
 * can be utilized by themes.
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
 * This hook can be utilized by themes.
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
 * @see theme_table()
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
  \Drupal\Core\Cache\Cache::invalidateTags(array('views' => TRUE));
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
 * @}
 */
