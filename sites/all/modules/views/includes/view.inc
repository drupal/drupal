<?php
// $Id: view.inc,v 1.167.4.40 2011/01/05 21:20:09 dereine Exp $
/**
 * @file view.inc
 * Provides the view object type and associated methods.
 */

/**
 * @defgroup views_objects Objects that represent a View or part of a view.
 * @{
 * These objects are the core of Views do the bulk of the direction and
 * storing of data. All database activity is in these objects.
 */

/**
 * An object to contain all of the data to generate a view, plus the member
 * functions to build the view query, execute the query and render the output.
 */
class view extends views_db_object {
  var $db_table = 'views_view';
  var $base_table = 'node';
  var $base_field = 'nid';

  // State variables
  var $built = FALSE;
  var $executed = FALSE;
  var $editing = FALSE;

  var $args = array();
  var $build_info = array();

  var $use_ajax = FALSE;

  // Where the results of a query will go.
  var $result = array();

  // May be used to override the current pager info.
  var $current_page = NULL;
  var $items_per_page = NULL;
  var $offset = NULL;
  var $total_rows = NULL;

  // Places to put attached renderings:
  var $attachment_before = '';
  var $attachment_after = '';

  // Exposed widget input
  var $exposed_data = array();
  var $exposed_input = array();

  // Used to store views that were previously running if we recurse.
  var $old_view = array();

  // Where the $query object will reside:
  var $query = NULL;

  /**
   * Constructor
   */
  function __construct() {
    parent::init();
    // Make sure all of our sub objects are arrays.
    foreach ($this->db_objects() as $object) {
      $this->$object = array();
    }
  }

  /**
   * Returns a list of the sub-object types used by this view. These types are
   * stored on the display, and are used in the build process.
   */
  function display_objects() {
    return array('argument', 'field', 'sort', 'filter', 'relationship', 'header', 'footer', 'empty');
  }

  /**
   * Returns the complete list of dependent objects in a view, for the purpose
   * of initialization and loading/saving to/from the database.
   */
  static function db_objects() {
    return array('display');
  }

  /**
   * Set the arguments that come to this view. Usually from the URL
   * but possibly from elsewhere.
   */
  function set_arguments($args) {
    $this->args = $args;
  }

  /**
   * Change/Set the current page for the pager.
   */
  function set_current_page($page) {
    $this->current_page = $page;
  }

  /**
   * Get the current page from the pager.
   */
  function get_current_page() {
    if (!empty($this->query->pager)) {
      return $this->query->pager->get_current_page();
    }
  }

  /**
   * Get the items per page from the pager.
   */
  function get_items_per_page() {
    if (!empty($this->query->pager)) {
      return $this->query->pager->get_items_per_page();
    }
  }

  /**
   * Set the items per page on the pager.
   */
  function set_items_per_page($items_per_page) {
    $this->items_per_page = $items_per_page;

    // If the pager is already initialized, pass it through to the pager.
    if (!empty($this->query->pager)) {
      $this->query->pager->set_items_per_page($items_per_page);
    }
  }

  /**
   * Get the pager offset from the pager.
   */
  function get_offset() {
    if (!empty($this->query->pager)) {
      return $this->query->pager->get_offset();
    }
  }

  /**
   * Set the offset on the pager.
   */
  function set_offset($offset) {
    $this->offset = $offset;

    // If the pager is already initialized, pass it through to the pager.
    if (!empty($this->query->pager)) {
      $this->query->pager->set_offset($offset);
    }
  }

  /**
   * Whether or not AJAX should be used. If AJAX is used, paging,
   * tablesorting and exposed filters will be fetched via an AJAX call
   * rather than a page refresh.
   */
  function set_use_ajax($use_ajax) {
    $this->use_ajax = $use_ajax;
  }

  /**
   * Set the exposed filters input to an array. If unset they will be taken
   * from $_GET when the time comes.
   */
  function set_exposed_input($filters) {
    $this->exposed_input = $filters;
  }

  /**
   * Figure out what the exposed input for this view is.
   */
  function get_exposed_input() {
    // Fill our input either from $_GET or from something previously set on the
    // view.
    if (empty($this->exposed_input)) {
      $this->exposed_input = $_GET;
      // unset items that are definitely not our input:
      foreach (array('page', 'q') as $key) {
        if (isset($this->exposed_input[$key])) {
          unset($this->exposed_input[$key]);
        }
      }

      // If we have no input at all, check for remembered input via session.

      // If filters are not overridden, store the 'remember' settings on the
      // default display. If they are, store them on this display. This way,
      // multiple displays in the same view can share the same filters and
      // remember settings.
      $display_id = ($this->display_handler->is_defaulted('filters')) ? 'default' : $this->current_display;

      if (empty($this->exposed_input) && !empty($_SESSION['views'][$this->name][$display_id])) {
        $this->exposed_input = $_SESSION['views'][$this->name][$display_id];
      }
    }

    return $this->exposed_input;
  }

  /**
   * Set the display for this view and initialize the display handler.
   */
  function init_display($reset = FALSE) {
    // The default display is always the first one in the list.
    if (isset($this->current_display)) {
      return TRUE;
    }

    // Instantiate all displays
    foreach (array_keys($this->display) as $id) {
      // Correct for shallow cloning
      // Often we'll have a cloned view so we don't mess up each other's
      // displays, but the clone is pretty shallow and doesn't necessarily
      // clone the displays. We can tell this by looking to see if a handler
      // has already been set; if it has, but $this->current_display is not
      // set, then something is dreadfully wrong.
      if (!empty($this->display[$id]->handler)) {
        $this->display[$id] = clone $this->display[$id];
        unset($this->display[$id]->handler);
      }
      $this->display[$id]->handler = views_get_plugin('display', $this->display[$id]->display_plugin);
      if (!empty($this->display[$id]->handler)) {
        // Initialize the new display handler with data.
        $this->display[$id]->handler->init($this, $this->display[$id]);
        // If this is NOT the default display handler, let it know which is
        // since it may well utilize some data from the default.
        // This assumes that the 'default' handler is always first. It always
        // is. Make sure of it.
        if ($id != 'default') {
          $this->display[$id]->handler->default_display = &$this->display['default']->handler;
        }
      }
    }

    $this->current_display = 'default';
    $this->display_handler = &$this->display['default']->handler;

    return TRUE;
  }

  /**
   * Get the first display that is accessible to the user.
   *
   * @param $displays
   *   Either a single display id or an array of display ids.
   */
  function choose_display($displays) {
    if (!is_array($displays)) {
      return $displays;
    }

    $this->init_display();

    foreach ($displays as $display_id) {
      if ($this->display[$display_id]->handler->access()) {
        return $display_id;
      }
    }

    return 'default';
  }

