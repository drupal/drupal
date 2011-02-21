<?php
// $Id: docs.php,v 1.16.4.10 2010/12/18 08:02:37 dereine Exp $
/**
 * @file
 * This file contains no working PHP code; it exists to provide additional documentation
 * for doxygen as well as to document hooks in the standard Drupal manner.
 */

/**
 * @mainpage Views 2 API Manual
 *
 * Much of this information is actually stored in the advanced help; please
 * check the API topic. This help will primarily be aimed at documenting
 * classes and function calls.
 *
 * An online version of the advanced help API documentation is available from:
 * @link http://views-help.doc.logrus.com/help/views/api @endlink
 *
 * Topics:
 * - @ref view_lifetime
 * - @ref views_hooks
 * - @ref views_handlers
 * - @ref views_plugins
 * - @ref views_templates
 */

/**
 * @page view_lifetime The life of a view
 *
 * This page explains the basic cycle of a view and what processes happen.
 */

/**
 * @page views_handlers About Views' handlers
 *
 * This page explains what views handlers are, how they're written, and what
 * the basic conventions are.
 *
 * - @ref views_field_handlers
 * - @ref views_sort_handlers
 * - @ref views_filter_handlers
 * - @ref views_argument_handlers
 * - @ref views_relationship_handlers
 */

/**
 * @page views_plugins About Views' plugins
 *
 * This page explains what views plugins are, how they're written, and what
 * the basic conventions are.
 *
 * - @ref views_display_plugins
 * - @ref views_style_plugins
 * - @ref views_row_plugins
 */

/**
 * @defgroup views_hooks Views' hooks
 * @{
 * Hooks that can be implemented by other modules in order to implement the
 * Views API.
 */

/**
 * Describe table structure to Views.
 *
 * This hook should be placed in MODULENAME.views.inc and it will be auto-loaded.
 * This must either be in the same directory as the .module file or in a subdirectory
 * named 'includes'.
 *
 * The full documentation for this hook is in the advanced help.
 * @link http://views-help.doc.logrus.com/help/views/api-tables @endlink
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

  // The 'group' index will be used as a prefix in the UI for any of this
  // table's fields, sort criteria, etc. so it's easy to tell where they came
  // from.
  $data['example_table']['table']['group'] = t('Example table');

  // Define this as a base table. In reality this is not very useful for
  // this table, as it isn't really a distinct object of its own, but
  // it makes a good example.
  $data['example_table']['table']['base'] = array(
    'field' => 'nid',
    'title' => t('Example table'),
    'help' => t("Example table contains example content and can be related to nodes."),
    'weight' => -10,
  );

  // This table references the {node} table.
  // This creates an 'implicit' relationship to the node table, so that when 'Node'
  // is the base table, the fields are automatically available.
  $data['example_table']['table']['join'] = array(
    // Index this array by the table name to which this table refers.
    // 'left_field' is the primary key in the referenced table.
    // 'field' is the foreign key in this table.
    'node' => array(
      'left_field' => 'nid',
      'field' => 'nid',
    ),
  );

  // Next, describe each of the individual fields in this table to Views. For
  // each field, you may define what field, sort, argument, and/or filter
  // handlers it supports. This will determine where in the Views interface you
  // may use the field.

  // Node ID field.
  $data['example_table']['nid'] = array(
    'title' => t('Example content'),
    'help' => t('Some example content that references a node.'),
    // Because this is a foreign key to the {node} table. This allows us to
    // have, when the view is configured with this relationship, all the fields
    // for the related node available.
    'relationship' => array(
      'base' => 'node',
      'field' => 'nid',
      'handler' => 'views_handler_relationship',
      'label' => t('Example node'),
    ),
  );

  // Example plain text field.
  $data['example_table']['plain_text_field'] = array(
    'title' => t('Plain text field'),
    'help' => t('Just a plain text field.'),
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
  );

  // Example numeric text field.
  $data['example_table']['numeric_field'] = array(
    'title' => t('Numeric field'),
    'help' => t('Just a numeric field.'),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
     ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  // Example boolean field.
  $data['example_table']['boolean_field'] = array(
    'title' => t('Boolean field'),
    'help' => t('Just an on/off field.'),
    'field' => array(
      'handler' => 'views_handler_field_boolean',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_boolean_operator',
      'label' => t('Published'),
      'type' => 'yes-no',
      // use boolean_field = 1 instead of boolean_field <> 0 in WHERE statment
      'use equal' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  // Example timestamp field.
  $data['example_table']['timestamp_field'] = array(
    'title' => t('Timestamp field'),
    'help' => t('Just a timestamp field.'),
    'field' => array(
      'handler' => 'views_handler_field_date',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort_date',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_date',
    ),
  );

  return $data;
}

/**
 * Alter table structure.
 *
 * You can add/edit/remove to existing tables defined by hook_views_data().
 *
 * This hook should be placed in MODULENAME.views.inc and it will be auto-loaded.
 * This must either be in the same directory as the .module file or in a subdirectory
 * named 'includes'.
 *
 * The full documentation for this hook is in the advanced help.
 * @link http://views-help.doc.logrus.com/help/views/api-tables @endlink
 */
