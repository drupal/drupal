<?php

/**
 * @file
 * Describe hooks provided by the Views module.
 */

/**
 * @defgroup views_handlers About Views handlers
 * @{
 * In Views, a handler is an object that is part of the view and is part of the
 * query building flow.
 *
 * Handlers are objects; much of the time, the base handlers will work, but
 * often you'll need to override the handler to achieve something meaningful.
 * One typical handler override will be views_handler_filter_operator_in which
 * allows you to have a filter select from a list of options; you'll need to
 * override this to provide your list.
 *
 * Handlers have two distinct code flows; the UI flow and the view building
 * flow.
 *
 * For the query flow:
 * - handler->construct()
 *   - Create the initial handler; at this time it is not yet attached to a
 *     view. It is here that you can set basic defaults if needed, but there
 *     will be no knowledge of the environment yet.
 * - handler->setDefinition()
 *   - Set the data from hook_views_data() relevant to the handler.
 * - handler->init()
 *   - Attach the handler to a view, and usually provides the options from the
 *     display.
 * - handler->preQuery()
 *   - Run prior to the query() stage to do early processing.
 * - handler->query()
 *   - Do the bulk of the work this handler needs to do to add itself to the
 *     query.
 *
 * Fields, being the only handlers concerned with output, also have an extended
 * piece of the flow:
 *
 * - handler->pre_render(&$values)
 *   - Called prior to the actual rendering, this allows handlers to query for
 *     extra data; the entire resultset is available here, and this is where
 *     items that have "multiple values" per record can do their extra query for
 *     all of the records available. There are several examples of this at work
 *     in the code, see for example views_handler_field_user_roles.
 * - handler->render()
 *   - This does the actual work of rendering the field.
 *
 * Most handlers are just extensions of existing classes with a few tweaks that
 * are specific to the field in question. For example,
 * views_handler_filter_in_operator provides a simple mechanism to set a
 * multiple-value list for setting filter values. Below,
 * views_handler_filter_node_type overrides the list options, but inherits
 * everything else.
 *
 * @code
 * class views_handler_filter_node_type extends views_handler_filter_in_operator {
 *   function get_value_options() {
 *     if (!isset($this->value_options)) {
 *       $this->value_title = t('Node type');
 *       $types = node_get_types();
 *       foreach ($types as $type => $info) {
 *         $options[$type] = $info-&gt;name;
 *       }
 *       $this->value_options = $options;
 *     }
 *   }
 * }
 * @endcode
 *
 * Handlers are stored in their own files and loaded on demand. Like all other
 * module files, they must first be registered through the module's info file.
 * For example:
 *
 * @code
 * name = Example module
 * description = "Gives an example of a module."
 * core = 8.x
 * files[] = example.module
 * files[] = example.install
 *
 * ; Views handlers
 * files[] = includes/views/handlers/example_handler_argument_string.inc
 * @endcode
 *
 * The best place to learn more about handlers and how they work is to explore
 * @link views_handlers Views' handlers @endlink and use existing handlers as a
 * guide and a model. Understanding how views_handler and its child classes work
 * is handy but you can do a lot just following these models. You can also
 * explore the views module directory, particularly node.views.inc.
 *
 * Please note that while all handler names in views are prefixed with views_,
 * you should use your own module's name to prefix your handler names in order
 * to ensure namespace safety. Note that the basic pattern for handler naming
 * goes like this:
 *
 * [module]_handler_[type]_[tablename]_[fieldname].
 *
 * Sometimes table and fieldname are not appropriate, but something that
 * resembles what the table/field would be can be used.
 *
 * See also:
 * - @link views_field_handlers Views field handlers @endlink
 * - @link views_sort_handlers Views sort handlers @endlink
 * - @link views_filter_handlers Views filter handlers @endlink
 * - @link views_argument_handlers Views argument handlers @endlink
 * - @link views_relationship_handlers Views relationship handlers @endlink
 * - @link views_area_handlers Views area handlers @endlink
 * @}
 */