  /**
   * Set the display as current.
   *
   * @param $display_id
   *   The id of the display to mark as current.
   */
  function set_display($display_id = NULL) {
    // If we have not already initialized the display, do so. But be careful.
    if (empty($this->current_display)) {
      $this->init_display();

      // If handlers were not initialized, and no argument was sent, set up
      // to the default display.
      if (empty($display_id)) {
        $display_id = 'default';
      }
    }

    $display_id = $this->choose_display($display_id);

    // If no display id sent in and one wasn't chosen above, we're finished.
    if (empty($display_id)) {
      return FALSE;
    }

    // Ensure the requested display exists.
    if (empty($this->display[$display_id])) {
      $display_id = 'default';
      if (empty($this->display[$display_id])) {
        debug(t('set_display() called with invalid display id @display.', array('@display' => $display_id)));
        return FALSE;
      }
    }

    // Set the current display.
    $this->current_display = $display_id;

    // Ensure requested display has a working handler.
    if (empty($this->display[$display_id]->handler)) {
      return FALSE;
    }

    // Set a shortcut
    $this->display_handler = &$this->display[$display_id]->handler;

    return TRUE;
  }

  /**
   * Find and initialize the style plugin.
   *
   * Note that arguments may have changed which style plugin we use, so
   * check the view object first, then ask the display handler.
   */
  function init_style() {
    if (isset($this->style_plugin)) {
      return is_object($this->style_plugin);
    }

    if (!isset($this->plugin_name)) {
      $this->plugin_name = $this->display_handler->get_option('style_plugin');
      $this->style_options = $this->display_handler->get_option('style_options');
    }

    $this->style_plugin = views_get_plugin('style', $this->plugin_name);

    if (empty($this->style_plugin)) {
      return FALSE;
    }

    // init the new style handler with data.
    $this->style_plugin->init($this, $this->display[$this->current_display], $this->style_options);
    return TRUE;
  }

  /**
   * Acquire and attach all of the handlers.
   */
  function init_handlers() {
    if (empty($this->inited)) {
      foreach (views_object_types() as $key => $info) {
        $this->_init_handler($key, $info);
      }
      $this->inited = TRUE;
    }
  }

  /**
   * Initialize the pager
   *
   * Like style initialization, pager initialization is held until late
   * to allow for overrides.
   */
  function init_pager() {
    if (empty($this->query->pager)) {
      $this->query->pager = $this->display_handler->get_plugin('pager');

      if ($this->query->pager->use_pager()) {
        $this->query->pager->set_current_page($this->current_page);
      }

      // These overrides may have been set earlier via $view->set_*
      // functions.
      if (isset($this->items_per_page)) {
        $this->query->pager->set_items_per_page($this->items_per_page);
      }

      if (isset($this->offset)) {
        $this->query->pager->set_offset($this->offset);
      }
    }
  }

  /**
   * Create a list of base tables eligible for this view. Used primarily
   * for the UI. Display must be already initialized.
   */
  function get_base_tables() {
    $base_tables = array(
      $this->base_table => TRUE,
      '#global' => TRUE,
    );

    foreach ($this->display_handler->get_handlers('relationship') as $handler) {
      $base_tables[$handler->definition['base']] = TRUE;
    }
    return $base_tables;
  }

  /**
   * Run the pre_query() on all active handlers.
   */
  function _pre_query() {
    foreach (views_object_types() as $key => $info) {
      $handlers = &$this->$key;
      $position = 0;
      foreach ($handlers as $id => $handler) {
        $handlers[$id]->position = $position;
        $handlers[$id]->pre_query();
        $position++;
      }
    }
  }

  /**
   * Attach all of the handlers for each type.
   *
   * @param $key
   *   One of 'argument', 'field', 'sort', 'filter', 'relationship'
   * @param $info
   *   The $info from views_object_types for this object.
   */
  function _init_handler($key, $info) {
    // Load the requested items from the display onto the object.
    $this->$key = $this->display_handler->get_handlers($key);

    // This reference deals with difficult PHP indirection.
    $handlers = &$this->$key;

    // Run through and test for accessibility.
    foreach ($handlers as $id => $handler) {
      if (!$handler->access()) {
        unset($handlers[$id]);
      }
    }
  }

  /**
   * Build all the arguments.
   */
  function _build_arguments() {
    // Initially, we want to build sorts and fields. This can change, though,
    // if we get a summary view.
    if (empty($this->argument)) {
      return TRUE;
    }

    // build arguments.
    $position = -1;

    // Create a title for use in the breadcrumb trail.
    $title = $this->display_handler->get_option('title');

    $this->build_info['breadcrumb'] = array();
    $breadcrumb_args = array();
    $substitutions = array();

    $status = TRUE;

    // Iterate through each argument and process.
    foreach ($this->argument as $id => $arg) {
      $position++;
      $argument = &$this->argument[$id];

      if ($argument->broken()) {
        continue;
      }

      $argument->set_relationship();

      $arg = isset($this->args[$position]) ? $this->args[$position] : NULL;
      $argument->position = $position;

      if (isset($arg) || $argument->has_default_argument()) {
        if (!isset($arg)) {
          $arg = $argument->get_default_argument();
          // make sure default args get put back.
          if (isset($arg)) {
            $this->args[$position] = $arg;
          }
        }

        // Set the argument, which will also validate that the argument can be set.
        if (!$argument->set_argument($arg)) {
          $status = $argument->validate_fail($arg);
          break;
        }

        if ($argument->is_wildcard()) {
          $arg_title = $argument->wildcard_title();
        }
        else {
          $arg_title = $argument->get_title();
          $argument->query($this->display_handler->use_group_by());
        }

        // Add this argument's substitution
        $substitutions['%' . ($position + 1)] = $arg_title;

        // Since we're really generating the breadcrumb for the item above us,
        // check the default action of this argument.
        if ($this->display_handler->uses_breadcrumb() && $argument->uses_breadcrumb()) {
          $path = $this->get_url($breadcrumb_args);
          if (strpos($path, '%') === FALSE) {
            $breadcrumb = !empty($argument->options['breadcrumb'])? $argument->options['breadcrumb'] : $title;
            $this->build_info['breadcrumb'][$path] = str_replace(array_keys($substitutions), $substitutions, $breadcrumb);
          }
        }

        // Allow the argument to muck with this breadcrumb.
        $argument->set_breadcrumb($this->build_info['breadcrumb']);

        // Test to see if we should use this argument's title
        if (!empty($argument->options['title'])) {
          $title = $argument->options['title'];
        }

        $breadcrumb_args[] = $arg;
      }
      else {
        // determine default condition and handle.
        $status = $argument->default_action();
        break;
      }

      // Be safe with references and loops:
      unset($argument);
    }

    // set the title in the build info.
    if (!empty($title)) {
      $this->build_info['title'] = str_replace(array_keys($substitutions), $substitutions, $title);
    }

    // Store the arguments for later use.
    $this->build_info['substitutions'] = $substitutions;

    return $status;
  }