function hook_views_data_alter(&$data) {
  // This example alters the title of the node: nid field for the admin.
  $data['node']['nid']['title'] = t('Node-Nid');

  // This example adds a example field to the users table
  $data['users']['example_field'] = array(
    'title' => t('Example field'),
    'help' => t('Some examüple content that references a user'),
    'handler' => 'hook_handlers_field_example_field',
  );

  // This example changes the handler of the node title field.
  // In this handler you could do stuff, like preview of the node, when clicking the node title.

  $data['node']['title']['handler'] = 'modulename_handlers_field_node_title';
}


/**
 * The full documentation for this hook is now in the advanced help.
 *
 * This hook should be placed in MODULENAME.views.inc and it will be auto-loaded.
 * This must either be in the same directory as the .module file or in a subdirectory
 * named 'includes'.
 *
 * This is a stub list as a reminder that this needs to be doc'd and is not used
 * in views anywhere so might not be remembered when this is formally documented:
 * - style: 'even empty'
 */
function hook_views_plugins() {
  // example code here
}

/**
 * Alter existing plugins data, defined by modules.
 */
function hook_views_plugins_alter(&$plugins) {
  // Add apachesolr to the base of the node row plugin.
  $plugins['row']['node']['base'][] = 'apachesolr';
}

/**
 * Register View API information. This is required for your module to have
 * its include files loaded; for example, when implementing
 * hook_views_default_views().
 *
 * @return
 *   An array with the following possible keys:
 *   - api:  (required) The version of the Views API the module implements.
 *   - path: (optional) If includes are stored somewhere other than within
 *       the root module directory or a subdirectory called includes, specify
 *       its path here.
 *   - template path: (optional) A path where the module has stored it's views template files.
 *        When you have specificed this key views automatically uses the template files for the views.
 *        You can use the same naming conventions like for normal views template files.
 */
function hook_views_api() {
  return array(
    'api' => 2,
    'path' => drupal_get_path('module', 'example') . '/includes/views',
  );
}

/**
 * This hook allows modules to provide their own views which can either be used
 * as-is or as a "starter" for users to build from.
 *
 * This hook should be placed in MODULENAME.views_default.inc and it will be
 * auto-loaded. This must either be in the same directory as the .module file
 * or in a subdirectory named 'includes'.
 *
 * The $view->disabled boolean flag indicates whether the View should be
 * enabled or disabled by default.
 *
 * @return
 *   An associative array containing the structures of views, as generated from
 *   the Export tab, keyed by the view name. A best practice is to go through
 *   and add t() to all title and label strings, with the exception of menu
 *   strings.
 */