/**
 * @defgroup views_plugins About Views plugins
 *
 * In Views, a plugin is a bit like a handler, but plugins are not directly
 * responsible for building the query. Instead, they are objects that are used
 * to display the view or make other modifications.
 *
 * There are 10 types of plugins in Views:
 * - Display: Display plugins are responsible for controlling *where* a view
 *   lives; that is, how they are being exposed to other parts of Drupal. Page
 *   and block are the most common displays, as well as the ubiquitous 'master'
 *   (or 'default') display.
 * - Style: Style plugins control how a view is displayed. For the most part
 *   they are object wrappers around theme templates. Styles could for example
 *   be HTML lists or tables.
 * - Row style: Row styles handle each individual record from the main view
 *   table. The two included by default render the entire entity (nodes only),
 *   or selected fields.
 * - Argument default: Argument default plugins allow pluggable ways of
 *   providing default values for contextual filters (previously 'arguments').
 *   This is useful for blocks and other display types lacking a natural
 *   argument input. Examples are plugins to extract node and user IDs from the
 *   URL.
 * - Argument validator: Validator plugins can ensure arguments are valid, and
 *   even do transformations on the arguments. They can also provide replacement
 *   patterns for the view title. For example, the 'content' validator
 *   verifies verifies that the argument value corresponds to a node, loads
 *   that node and provides the node title as a replacement pattern.
 * - Access: Access plugins are responsible for controlling access to the view.
 *   Views includes plugins for checking user roles and individual permissions.
 * - Query: Query plugins generate and execute a query, so they can be seen as
 *   a data backend. The default implementation is using SQL. There are
 *   contributed modules reading data from other sources, see for example the
 *   Views XML Backend module.
 * - Cache: Cache plugins control the storage and loading of caches. Currently
 *   they can do both result and render caching, but maybe one day cache the
 *   generated query.
 * - Pager plugins: Pager plugins take care of everything regarding pagers.
 *   From getting and setting the total amount of items to render the pager and
 *   setting the global pager arrays.
 * - Exposed form plugins: Exposed form plugins are responsible for building,
 *   rendering and controlling exposed forms. They can expose new parts of the
 *   view to the user and more.
 * - Localization plugins: Localization plugins take care how the view options
 *   are translated. There are example implementations for t(), 'no
 *   translation' and i18n.
 * - Display extenders: Display extender plugins allow scaling of views options
 *   horizontally. This means that you can add options and do stuff on all
 *   views displays. One theoretical example is metatags for views.
 *
 * Plugins are registered by extending one of the Views base plugin classes
 * and defining settings in the plugin annotation.
 *
 * @todo Document how to use annotations and what goes in them.
 * @todo Add @ingroup to all the base plugins for this group.
 * @todo Add a separate @ingroup for all plugins?
 * @todo Document specific options on the appropriate plugin base classes.
 * @todo Add examples.
 *
 * @see Drupal\views\Plugin\views\PluginBase
 */

/**
 * @defgroup views_hooks Views hooks
 * @{
 * Hooks that allow other modules to implement the Views API.
 */

/**
 * Describe data tables (or the equivalent) to Views.
 *
 * The data described with this hook is fetched and retrieved by
 * views_fetch_data().
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
  //     footer or as no result behaviour.
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
      'click sortable' => TRUE, // This is use by the table display plugin.
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
      'click sortable' => TRUE,
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
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'boolean',
      // Note that you can override the field-wide label:
      'label' => t('Published'),
      // This setting is used by the boolean filter handler, as possible option.
      'type' => 'yes-no',
      // use boolean_field = 1 instead of boolean_field <> 0 in WHERE statment.
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
      'click sortable' => TRUE,
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
  );

  // This example changes the handler of the node title field.
  // In this handler you could do stuff, like preview of the node when clicking
  // the node title.
  $data['node']['title']['handler'] = 'modulename_handlers_field_node_title';

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
 * Performs replacements in the query before being performed.
 *
 * @param $view
 *   The View being executed.
 * @return
 *   An array with keys being the strings to replace, and the values the strings
 *   to replace them with. The strings to replace are ofted surrounded with
 *   '***', as illustrated in the example implementation.
 */
function hook_views_query_substitutions($view) {
  // Example from views_views_query_substitutions().
  return array(
    '***CURRENT_VERSION***' => VERSION,
    '***CURRENT_TIME***' => REQUEST_TIME,
    '***CURRENT_LANGUAGE***' => language(LANGUAGE_TYPE_CONTENT)->langcode,
    '***DEFAULT_LANGUAGE***' => language_default()->langcode,
  );
}