  /**
   * Do some common building initialization.
   */
  function init_query() {
    if (!empty($this->query)) {
      $class = get_class($this->query);
      if ($class && $class != 'stdClass') {
        // return if query is already initialized.
        return TRUE;
      }
    }

    // Create and initialize the query object.
    $views_data = views_fetch_data($this->base_table);
    $this->base_field = $views_data['table']['base']['field'];
    if (!empty($views_data['table']['base']['database'])) {
      $this->base_database = $views_data['table']['base']['database'];
    }

    // Load the options.
    $query_options = $this->display_handler->get_option('query');

    // Create and initialize the query object.
    $plugin = !empty($views_data['table']['base']['query class']) ? $views_data['table']['base']['query class'] : 'views_query';
    $this->query = views_get_plugin('query', $plugin);

    if (empty($this->query)) {
      return FALSE;
    }

    $this->query->init($this->base_table, $this->base_field, $query_options['options']);
    return TRUE;
  }

  /**
   * Build the query for the view.
   */
  function build($display_id = NULL) {
    if (!empty($this->built)) {
      return;
    }

    if (empty($this->current_display) || $display_id) {
      if (!$this->set_display($display_id)) {
        return FALSE;
      }
    }

    // Let modules modify the view just prior to building it.
    foreach (module_implements('views_pre_build') as $module) {
      $function = $module . '_views_pre_build';
      $function($this);
    }

    // Attempt to load from cache.
    // @todo Load a build_info from cache.

    $start = microtime(TRUE);
    // If that fails, let's build!
    $this->build_info = array(
      'query' => '',
      'count_query' => '',
      'query_args' => array(),
    );

    $this->init_query();

    // Call a module hook and see if it wants to present us with a
    // pre-built query or instruct us not to build the query for
    // some reason.
    // @todo: Implement this. Use the same mechanism Panels uses.

    // Run through our handlers and ensure they have necessary information.
    $this->init_handlers();

    // Let the handlers interact with each other if they really want.
    $this->_pre_query();

    if ($this->display_handler->uses_exposed()) {
      $exposed_form = $this->display_handler->get_plugin('exposed_form');
      $this->exposed_widgets = $exposed_form->render_exposed_form();
      if (form_set_error() || !empty($this->build_info['abort'])) {
        $this->built = TRUE;
        // Don't execute the query, but rendering will still be executed to display the empty text.
        $this->executed = TRUE;
        return empty($this->build_info['fail']);
      }
    }

    // Build all the relationships first thing.
    $this->_build('relationship');

    // Set the filtering groups.
    if (!empty($this->filter)) {
      $filter_groups = $this->display_handler->get_option('filter_groups');
      if ($filter_groups) {
        $this->query->set_group_operator($filter_groups['operator']);
        foreach($filter_groups['groups'] as $id => $operator) {
          $this->query->set_where_group($operator, $id);
        }
      }
    }

    // Build all the filters.
    $this->_build('filter');

    $this->build_sort = TRUE;

    // Arguments can, in fact, cause this whole thing to abort.
    if (!$this->_build_arguments()) {
      $this->build_time = microtime(TRUE) - $start;
      $this->attach_displays();
      return $this->built;
    }

    // Initialize the style; arguments may have changed which style we use,
    // so waiting as long as possible is important. But we need to know
    // about the style when we go to build fields.
    if (!$this->init_style()) {
      $this->build_info['fail'] = TRUE;
      return FALSE;
    }

    if ($this->style_plugin->uses_fields()) {
      $this->_build('field');
    }

    // Build our sort criteria if we were instructed to do so.
    if (!empty($this->build_sort)) {
      // Allow the style handler to deal with sorting.
      if ($this->style_plugin->build_sort()) {
        $this->_build('sort');
      }
      // allow the plugin to build second sorts as well.
      $this->style_plugin->build_sort_post();
    }

    // Allow display handler to affect the query:
    $this->display_handler->query($this->display_handler->use_group_by());

    // Allow style handler to affect the query:
    $this->style_plugin->query($this->display_handler->use_group_by());

    // Allow exposed form to affect the query:
    if (isset($exposed_form)) {
      $exposed_form->query();
    }

    if (variable_get('views_sql_signature', FALSE)) {
      $this->query->add_signature($this);
    }

    // Let modules modify the query just prior to finalizing it.
    $this->query->alter($this);

    // Only build the query if we weren't interrupted.
    if (empty($this->built)) {
      // Build the necessary info to execute the query.
      $this->query->build($this);
    }

    $this->built = TRUE;
    $this->build_time = microtime(TRUE) - $start;

    // Attach displays
    $this->attach_displays();

    // Let modules modify the view just after building it.
    foreach (module_implements('views_post_build') as $module) {
      $function = $module . '_views_post_build';
      $function($this);
    }

    return TRUE;
  }

  /**
   * Internal method to build an individual set of handlers.
   */
  function _build($key) {
    $handlers = &$this->$key;
    foreach ($handlers as $id => $data) {
      if (!empty($handlers[$id]) && is_object($handlers[$id])) {
        // Give this handler access to the exposed filter input.
        if (!empty($this->exposed_data)) {
          $rc = $handlers[$id]->accept_exposed_input($this->exposed_data);
          $handlers[$id]->store_exposed_input($this->exposed_data, $rc);
          if (!$rc) {
            continue;
          }
        }
        $handlers[$id]->set_relationship();
        $handlers[$id]->query($this->display_handler->use_group_by());
      }
    }
  }

  /**
   * Execute the view's query.
   */
  function execute($display_id = NULL) {
    if (empty($this->built)) {
      if (!$this->build($display_id)) {
        return FALSE;
      }
    }

    if (!empty($this->executed)) {
      return TRUE;
    }

    // Don't allow to use deactivated displays.
    if (!$this->display[$this->current_display]->handler->get_option('enabled')) {
      $this->build_info['fail'] = TRUE;
      return FALSE;
    }

    // Let modules modify the view just prior to executing it.
    foreach (module_implements('views_pre_execute') as $module) {
      $function = $module . '_views_pre_execute';
      $function($this);
    }

    // Check for already-cached results.
    if (!empty($this->live_preview)) {
      $cache = FALSE;
    }
    else {
      $cache = $this->display_handler->get_plugin('cache');
    }
    if ($cache && $cache->cache_get('results')) {
    }
    else {
      $this->query->execute($this);
      if ($cache) {
        $cache->cache_set('results');
      }
    }

    // Let modules modify the view just after executing it.
    foreach (module_implements('views_post_execute') as $module) {
      $function = $module . '_views_post_execute';
      $function($this);
    }

    $this->executed = TRUE;
  }