function hook_views_default_views() {
  // Begin copy and paste of output from the Export tab of a view.
  $view = new view;
  $view->name = 'frontpage';
  $view->description = t('Emulates the default Drupal front page; you may set the default home page path to this view to make it your front page.');
  $view->tag = t('default');
  $view->base_table = 'node';
  $view->api_version = 2;
  $view->disabled = FALSE; // Edit this to true to make a default view disabled initially
  $view->display = array();
    $display = new views_display;
    $display->id = 'default';
    $display->display_title = t('Defaults');
    $display->display_plugin = 'default';
    $display->position = '1';
    $display->display_options = array (
    'style_plugin' => 'default',
    'style_options' =>
    array (
    ),
    'row_plugin' => 'node',
    'row_options' =>
    array (
      'teaser' => 1,
      'links' => 1,
    ),
    'relationships' =>
    array (
    ),
    'fields' =>
    array (
    ),
    'sorts' =>
    array (
      'sticky' =>
      array (
        'id' => 'sticky',
        'table' => 'node',
        'field' => 'sticky',
        'order' => 'ASC',
      ),
      'created' =>
      array (
        'id' => 'created',
        'table' => 'node',
        'field' => 'created',
        'order' => 'ASC',
        'relationship' => 'none',
        'granularity' => 'second',
      ),
    ),
    'arguments' =>
    array (
    ),
    'filters' =>
    array (
      'promote' =>
      array (
        'id' => 'promote',
        'table' => 'node',
        'field' => 'promote',
        'operator' => '=',
        'value' => '1',
        'group' => 0,
        'exposed' => false,
        'expose' =>
        array (
          'operator' => false,
          'label' => '',
        ),
      ),
      'status' =>
      array (
        'id' => 'status',
        'table' => 'node',
        'field' => 'status',
        'operator' => '=',
        'value' => '1',
        'group' => 0,
        'exposed' => false,
        'expose' =>
        array (
          'operator' => false,
          'label' => '',
        ),
      ),
    ),
    'items_per_page' => 10,
    'use_pager' => '1',
    'pager_element' => 0,
    'title' => '',
    'header' => '',
    'header_format' => '1',
    'footer' => '',
    'footer_format' => '1',
    'empty' => '',
    'empty_format' => '1',
  );
  $view->display['default'] = $display;
    $display = new views_display;
    $display->id = 'page';
    $display->display_title = t('Page');
    $display->display_plugin = 'page';
    $display->position = '2';
    $display->display_options = array (
    'defaults' =>
    array (
      'access' => true,
      'title' => true,
      'header' => true,
      'header_format' => true,
      'header_empty' => true,
      'footer' => true,
      'footer_format' => true,
      'footer_empty' => true,
      'empty' => true,
      'empty_format' => true,
      'items_per_page' => true,
      'offset' => true,
      'use_pager' => true,
      'pager_element' => true,
      'link_display' => true,
      'php_arg_code' => true,
      'exposed_options' => true,
      'style_plugin' => true,
      'style_options' => true,
      'row_plugin' => true,
      'row_options' => true,
      'relationships' => true,
      'fields' => true,
      'sorts' => true,
      'arguments' => true,
      'filters' => true,
      'use_ajax' => true,
      'distinct' => true,
    ),
    'relationships' =>
    array (
    ),
    'fields' =>
    array (
    ),
    'sorts' =>
    array (
    ),
    'arguments' =>
    array (
    ),
    'filters' =>
    array (
    ),
    'path' => 'frontpage',
  );
  $view->display['page'] = $display;
    $display = new views_display;
    $display->id = 'feed';
    $display->display_title = t('Feed');
    $display->display_plugin = 'feed';
    $display->position = '3';
    $display->display_options = array (
    'defaults' =>
    array (
      'access' => true,
      'title' => false,
      'header' => true,
      'header_format' => true,
      'header_empty' => true,
      'footer' => true,
      'footer_format' => true,
      'footer_empty' => true,
      'empty' => true,
      'empty_format' => true,
      'use_ajax' => true,
      'items_per_page' => true,
      'offset' => true,
      'use_pager' => true,
      'pager_element' => true,
      'use_more' => true,
      'distinct' => true,
      'link_display' => true,
      'php_arg_code' => true,
      'exposed_options' => true,
      'style_plugin' => false,
      'style_options' => false,
      'row_plugin' => false,
      'row_options' => false,
      'relationships' => true,
      'fields' => true,
      'sorts' => true,
      'arguments' => true,
      'filters' => true,
    ),
    'relationships' =>
    array (
    ),
    'fields' =>
    array (
    ),
    'sorts' =>
    array (
    ),
    'arguments' =>
    array (
    ),
    'filters' =>
    array (
    ),
    'displays' =>
    array (
      'default' => 'default',
      'page' => 'page',
    ),
    'style_plugin' => 'rss',
    'style_options' =>
    array (
      'description' => '',
    ),
    'row_plugin' => 'node_rss',
    'row_options' =>
    array (
      'item_length' => 'default',
    ),
    'path' => 'rss.xml',
    'title' => t('Front page feed'),
  );
  $view->display['feed'] = $display;
  // End copy and paste of Export tab output.

  // Add view to list of views to provide.
  $views[$view->name] = $view;

  // ...Repeat all of the above for each view the module should provide.

  // At the end, return array of default views.
  return $views;
}

/**
 * This hook is called right before all default views are cached to the
 * database. It takes a keyed array of views by reference.
 */