/**
 * This hook is called to get a list of placeholders and their substitutions,
 * used when preprocessing a View with form elements.
 *
 * @return
 *   An array with keys being the strings to replace, and the values the strings
 *   to replace them with.
 */
function hook_views_form_substitutions() {
  return array(
    '<!--views-form-example-substitutions-->' => 'Example Substitution',
  );
}

/**
 * Allows altering a view at the very beginning of views processing, before
 * anything is done.
 *
 * Adding output to the view can be accomplished by placing text on
 * $view->attachment_before and $view->attachment_after.
 * @param $view
 *   The view object about to be processed.
 * @param $display_id
 *   The machine name of the active display.
 * @param $args
 *   An array of arguments passed into the view.
 */
function hook_views_pre_view(&$view, &$display_id, &$args) {
  // Change the display if the acting user has 'administer site configuration'
  // permission, to display something radically different.
  // (Note that this is not necessarily the best way to solve that task. Feel
  // free to contribute another example!)
  if (
    $view->name == 'my_special_view' &&
    user_access('administer site configuration') &&
    $display_id == 'public_display'
  ) {
    $display_id = 'private_display';
  }
}

/**
 * This hook is called right before the build process, but after displays
 * are attached and the display performs its pre_execute phase.
 *
 * Adding output to the view can be accomplished by placing text on
 * $view->attachment_before and $view->attachment_after.
 * @param $view
 *   The view object about to be processed.
 */
function hook_views_pre_build(&$view) {
  // Because of some unexplicable business logic, we should remove all
  // attachments from all views on Mondays.
  // (This alter could be done later in the execution process as well.)
  if (date('D') == 'Mon') {
    unset($view->attachment_before);
    unset($view->attachment_after);
  }
}

/**
 * This hook is called right after the build process. The query is now fully
 * built, but it has not yet been run through db_rewrite_sql.
 *
 * Adding output to the view can be accomplished by placing text on
 * $view->attachment_before and $view->attachment_after.
 * @param $view
 *   The view object about to be processed.
 */