  /**
   * Render this view for display.
   */
  function render($display_id = NULL) {
    $this->execute($display_id);

    // Check to see if the build failed.
    if (!empty($this->build_info['fail'])) {
      return;
    }

    drupal_theme_initialize();

    $start = microtime(TRUE);
    if (!empty($this->live_preview) && variable_get('views_show_additional_queries', FALSE)) {
      $this->start_query_capture();
    }

    $exposed_form = $this->display_handler->get_plugin('exposed_form');
    $exposed_form->pre_render($this->result);

    // Check for already-cached output.
    if (!empty($this->live_preview)) {
      $cache = FALSE;
    }
    else {
      $cache = $this->display_handler->get_plugin('cache');
    }
    if ($cache && $cache->cache_get('output')) {
    }
    else {
      if ($cache) {
        $cache->cache_start();
      }

      // Initialize the style plugin.
      $this->init_style();

      $this->style_plugin->pre_render($this->result);

      // Let modules modify the view just prior to rendering it.
      foreach (module_implements('views_pre_render') as $module) {
        $function = $module . '_views_pre_render';
        $function($this);
      }

      // Let the theme play too, because pre render is a very themey thing.
      $function = $GLOBALS['theme'] . '_views_pre_render';
      if (function_exists($function)) {
        $function($this, $this->display_handler->output, $cache);
      }

      // Give field handlers the opportunity to perform additional queries
      // using the entire resultset prior to rendering.
      if ($this->style_plugin->uses_fields()) {
        foreach ($this->field as $id => $handler) {
          if (!empty($this->field[$id])) {
            $this->field[$id]->pre_render($this->result);
          }
        }
      }
      $this->display_handler->output = $this->display_handler->render();
      if ($cache) {
        $cache->cache_set('output');
      }
    }

    $exposed_form->post_render($this->display_handler->output);

    if ($cache) {
      $cache->post_render($this->display_handler->output);
    }

    // Let modules modify the view output after it is rendered.
    foreach (module_implements('views_post_render') as $module) {
      $function = $module . '_views_post_render';
      $function($this, $this->display_handler->output, $cache);
    }

    // Let the theme play too, because post render is a very themey thing.
    $function = $GLOBALS['theme'] . '_views_post_render';
    if (function_exists($function)) {
      $function($this, $this->display_handler->output, $cache);
    }

    if (!empty($this->live_preview) && variable_get('views_show_additional_queries', FALSE)) {
      $this->end_query_capture();
    }
    $this->render_time = microtime(TRUE) - $start;

    return $this->display_handler->output;
  }

  /**
   * Render a specific field via the field ID and the row #.
   */
  function render_field($field, $row) {
    if (isset($this->field[$field]) && isset($this->result[$row])) {
      return $this->field[$field]->advanced_render($this->result[$row]);
    }
  }

  /**
   * Execute the given display, with the given arguments.
   * To be called externally by whatever mechanism invokes the view,
   * such as a page callback, hook_block, etc.
   *
   * This function should NOT be used by anything external as this
   * returns data in the format specified by the display. It can also
   * have other side effects that are only intended for the 'proper'
   * use of the display, such as setting page titles and breadcrumbs.
   *
   * If you simply want to view the display, use view::preview() instead.
   */
  function execute_display($display_id = NULL, $args = array()) {
    if (empty($this->current_display) || $this->current_display != $this->choose_display($display_id)) {
      if (!$this->set_display($display_id)) {
        return FALSE;
      }
    }

    $this->pre_execute($args);

    // Execute the view
    $output = $this->display_handler->execute();

    $this->post_execute();
    return $output;
  }

  /**
   * Preview the given display, with the given arguments.
   *
   * To be called externally, probably by an AJAX handler of some flavor.
   * Can also be called when views are embedded, as this guarantees
   * normalized output.
   */
  function preview($display_id = NULL, $args = array()) {
    if (empty($this->current_display) || ((!empty($display_id)) && $this->current_display != $display_id)) {
      if (!$this->set_display($display_id)) {
        return FALSE;
      }
    }

    $this->preview = TRUE;
    $this->pre_execute($args);
    // Preview the view.
    $output = $this->display_handler->preview();

    $this->post_execute();
    return $output;
  }

  /**
   * Run attachments and let the display do what it needs to do prior
   * to running.
   */
  function pre_execute($args = array()) {
    $this->old_view[] = views_get_current_view();
    views_set_current_view($this);
    $display_id = $this->current_display;

    // Let modules modify the view just prior to executing it.
    foreach (module_implements('views_pre_view') as $module) {
      $function = $module . '_views_pre_view';
      $function($this, $display_id, $args);
    }

    // Prepare the view with the information we have, but only if we were
    // passed arguments, as they may have been set previously.
    if ($args) {
      $this->set_arguments($args);
    }

//    $this->attach_displays();

    // Allow the display handler to set up for execution
    $this->display_handler->pre_execute();
  }

  /**
   * Unset the current view, mostly.
   */
  function post_execute() {
    // unset current view so we can be properly destructed later on.
    // Return the previous value in case we're an attachment.

    if ($this->old_view) {
      $old_view = array_pop($this->old_view);
    }

    views_set_current_view(isset($old_view) ? $old_view : FALSE);
  }

  /**
   * Run attachment displays for the view.
   */
  function attach_displays() {
    if (!empty($this->is_attachment)) {
      return;
    }

    if (!$this->display_handler->accept_attachments()) {
      return;
    }

    $this->is_attachment = TRUE;
    // Give other displays an opportunity to attach to the view.
    foreach ($this->display as $id => $display) {
      if (!empty($this->display[$id]->handler)) {
        $this->display[$id]->handler->attach_to($this->current_display);
      }
    }
    $this->is_attachment = FALSE;
  }

  /**
   * Called to get hook_menu() information from the view and the named display handler.
   *
   * @param $display_id
   *   A display id.
   * @param $callbacks
   *   A menu callback array passed from views_menu_alter().
   */
  function execute_hook_menu($display_id = NULL, $callbacks = array()) {
    // Prepare the view with the information we have.

    // This was probably already called, but it's good to be safe.
    if (!$this->set_display($display_id)) {
      return FALSE;
    }

    // Execute the view
    if (isset($this->display_handler)) {
      return $this->display_handler->execute_hook_menu($callbacks);
    }
  }

  /**
   * Called to get hook_block information from the view and the
   * named display handler.
   */
  function execute_hook_block_list($display_id = NULL) {
    // Prepare the view with the information we have.

    // This was probably already called, but it's good to be safe.
    if (!$this->set_display($display_id)) {
      return FALSE;
    }

    // Execute the view
    if (isset($this->display_handler)) {
      return $this->display_handler->execute_hook_block_list();
    }
  }