function hook_views_default_views_alter(&$views) {
  if (isset($views['taxonomy_term'])) {
    $views['taxonomy_term']->set_display('default');
    $views['taxonomy_term']->display_handler->set_option('title', 'Categories');
  }
}

/**
 * Stub hook documentation
 *
 * This hook should be placed in MODULENAME.views_convert.inc and it will be auto-loaded.
 * This must either be in the same directory as the .module file or in a subdirectory
 * named 'includes'.
 */
function hook_views_convert() {
  // example code here
}

/**
 * Stub hook documentation
 */
function hook_views_query_substitutions() {
  // example code here
}

/**
 * This hook is called at the very beginning of views processing,
 * before anything is done.
 *
 * Adding output to the view can be accomplished by placing text on
 * $view->attachment_before and $view->attachment_after.
 */
function hook_views_pre_view(&$view, &$display_id, &$args) {
  // example code here
}

/**
 * This hook is called right before the build process, but after displays
 * are attached and the display performs its pre_execute phase.
 *
 * Adding output to the view can be accomplished by placing text on
 * $view->attachment_before and $view->attachment_after.
 */
function hook_views_pre_build(&$view) {
  // example code here
}

/**
 * This hook is called right after the build process. The query is
 * now fully built, but it has not yet been run through db_rewrite_sql.
 *
 * Adding output to the view can be accomplished by placing text on
 * $view->attachment_before and $view->attachment_after.
 */
function hook_views_post_build(&$view) {
  // example code here
}

/**
 * This hook is called right before the execute process. The query is
 * now fully built, but it has not yet been run through db_rewrite_sql.
 *
 * Adding output to the view can be accomplished by placing text on
 * $view->attachment_before and $view->attachment_after.
 */
function hook_views_pre_execute(&$view) {
  // example code here
}

/**
 * This hook is called right after the execute process. The query has
 * been executed, but the pre_render() phase has not yet happened for
 * handlers.
 *
 * Adding output to the view can be accomplished by placing text on
 * $view->attachment_before and $view->attachment_after. Altering the
 * content can be achieved by editing the items of $view->result.
 */
function hook_views_post_execute(&$view) {
  // example code here
}

/**
 * This hook is called right before the render process. The query has
 * been executed, and the pre_render() phase has already happened for
 * handlers, so all data should be available.
 *
 * Adding output to the view can be accomplished by placing text on
 * $view->attachment_before and $view->attachment_after. Altering the
 * content can be achieved by editing the items of $view->result.
 *
 * This hook can be utilized by themes.
 */
function hook_views_pre_render(&$view) {
  // example code here
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
 * strtr($output, array('<!--post-FIELD-1-->', 'output for FIELD of nid 1');
 *
 * All of the cached result data will be available in $view->result, as well,
 * so all ids used in the query should be discoverable.
 *
 * This hook can be utilized by themes.
 */
function hook_views_post_render(&$view, &$output, &$cache) {

}

/**
 * Stub hook documentation
 *
 * This hook should be placed in MODULENAME.views.inc and it will be auto-loaded.
 * This must either be in the same directory as the .module file or in a subdirectory
 * named 'includes'.
 *
 */
function hook_views_query_alter(&$view, &$query) {
  // example code here
}

/**
 * This hook should be placed in MODULENAME.views.inc and it will be auto-loaded.
 * This must either be in the same directory as the .module file or in a subdirectory
 * named 'includes'.
 *
 * Alter the links that appear over a view. They are in a format suitable for
 * theme('links').
 *
 * Warning: $view is not a reference in PHP4 and cannot be modified here. But it IS
 * a reference in PHP5, and can be modified. Please be careful with it.
 *
 * @see theme_links
 */
function hook_views_admin_links_alter(&$links, $view) {
  // example code here
}

/**
 * This hook should be placed in MODULENAME.views.inc and it will be auto-loaded.
 * This must either be in the same directory as the .module file or in a subdirectory
 * named 'includes'.
 *
 * Alter the rows that appear with a view, which includes path and query information.
 * The rows are suitable for theme('table').
 *
 * Warning: $view is not a reference in PHP4 and cannot be modified here. But it IS
 * a reference in PHP5, and can be modified. Please be careful with it.
 *
 * @see theme_table
 */
function hook_views_preview_info_alter(&$rows, $view) {
  // example code here
}

/**
 * @}
 */