function hook_views_post_build(&$view) {
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
 * This hook is called right before the execute process. The query is now fully
 * built, but it has not yet been run through db_rewrite_sql.
 *
 * Adding output to the view can be accomplished by placing text on
 * $view->attachment_before and $view->attachment_after.
 * @param $view
 *   The view object about to be processed.
 */
function hook_views_pre_execute(&$view) {
  // Whenever a view queries more than two tables, show a message that notifies
  // view administrators that the query might be heavy.
  // (This action could be performed later in the execution process, but not
  // earlier.)
  if (count($view->query->tables) > 2 && user_access('administer views')) {
    drupal_set_message(t('The view %view may be heavy to execute.', array('%view' => $view->name)), 'warning');
  }
}

/**
 * This hook is called right after the execute process. The query has
 * been executed, but the pre_render() phase has not yet happened for
 * handlers.
 *
 * Adding output to the view can be accomplished by placing text on
 * $view->attachment_before and $view->attachment_after. Altering the
 * content can be achieved by editing the items of $view->result.
 * @param $view
 *   The view object about to be processed.
 */
function hook_views_post_execute(&$view) {
  // If there are more than 100 results, show a message that encourages the user
  // to change the filter settings.
  // (This action could be performed later in the execution process, but not
  // earlier.)
  if ($view->total_rows > 100) {
    drupal_set_message(t('You have more than 100 hits. Use the filter settings to narrow down your list.'));
  }
}

/**
 * This hook is called right before the render process. The query has been
 * executed, and the pre_render() phase has already happened for handlers, so
 * all data should be available.
 *
 * Adding output to the view can be accomplished by placing text on
 * $view->attachment_before and $view->attachment_after. Altering the content
 * can be achieved by editing the items of $view->result.
 *
 * This hook can be utilized by themes.
 * @param $view
 *   The view object about to be processed.
 */
function hook_views_pre_render(&$view) {
  // Scramble the order of the rows shown on this result page.
  // Note that this could be done earlier, but not later in the view execution
  // process.
  shuffle($view->result);
}

/**
 * Post process any rendered data.
 *
 * This can be valuable to be able to cache a view and still have some level of
 * dynamic output. In an ideal world, the actual output will include HTML
 * comment based tokens, and then the post process can replace those tokens.
 *
 * Example usage. If it is known that the view is a node view and that the
 * primary field will be a nid, you can do something like this:
 *
 * <!--post-FIELD-NID-->
 *
 * And then in the post render, create an array with the text that should
 * go there:
 *
 * strtr($output, array('<!--post-FIELD-1-->' => 'output for FIELD of nid 1');
 *
 * All of the cached result data will be available in $view->result, as well,
 * so all ids used in the query should be discoverable.
 *
 * This hook can be utilized by themes.
 * @param $view
 *   The view object about to be processed.
 * @param $output
 *   A flat string with the rendered output of the view.
 * @param $cache
 *   The cache settings.
 */
function hook_views_post_render(&$view, &$output, &$cache) {
  // When using full pager, disable any time-based caching if there are less
  // then 10 results.
  if ($view->pager instanceof Drupal\views\Plugin\views\pager\Full && $cache->options['type'] == 'time' && count($view->result) < 10) {
    $cache['options']['results_lifespan'] = 0;
    $cache['options']['output_lifespan'] = 0;
  }
}

/**
 * Alter the query before executing the query.
 *
 * This hook should be placed in MODULENAME.views.inc and it will be
 * auto-loaded. MODULENAME.views.inc must be in the directory specified by the
 * 'path' key returned by MODULENAME_views_api(), or the same directory as the
 * .module file, if 'path' is unspecified.
 *
 * @param $view
 *   The view object about to be processed.
 * @param $query
 *   An object describing the query.
 * @see hook_views_query_substitutions()
 */
function hook_views_query_alter(&$view, &$query) {
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
 * Alter the information box that (optionally) appears with a view preview,
 * including query and performance statistics.
 *
 * This hook should be placed in MODULENAME.views.inc and it will be
 * auto-loaded. MODULENAME.views.inc must be in the directory specified by the
 * 'path' key returned by MODULENAME_views_api(), or the same directory as the
 * .module file, if 'path' is unspecified.
 *
 * Warning: $view is not a reference in PHP4 and cannot be modified here. But it
 * IS a reference in PHP5, and can be modified. Please be careful with it.
 *
 * @param $rows
 *   An associative array with two keys:
 *   - query: An array of rows suitable for theme('table'), containing
 *     information about the query and the display title and path.
 *   - statistics: An array of rows suitable for theme('table'), containing
 *     performance statistics.
 * @param $view
 *   The view object.
 * @see theme_table()
 */
function hook_views_preview_info_alter(&$rows, $view) {
  // Adds information about the tables being queried by the view to the query
  // part of the info box.
  $rows['query'][] = array(
    t('<strong>Table queue</strong>'),
    count($view->query->table_queue) . ': (' . implode(', ', array_keys($view->query->table_queue)) . ')',
  );
}

/**
 * This hooks allows to alter the links at the top of the view edit form. Some
 * modules might want to add links there.
 *
 * @param $links
 *   An array of links which will be displayed at the top of the view edit form.
 *   Each entry should be on a form suitable for theme('link').
 * @param view $view
 *   The full view object which is currently edited.
 * @param $display_id
 *   The current display id which is edited. For example that's 'default' or
 *   'page_1'.
 */
function hook_views_ui_display_top_links_alter(&$links, $view, $display_id) {
  // Put the export link first in the list.
  if (isset($links['export'])) {
    $links = array('export' => $links['export']) + $links;
  }
}

/**
 * This hook allows to alter the commands which are used on a views ajax
 * request.
 *
 * @param $commands
 *   An array of ajax commands
 * @param $view view
 *   The view which is requested.
 */
function hook_views_ajax_data_alter(&$commands, $view) {
  // Replace Views' method for scrolling to the top of the element with your
  // custom scrolling method.
  foreach ($commands as &$command) {
    if ($command['method'] == 'viewsScrollTop') {
      $command['method'] .= 'myScrollTop';
    }
  }
}

/**
 * Allow modules to respond to the Views cache being invalidated.
 *
 * This hook should fire whenever a view is enabled, disabled, created,
 * updated, or deleted.
 *
 * @see views_invalidate_cache()
 */
function hook_views_invalidate_cache() {
  cache('mymodule')->invalidateTags(array('views' => TRUE));
}

/**
 * @}
 */

/**
 * @defgroup views_module_handlers Views module handlers
 * @{
 * Handlers exposed by various modules to Views.
 * @}
 */