  /**
   * Determine if the given user has access to the view. Note that
   * this sets the display handler if it hasn't been.
   */
  function access($displays = NULL, $account = NULL) {
    // Noone should have access to disabled views.
    if (!empty($this->disabled)) {
      return FALSE;
    }

    if (!isset($this->current_display)) {
      $this->init_display();
    }

    if (!$account) {
      $account = $GLOBALS['user'];
    }

    // We can't use choose_display() here because that function
    // calls this one.
    $displays = (array)$displays;
    foreach ($displays as $display_id) {
      if (!empty($this->display[$display_id]->handler)) {
        if ($this->display[$display_id]->handler->access($account)) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * Get the view's current title. This can change depending upon how it
   * was built.
   */
  function get_title() {
    if (empty($this->display_handler)) {
      if (!$this->set_display('default')) {
        return FALSE;
      }
    }

    // During building, we might find a title override. If so, use it.
    if (!empty($this->build_info['title'])) {
      $title = $this->build_info['title'];
    }
    else {
      $title = $this->display_handler->get_option('title');
    }

    return $title;
  }

  /**
   * Force the view to build a title.
   */
  function build_title() {
    $this->init_display();

    if (empty($this->built)) {
      $this->init_query();
    }

    $this->init_handlers();

    $this->_build_arguments();
  }

  /**
   * Get the URL for the current view.
   *
   * This URL will be adjusted for arguments.
   */
  function get_url($args = NULL, $path = NULL) {
    if (!isset($path)) {
      $path = $this->get_path();
    }
    if (!isset($args)) {
      $args = $this->args;
    }
    // Don't bother working if there's nothing to do:
    if (empty($path) || (empty($args) && strpos($path, '%') === FALSE)) {
      return $path;
    }

    $pieces = array();
    $arguments = isset($arguments) ? $arguments : $this->display_handler->get_option('arguments');
    $argument_keys = isset($arguments) ? array_keys($arguments) : array();
    $id = current($argument_keys);
    foreach (explode('/', $path) as $piece) {
      if ($piece != '%') {
        $pieces[] = $piece;
      }
      else {
        if (empty($args)) {
          // Try to never put % in a url; use the wildcard instead.
          if ($id && !empty($arguments[$id]['wildcard'])) {
            $pieces[] = $arguments[$id]['wildcard'];
          }
          else {
            $pieces[] = '*'; // gotta put something if there just isn't one.
          }

        }
        else {
          $pieces[] = array_shift($args);
        }

        if ($id) {
          $id = next($argument_keys);
        }
      }
    }

    if (!empty($args)) {
      $pieces = array_merge($pieces, $args);
    }
    return implode('/', $pieces);
  }

  /**
   * Get the base path used for this view.
   */
  function get_path() {
    if (!empty($this->override_path)) {
      return $this->override_path;
    }

    if (empty($this->display_handler)) {
      if (!$this->set_display('default')) {
        return FALSE;
      }
    }
    return $this->display_handler->get_path();
  }

  /**
   * Get the breadcrumb used for this view.
   *
   * @param $set
   *   If true, use drupal_set_breadcrumb() to install the breadcrumb.
   */
  function get_breadcrumb($set = FALSE) {
    // Now that we've built the view, extract the breadcrumb.
    $base = TRUE;
    $breadcrumb = array();

    if (!empty($this->build_info['breadcrumb'])) {
      foreach ($this->build_info['breadcrumb'] as $path => $title) {
        // Check to see if the frontpage is in the breadcrumb trail; if it
        // is, we'll remove that from the actual breadcrumb later.
        if ($path == variable_get('site_frontpage', 'node')) {
          $base = FALSE;
          $title = t('Home');
        }
        if ($title) {
          $breadcrumb[] = l($title, $path, array('html' => true));
        }
      }

      if ($set) {
        if ($base) {
          $breadcrumb = array_merge(drupal_get_breadcrumb(), $breadcrumb);
        }
        drupal_set_breadcrumb($breadcrumb);
      }
    }
    return $breadcrumb;
  }

  /**
   * Is this view cacheable?
   */
  function is_cacheable() {
    return $this->is_cacheable;
  }

  /**
   * Set up query capturing.
   *
   * db_query() stores the queries that it runs in global $queries,
   * bit only if dev_query is set to true. In this case, we want
   * to temporarily override that setting if it's not and we
   * can do that without forcing a db rewrite by just manipulating
   * $conf. This is kind of evil but it works.
   */
  function start_query_capture() {
    global $conf, $queries;
    if (empty($conf['dev_query'])) {
      $this->fix_dev_query = TRUE;
      $conf['dev_query'] = TRUE;
    }

    // Record the last query key used; anything already run isn't
    // a query that we are interested in.
    $this->last_query_key = NULL;

    if (!empty($queries)) {
      $keys = array_keys($queries);
      $this->last_query_key = array_pop($keys);
    }
  }

  /**
   * Add the list of queries run during render to buildinfo.
   *
   * @see view::start_query_capture()
   */
  function end_query_capture() {
    global $conf, $queries;
    if (!empty($this->fix_dev_query)) {
      $conf['dev_query'] = FALSE;
    }

    // make a copy of the array so we can manipulate it with array_splice.
    $temp = $queries;

    // Scroll through the queries until we get to our last query key.
    // Unset anything in our temp array.
    if (isset($this->last_query_key)) {
      while (list($id, $query) = each($queries)) {
        if ($id == $this->last_query_key) {
          break;
        }

        unset($temp[$id]);
      }
    }

    $this->additional_queries = $temp;
  }

  /**
   * Load a view from the database based upon either vid or name.
   *
   * This is a static factory method that implements internal caching for
   * view objects.
   *
   * @param $arg
   *  The name of the view or its internal view id (vid)
   * @param $reset
   *  If TRUE, reset this entry in the load cache.
   * @return A view object or NULL if it was not available.
   */
  static function &load($arg, $reset = FALSE) {
    static $cache = array();

    // We want a NULL value to return TRUE here, so we can't use isset() or empty().
    if (!array_key_exists($arg, $cache) || $reset) {
      $result = db_select('views_view', 'v')
        ->fields('v')
        ->condition(is_numeric($arg) ? 'vid' : 'name', $arg)
        ->execute();
      foreach ($result as $data) {
        if (empty($data)) {
          $cache[$arg] = NULL;
        }
        else {
          $view = new view();
          $view->load_row($data);
          $view->type = t('Normal');
          // Load all of our subtables.
          foreach ($view->db_objects() as $key) {
            $object_name = "views_$key";
            $result = db_select($object_name, 'v')
              ->fields('v')
              ->condition('vid', $view->vid)
              ->orderBy('position')
              ->execute();
            foreach ($result as $data) {
              $object = new $object_name(FALSE);
              $object->load_row($data);

              // Because it can get complicated with this much indirection,
              // make a shortcut reference.
              $location = &$view->$key;

              // If we have a basic id field, load the item onto the view based on
              // this ID, otherwise push it on.
              if (!empty($object->id)) {
                $location[$object->id] = $object;
              }
              else {
                $location[] = $object;
              }
            }
          }
        }

        $view->loaded = TRUE;
        $cache[$arg] = $view;
      }
    }

    return $cache[$arg];
  }

  /**
   * Static factory method to load a list of views based upon a $where clause.
   *
   * Although this method could be implemented to simply iterate over views::load(),
   * that would be very slow.  Buiding the views externally from unified queries is
   * much faster.
   */
  static function load_views() {
    $result = db_query("SELECT DISTINCT v.* FROM {views_view} v");
    $views = array();
    $vids = array();

    // Load all the views.
    foreach ($result as $data) {
      $view = new view;
      $view->load_row($data);
      $view->loaded = TRUE;
      $view->type = t('Normal');
      $views[$view->name] = $view;
      $names[$view->vid] = $view->name;
    }

    // Stop if we didn't get any views.
    if (!$views) {
      return array();
    }

    $vids = implode(', ', array_keys($names));
    // Now load all the subtables:
    foreach (view::db_objects() as $key) {
      $object_name = "views_$key";
      $result = db_query("SELECT * FROM {{$object_name}} WHERE vid IN (:vids) ORDER BY vid, position",
        array(':vids' => array_keys($names)));

      foreach ($result as $data) {
        $object = new $object_name(FALSE);
        $object->load_row($data);

        // Because it can get complicated with this much indirection,
        // make a shortcut reference.
        $location = &$views[$names[$object->vid]]->$key;

        // If we have a basic id field, load the item onto the view based on
        // this ID, otherwise push it on.
        if (!empty($object->id)) {
          $location[$object->id] = $object;
        }
        else {
          $location[] = $object;
        }
      }
    }
    return $views;
  }

  /**
   * Save the view to the database. If the view does not already exist,
   * A vid will be assigned to the view and also returned from this function.
   */
  function save() {
    if ($this->vid == 'new') {
      $this->vid = NULL;
    }

    // If we have no vid or our vid is a string, this is a new view.
    if (!empty($this->vid)) {
      // remove existing table entries
      foreach ($this->db_objects() as $key) {
        db_delete('views_' . $key)
          ->condition('vid', $this->vid)
          ->execute();
      }
    }

    $this->save_row(!empty($this->vid) ? 'vid' : FALSE);

    // Save all of our subtables.
    foreach ($this->db_objects() as $key) {
      $this->_save_rows($key);
    }

    cache_clear_all('views_urls', 'cache_views');
    cache_clear_all(); // clear the page cache as well.
  }

  /**
   * Save a row to the database for the given key, which is one of the
   * keys from view::db_objects()
   */
  function _save_rows($key) {
    $count = 0;
    foreach ($this->$key as $position => $object) {
      $object->position = ++$count;
      $object->vid = $this->vid;
      $object->save_row();
    }
  }

  /**
   * Delete the view from the database.
   */
  function delete($clear = TRUE) {
    if (empty($this->vid)) {
      return;
    }

    db_delete('views_view')
      ->condition('vid', $this->vid)
      ->execute();
    // Delete from all of our subtables as well.
    foreach ($this->db_objects() as $key) {
      db_delete('views_'. $key)
        ->condition('vid', $this->vid)
        ->execute();
    }

    cache_clear_all('views_query:' . $this->name, 'cache_views');

    if ($clear) {
      cache_clear_all(); // this clears the block and page caches only.
      menu_rebuild(); // force a menu rebuild when a view is deleted.
    }
  }

  /**
   * Export a view as PHP code.
   */
  function export($indent = '') {
    $this->init_display();
    $output = '';
    $output .= $this->export_row('view', $indent);
    // Set the API version
    $output .= $indent . '$view->api_version = \'' . views_api_version() . "';\n";
    $output .= $indent . '$view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */' . "\n";

    foreach ($this->display as $id => $display) {
      $output .= "\n" . $indent . "/* Display: $display->display_title */\n";
      $output .= $indent . '$handler = $view->new_display(' . views_var_export($display->display_plugin, $indent) . ', ' . views_var_export($display->display_title, $indent) . ', \'' . $id . "');\n";
      if (empty($display->handler)) {
        // @todo -- probably need a method of exporting broken displays as
        // they may simply be broken because a module is not installed. That
        // does not invalidate the display.
        continue;
      }

      $output .= $display->handler->export_options($indent, '$handler->options');
    }

    // Give the localization system a chance to export translatables to code.
    if ($this->init_localization()) {
      $this->export_locale_strings('export');
      $translatables = $this->localization_plugin->export_render($indent);
      if (!empty($translatables)) {
        $output .= $translatables;
      }
    }

    return $output;
  }

  /**
   * Make a copy of this view that has been sanitized of all database IDs
   * and handlers and other stuff.
   *
   * I'd call this clone() but it's reserved.
   */
  function copy() {
    $code = $this->export();
    eval($code);
    return $view;
  }

  /**
   * Safely clone a view.
   *
   * Because views are complicated objects within objects, and PHP loves to
   * do references to everything, if a View is not properly and safely
   * cloned it will still have references to the original view, and can
   * actually cause the original view to point to objects in the cloned
   * view. This gets ugly fast.
   *
   * This will completely wipe a view clean so it can be considered fresh.
   */
  function clone_view() {
    $clone = version_compare(phpversion(), '5.0') < 0 ? $this : clone($this);

    $keys = array('current_display', 'display_handler', 'build_info', 'built', 'executed', 'attachment_before', 'attachment_after', 'field', 'argument', 'filter', 'sort', 'relationship', 'header', 'footer', 'empty', 'query', 'inited', 'style_plugin', 'plugin_name', 'exposed_data', 'exposed_input', 'exposed_widgets', 'many_to_one_tables', 'feed_icon');
    foreach ($keys as $key) {
      if (isset($clone->$key)) {
        unset($clone->$key);
      }
    }
    $clone->built = $clone->executed = FALSE;
    $clone->build_info = array();
    $clone->attachment_before = '';
    $clone->attachment_after = '';
    $clone->result = array();

    // shallow cloning means that all the display objects
    // *were not cloned*. We must clone them ourselves.
    $displays = array();
    foreach ($clone->display as $id => $display) {
      $displays[$id] = clone $display;
      if (isset($displays[$id]->handler)) {
        unset($displays[$id]->handler);
      }
    }
    $clone->display = $displays;

    return $clone;
  }

  /**
   * Unset references so that a $view object may be properly garbage
   * collected.
   */
  function destroy() {
    foreach (array_keys($this->display) as $display_id) {
      if (isset($this->display[$display_id]->handler)) {
        $this->display[$display_id]->handler->destroy();
        unset($this->display[$display_id]->handler);
      }

      foreach (views_object_types() as $type => $info) {
        if (isset($this->$type)) {
          $handlers = &$this->$type;
          foreach ($handlers as $id => $item) {
            $handlers[$id]->destroy();
          }
          unset($handlers);
        }
      }

      if (isset($this->style_plugin)) {
        $this->style_plugin->destroy();
        unset($this->style_plugin);
      }

      // Clear these to make sure the view can be processed/used again.
      if (isset($this->display_handler)) {
        unset($this->display_handler);
      }

      if (isset($this->current_display)) {
        unset($this->current_display);
      }

      if (isset($this->query)) {
        unset($this->query);
      }

      $keys = array('current_display', 'display_handler', 'build_info', 'built', 'executed', 'attachment_before', 'attachment_after', 'field', 'argument', 'filter', 'sort', 'relationship', 'header', 'footer', 'empty', 'query', 'result', 'inited', 'style_plugin', 'plugin_name', 'exposed_data', 'exposed_input', 'many_to_one_tables');
      foreach ($keys as $key) {
        if (isset($this->$key)) {
          unset($this->$key);
        }
      }
      $this->built = $this->executed = FALSE;
      $this->build_info = array();
      $this->attachment_before = '';
      $this->attachment_after = '';
    }
  }

  /**
   * Make sure the view is completely valid.
   *
   * @return
   *   TRUE if the view is valid; an array of error strings if it is not.
   */
  function validate() {
    $this->init_display();

    $errors = array();

    foreach ($this->display as $id => $display) {
      if ($display->handler) {
        if (!empty($display->deleted)) {
          continue;
        }

        $result = $this->display[$id]->handler->validate();
        if (!empty($result) && is_array($result)) {
          $errors = array_merge($errors, $result);
        }
      }
    }

    return $errors ? $errors : TRUE;
  }

  /**
   * Find and initialize the localizer plugin.
   */
  function init_localization() {
    if (isset($this->localization_plugin) && is_object($this->localization_plugin)) {
      return TRUE;
    }

    $this->localization_plugin = views_get_plugin('localization', variable_get('views_localization_plugin', 'core'));

    if (empty($this->localization_plugin)) {
      return FALSE;
    }

    /**
    * Figure out whether there should be options.
    */
    $this->localization_plugin->init($this);

    return $this->localization_plugin->translate;
  }

  /**
   * Determine whether a view supports admin string translation.
   */
  function is_translatable() {
    // If the view is normal or overridden, use admin string translation.
    // A newly created view won't have a type. Accept this.
    return (!isset($this->type) || in_array($this->type, array(t('Normal'), t('Overridden')))) ? TRUE : FALSE;
  }

  /**
   * Send strings for localization.
   */
  function save_locale_strings() {
    $this->process_locale_strings('save');
  }

  /**
   * Delete localized strings.
   */
  function delete_locale_strings() {
    $this->process_locale_strings('delete');
  }

 /**
  * Export localized strings.
  */
  function export_locale_strings() {
    $this->process_locale_strings('export');
  }

  /**
   * Process strings for localization, deletion or export to code.
   */
  function process_locale_strings($op) {
    // Ensure this view supports translation, we have a display, and we
    // have a localization plugin.
    // @fixme Export does not init every handler.
    if (($this->is_translatable() || $op == 'export') && $this->init_display() && $this->init_localization()) {
      $this->localization_plugin->process_locale_strings($op);
    }
  }

}

/**
 * Base class for views' database objects.
 */
class views_db_object {
  /**
   * Initialize this object, setting values from schema defaults.
   *
   * @param $init
   *   If an array, this is a set of values from db_fetch_object to
   *   load. Otherwse, if TRUE values will be filled in from schema
   *   defaults.
   */
  function init($init = TRUE) {
    if (is_array($init)) {
      return $this->load_row($init);
    }

    if (!$init) {
      return;
    }

    $schema = drupal_get_schema($this->db_table);

    if (!$schema) {
      return;
    }

    // Go through our schema and build correlations.
    foreach ($schema['fields'] as $field => $info) {
      if ($info['type'] == 'serial') {
        $this->$field = NULL;
      }
      if (!isset($this->$field)) {
        if (!empty($info['serialize']) && isset($info['serialized default'])) {
          $this->$field = unserialize($info['serialized default']);
        }
        elseif (isset($info['default'])) {
          $this->$field = $info['default'];
        }
        else {
          $this->$field = '';
        }
      }
    }
  }

  /**
   * Write the row to the database.
   *
   * @param $update
   *   If true this will be an UPDATE query. Otherwise it will be an INSERT.
   */
  function save_row($update = NULL) {
    $fields = $defs = $values = $serials = array();
    $schema = drupal_get_schema($this->db_table);

    // Go through our schema and build correlations.
    foreach ($schema['fields'] as $field => $info) {
      // special case -- skip serial types if we are updating.
      if ($info['type'] == 'serial') {
        $serials[] = $field;
        continue;
      }
      elseif ($info['type'] == 'int') {
        $this->$field = (int) $this->$field;
      }
      $fields[$field] = empty($info['serialize']) ? $this->$field : serialize($this->$field);
    }
    if (!$update) {
      $query = db_insert($this->db_table);
    }
    else {
      $query = db_update($this->db_table)
        ->condition($update, $this->$update);
    }
    $return = $query
      ->fields($fields)
      ->execute();

    if ($serials && !$update) {
      // get last insert ids and fill them in.
      // Well, one ID.
      foreach ($serials as $field) {
        $this->$field = $return;
      }
    }
  }

  /**
   * Load the object with a row from the database.
   *
   * This method is separate from the constructor in order to give us
   * more flexibility in terms of how the view object is built in different
   * contexts.
   *
   * @param $data
   *   An object from db_fetch_object. It should contain all of the fields
   *   that are in the schema.
   */
  function load_row($data) {
    $schema = drupal_get_schema($this->db_table);

    // Go through our schema and build correlations.
    foreach ($schema['fields'] as $field => $info) {
      $this->$field = empty($info['serialize']) ? $data->$field : unserialize($data->$field);
    }
  }

  /**
   * Export a loaded row, such as an argument, field or the view itself to PHP code.
   *
   * @param $identifier
   *   The variable to assign the PHP code for this object to.
   * @param $indent
   *   An optional indentation for prettifying nested code.
   */
  function export_row($identifier = NULL, $indent = '') {
    if (!$identifier) {
      $identifier = $this->db_table;
    }
    $schema = drupal_get_schema($this->db_table);

    $output = $indent . '$' . $identifier . ' = new ' . get_class($this) . ";\n";
    // Go through our schema and build correlations.
    foreach ($schema['fields'] as $field => $info) {
      if (!empty($info['no export'])) {
        continue;
      }
      if (!isset($this->$field)) {
        if (isset($info['default'])) {
          $this->$field = $info['default'];
        }
        else {
          $this->$field = '';
        }

        // serialized defaults must be set as serialized.
        if (isset($info['serialize'])) {
          $this->$field = unserialize(db_decode_blob($this->$field));
        }
      }
      $value = $this->$field;
      if ($info['type'] == 'int') {
        if (isset($info['size']) && $info['size'] == 'tiny') {
          $value = (bool) $value;
        }
        else {
          $value = (int) $value;
        }
      }

      $output .= $indent . '$' . $identifier . '->' . $field . ' = ' . views_var_export($value, $indent) . ";\n";
    }
    return $output;
  }

  /**
   * Add a new display handler to the view, automatically creating an id.
   *
   * @param $type
   *   The plugin type from the views plugin data. Defaults to 'page'.
   * @param $title
   *   The title of the display; optional, may be filled in from default.
   * @param $id
   *   The id to use.
   * @return
   *   The key to the display in $view->display, so that the new display
   *   can be easily located.
   */
  function add_display($type = 'page', $title = NULL, $id = NULL) {
    if (empty($type)) {
      return FALSE;
    }

    $plugin = views_fetch_plugin_data('display', $type);
    if (empty($plugin)) {
      $plugin['title'] = t('Broken');
    }


    if (empty($id)) {
      $title = $plugin['title'];

      $id = $this->generate_display_id($type);
      $count = str_replace($type . '_', '', $id);
      if (empty($title)) {
        $title = $plugin['title'] . ' ' . $count;
      }
    }

    // Create the new display object
    $display = new views_display;
    $display->options($type, $id, $title);

    // Add the new display object to the view.
    $this->display[$id] = $display;
    return $id;
  }

  /**
   * Generate a display id of a certain plugin type.
   *
   * @param $type
   *   Which plugin should be used for the new display id.
   */
  function generate_display_id($type) {
    // 'default' is singular and is unique, so just go with 'default'
    // for it. For all others, start counting.
    if ($type == 'default') {
      return 'default';
    }
    // Initial id.
    $id = $type . '_1';
    $count = 1;

    // Loop through IDs based upon our style plugin name until
    // we find one that is unused.
    while (!empty($this->display[$id])) {
      $id = $type . '_' . ++$count;
    }

    return $id;
  }

  /**
   * Create a new display and a display handler for it.
   * @param $type
   *   The plugin type from the views plugin data. Defaults to 'page'.
   * @param $title
   *   The title of the display; optional, may be filled in from default.
   * @param $id
   *   The id to use.
   * @return
   *   A reference to the new handler object.
   */
  function &new_display($type = 'page', $title = NULL, $id = NULL) {
    $id = $this->add_display($type, $title, $id);

    // Create a handler
    $this->display[$id]->handler = views_get_plugin('display', $this->display[$id]->display_plugin);
    if (empty($this->display[$id]->handler)) {
      // provide a 'default' handler as an emergency. This won't work well but
      // it will keep things from crashing.
      $this->display[$id]->handler = views_get_plugin('display', 'default');
    }

    if (!empty($this->display[$id]->handler)) {
      // Initialize the new display handler with data.
      $this->display[$id]->handler->init($this, $this->display[$id]);
      // If this is NOT the default display handler, let it know which is
      if ($id != 'default') {
        $this->display[$id]->handler->default_display = &$this->display['default']->handler;
      }
    }

    return $this->display[$id]->handler;
  }

  /**
   * Add an item with a handler to the view.
   *
   * These items may be fields, filters, sort criteria, or arguments.
   */
  function add_item($display_id, $type, $table, $field, $options = array(), $id = NULL) {
    $types = views_object_types();
    $this->set_display($display_id);

    $fields = $this->display[$display_id]->handler->get_option($types[$type]['plural']);

    if (empty($id)) {
      $count = 0;
      $id = $field;
      while (!empty($fields[$id])) {
        $id = $field . '_' . ++$count;
      }
    }

    $new_item = array(
      'id' => $id,
      'table' => $table,
      'field' => $field,
    ) + $options;

    if (!empty($types[$type]['type'])) {
      $handler_type = $types[$type]['type'];
    }
    else {
      $handler_type = $type;
    }

    $handler = views_get_handler($table, $field, $handler_type);

    $fields[$id] = $new_item;
    $this->display[$display_id]->handler->set_option($types[$type]['plural'], $fields);

    return $id;
  }

  /**
   * Get an array of items for the current display.
   */
  function get_items($type, $display_id = NULL) {
    $this->set_display($display_id);

    if (!isset($display_id)) {
      $display_id = $this->current_display;
    }

    // Get info about the types so we can get the right data.
    $types = views_object_types();
    return $this->display[$display_id]->handler->get_option($types[$type]['plural']);
  }

  /**
   * Get the configuration of an item (field/sort/filter/etc) on a given
   * display.
   */
  function get_item($display_id, $type, $id) {
    // Get info about the types so we can get the right data.
    $types = views_object_types();
    // Initialize the display
    $this->set_display($display_id);

    // Get the existing configuration
    $fields = $this->display[$display_id]->handler->get_option($types[$type]['plural']);

    return isset($fields[$id]) ? $fields[$id] : NULL;
  }

  /**
   * Get the configuration of an item (field/sort/filter/etc) on a given
   * display.
   *
   * Pass in NULL for the $item to remove an item.
   */
  function set_item($display_id, $type, $id, $item) {
    // Get info about the types so we can get the right data.
    $types = views_object_types();
    // Initialize the display
    $this->set_display($display_id);

    // Get the existing configuration
    $fields = $this->display[$display_id]->handler->get_option($types[$type]['plural']);
    if (isset($item)) {
      $fields[$id] = $item;
    }
    else {
      unset($fields[$id]);
    }

    // Store.
    $this->display[$display_id]->handler->set_option($types[$type]['plural'], $fields);
  }

  /**
   * Set an option on an item.
   *
   * Use this only if you have just 1 or 2 options to set; if you have
   * many, consider getting the item, adding the options and doing
   * set_item yourself.
   */
  function set_item_option($display_id, $type, $id, $option, $value) {
    $item = $this->get_item($display_id, $type, $id);
    $item[$option] = $value;
    $this->set_item($display_id, $type, $id, $item);
  }
}

/**
 * A display type in a view.
 *
 * This is just the database storage mechanism, and isn't terribly important
 * to the behavior of the display at all.
 */
class views_display extends views_db_object {
  var $db_table = 'views_display';
  function views_display($init = TRUE) {
    parent::init($init);
  }

  function options($type, $id, $title) {
    $this->display_plugin = $type;
    $this->id = $id;
    $this->display_title = $title;
  }
}

/**
 * Provide a list of views object types used in a view, with some information
 * about them.
 */
function views_object_types() {
  static $retval = NULL;

  // statically cache this so t() doesn't run a bajillion times.
  if (!isset($retval)) {
    $retval = array(
      'field' => array(
        'title' => t('Fields'), // title
        'ltitle' => t('fields'), // lowercase title for mid-sentence
        'stitle' => t('Field'), // singular title
        'lstitle' => t('field'), // singular lowercase title for mid sentence
        'plural' => 'fields',
      ),
      'argument' => array(
        'title' => t('Arguments'),
        'ltitle' => t('arguments'),
        'stitle' => t('Argument'),
        'lstitle' => t('Argument'),
        'plural' => 'arguments',
      ),
      'sort' => array(
        'title' => t('Sort criteria'),
        'ltitle' => t('sort criteria'),
        'stitle' => t('Sort criterion'),
        'lstitle' => t('sort criterion'),
        'plural' => 'sorts',
      ),
      'filter' => array(
        'title' => t('Filters'),
        'ltitle' => t('filters'),
        'stitle' => t('Filter'),
        'lstitle' => t('filter'),
        'plural' => 'filters',
        'options' => 'views_ui_config_filters_form',
      ),
      'relationship' => array(
        'title' => t('Relationships'),
        'ltitle' => t('relationships'),
        'stitle' => t('Relationship'),
        'lstitle' => t('Relationship'),
        'plural' => 'relationships',
      ),
      'header' => array(
        'title' => t('Header'),
        'ltitle' => t('header'),
        'stitle' => t('Header'),
        'lstitle' => t('Header'),
        'plural' => 'header',
        'type' => 'area',
      ),
      'footer' => array(
        'title' => t('Footer'),
        'ltitle' => t('footer'),
        'stitle' => t('Footer'),
        'lstitle' => t('Footer'),
        'plural' => 'footer',
        'type' => 'area',
      ),
      'empty' => array(
        'title' => t('Empty text'),
        'ltitle' => t('empty text'),
        'stitle' => t('Empty text'),
        'lstitle' => t('Empty text'),
        'plural' => 'empty',
        'type' => 'area',
      ),
    );
  }

  return $retval;
}

/**
 * @}
 */
