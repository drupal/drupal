<?php

/**
 * @file
 * Definition of Drupal\views\ViewExecutable.
 */

namespace Drupal\views;

use Drupal\Core\DependencyInjection\DependencySerialization;
use Drupal\Core\Session\AccountInterface;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ViewStorageInterface;
use Drupal\Component\Utility\Tags;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @defgroup views_objects Objects that represent a View or part of a view
 * @{
 * These objects are the core of Views do the bulk of the direction and
 * storing of data. All database activity is in these objects.
 */

/**
 * An object to contain all of the data to generate a view, plus the member
 * functions to build the view query, execute the query and render the output.
 */
class ViewExecutable extends DependencySerialization {

  /**
   * The config entity in which the view is stored.
   *
   * @var \Drupal\views\Entity\View
   */
  public $storage;

  /**
   * Whether or not the view has been built.
   *
   * @todo Group with other static properties.
   *
   * @var bool
   */
  public $built = FALSE;

  /**
   * Whether the view has been executed/query has been run.
   *
   * @todo Group with other static properties.
   *
   * @var bool
   */
  public $executed = FALSE;

  /**
   * Any arguments that have been passed into the view.
   *
   * @var array
   */
  public $args = array();

  /**
   * An array of build info.
   *
   * @var array
   */
  public $build_info = array();

  /**
   * Whether this view uses AJAX.
   *
   * @var bool
   */
  protected $ajaxEnabled = FALSE;

  /**
   * Where the results of a query will go.
   *
   * The array must use a numeric index starting at 0.
   *
   * @var \Drupal\views\ResultRow[]
   */
  public $result = array();

  // May be used to override the current pager info.

  /**
   * The current page. If the view uses pagination.
   *
   * @var int
   */
  public $current_page = NULL;

  /**
   * The number of items per page.
   *
   * @var int
   */
  public $items_per_page = NULL;

  /**
   * The pager offset.
   *
   * @var int
   */
  public $offset = NULL;

  /**
   * The total number of rows returned from the query.
   *
   * @var int
   */
  public $total_rows = NULL;

  /**
   * Attachments to place before the view.
   *
   * @var array()
   */
  public $attachment_before = array();

  /**
   * Attachments to place after the view.
   *
   * @var array
   */
  public $attachment_after = array();

  // Exposed widget input

  /**
   * All the form data from $form_state['values'].
   *
   * @var array
   */
  public $exposed_data = array();

  /**
   * An array of input values from exposed forms.
   *
   * @var array
   */
  public $exposed_input = array();

  /**
   * Exposed widget input directly from the $form_state['values'].
   *
   * @var array
   */
  public $exposed_raw_input = array();

  /**
   * Used to store views that were previously running if we recurse.
   *
   * @var array
   */
  public $old_view = array();

  /**
   * To avoid recursion in views embedded into areas.
   *
   * @var array
   */
  public $parent_views = array();

  /**
   * Whether this view is an attachment to another view.
   *
   * @var bool
   */
  public $is_attachment = NULL;

  /**
   * Identifier of the current display.
   *
   * @var string
   */
  public $current_display;

  /**
   * Where the $query object will reside.
   *
   * @var \Drupal\views\Plugin\views\query\QueryPluginBase
   */
  public $query = NULL;

  /**
   * The used pager plugin used by the current executed view.
   *
   * @var \Drupal\views\Plugin\views\pager\PagerPluginBase
   */
  public $pager = NULL;

  /**
   * The current used display plugin.
   *
   * @var \Drupal\views\Plugin\views\display\DisplayPluginBase
   */
  public $display_handler;

  /**
   * The list of used displays of the view.
   *
   * An array containing Drupal\views\Plugin\views\display\DisplayPluginBase
   * objects.
   *
   * @var \Drupal\views\DisplayBag
   */
  public $displayHandlers;

  /**
   * The current used style plugin.
   *
   * @var \Drupal\views\Plugin\views\style\StylePluginBase
   */
  public $style_plugin;

  /**
   * The current used row plugin, if the style plugin supports row plugins.
   *
   * @var \Drupal\views\Plugin\views\row\RowPluginBase
   */
  public $rowPlugin;

  /**
   * Stores the current active row while rendering.
   *
   * @var int
   */
  public $row_index;

  /**
   * Allow to override the url of the current view.
   *
   * @var string
   */
  public $override_url = NULL;

  /**
   * Allow to override the path used for generated urls.
   *
   * @var string
   */
  public $override_path = NULL;

  /**
   * Allow to override the used database which is used for this query.
   *
   * @var bool
   */
  public $base_database = NULL;

  // Handlers which are active on this view.

  /**
   * Stores the field handlers which are initialized on this view.
   *
   * An array containing Drupal\views\Plugin\views\field\FieldPluginBase
   * objects.
   *
   * @var array
   */
  public $field;

  /**
   * Stores the argument handlers which are initialized on this view.
   *
   * An array containing Drupal\views\Plugin\views\argument\ArgumentPluginBase
   * objects.
   *
   * @var array
   */
  public $argument;

  /**
   * Stores the sort handlers which are initialized on this view.
   *
   * An array containing Drupal\views\Plugin\views\sort\SortPluginBase objects.
   *
   * @var array
   */
  public $sort;

  /**
   * Stores the filter handlers which are initialized on this view.
   *
   * An array containing Drupal\views\Plugin\views\filter\FilterPluginBase
   * objects.
   *
   * @var array
   */
  public $filter;

  /**
   * Stores the relationship handlers which are initialized on this view.
   *
   * An array containing Drupal\views\Plugin\views\relationship\RelationshipPluginBase
   * objects.
   *
   * @var array
   */
  public $relationship;

  /**
   * Stores the area handlers for the header which are initialized on this view.
   *
   * An array containing Drupal\views\Plugin\views\area\AreaPluginBase objects.
   *
   * @var array
   */
  public $header;

  /**
   * Stores the area handlers for the footer which are initialized on this view.
   *
   * An array containing Drupal\views\Plugin\views\area\AreaPluginBase objects.
   *
   * @var array
   */
  public $footer;

  /**
   * Stores the area handlers for the empty text which are initialized on this view.
   *
   * An array containing Drupal\views\Plugin\views\area\AreaPluginBase objects.
   *
   * @var array
   */
  public $empty;

  /**
   * Stores the current response object.
   *
   * @var \Symfony\Component\HttpFoundation\Response
   */
  protected $response = NULL;

  /**
   * Stores the current request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Does this view already have loaded it's handlers.
   *
   * @todo Group with other static properties.
   *
   * @var bool
   */
  public $inited;

  /**
   * The rendered output of the exposed form.
   *
   * @var string
   */
  public $exposed_widgets;

  /**
   * If this view has been previewed.
   *
   * @var bool
   */
  public $preview;

  /**
   * Force the query to calculate the total number of results.
   *
   * @todo Move to the query.
   *
   * @var bool
   */
  public $get_total_rows;

  /**
   * Indicates if the sorts have been built.
   *
   * @todo Group with other static properties.
   *
   * @var bool
   */
  public $build_sort;

  /**
   * Stores the many-to-one tables for performance.
   *
   * @var array
   */
  public $many_to_one_tables;

  /**
   * A unique identifier which allows to update multiple views output via js.
   *
   * @var string
   */
  public $dom_id;

  /**
   * A render array container to store render related information.
   *
   * For example you can alter the array and attach some css/js via the
   * #attached key. This is the required way to add custom css/js.
   *
   * @var array
   *
   * @see drupal_process_attached
   */
  public $element = array(
    '#attached' => array(
      'css' => array(),
      'js' => array(),
      'library' => array(),
    ),
  );

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * Should the admin links be shown on the rendered view.
   *
   * @var bool
   */
  protected $showAdminLinks;

  /**
   * Constructs a new ViewExecutable object.
   *
   * @param \Drupal\views\ViewStorageInterface $storage
   *   The view config entity the actual information is stored on.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   */
  public function __construct(ViewStorageInterface $storage, AccountInterface $user) {
    // Reference the storage and the executable to each other.
    $this->storage = $storage;
    $this->storage->set('executable', $this);
    $this->user = $user;

    // Add the default css for a view.
    $this->element['#attached']['library'][] = 'views/views.module';
  }

  /**
   * @todo.
   */
  public function save() {
    $this->storage->save();
  }

  /**
   * Set the arguments that come to this view. Usually from the URL
   * but possibly from elsewhere.
   */
  public function setArguments($args) {
    $this->args = $args;
  }

  /**
   * Change/Set the current page for the pager.
   */
  public function setCurrentPage($page) {
    $this->current_page = $page;

    // If the pager is already initialized, pass it through to the pager.
    if (!empty($this->pager)) {
      return $this->pager->setCurrentPage($page);
    }
  }

  /**
   * Get the current page from the pager.
   */
  public function getCurrentPage() {
    // If the pager is already initialized, pass it through to the pager.
    if (!empty($this->pager)) {
      return $this->pager->getCurrentPage();
    }

    if (isset($this->current_page)) {
      return $this->current_page;
    }
  }

  /**
   * Get the items per page from the pager.
   */
  public function getItemsPerPage() {
    // If the pager is already initialized, pass it through to the pager.
    if (!empty($this->pager)) {
      return $this->pager->getItemsPerPage();
    }

    if (isset($this->items_per_page)) {
      return $this->items_per_page;
    }
  }

  /**
   * Set the items per page on the pager.
   */
  public function setItemsPerPage($items_per_page) {
    $this->items_per_page = $items_per_page;

    // If the pager is already initialized, pass it through to the pager.
    if (!empty($this->pager)) {
      $this->pager->setItemsPerPage($items_per_page);
    }
  }

  /**
   * Get the pager offset from the pager.
   */
  public function getOffset() {
    // If the pager is already initialized, pass it through to the pager.
    if (!empty($this->pager)) {
      return $this->pager->getOffset();
    }

    if (isset($this->offset)) {
      return $this->offset;
    }
  }

  /**
   * Set the offset on the pager.
   */
  public function setOffset($offset) {
    $this->offset = $offset;

    // If the pager is already initialized, pass it through to the pager.
    if (!empty($this->pager)) {
      $this->pager->setOffset($offset);
    }
  }

  /**
   * Determine if the pager actually uses a pager.
   */
  public function usePager() {
    if (!empty($this->pager)) {
      return $this->pager->usePager();
    }
  }

  /**
   * Sets whether or not AJAX should be used.
   *
   * If AJAX is used, paging, tablesorting and exposed filters will be fetched
   * via an AJAX call rather than a page refresh.
   *
   * @param bool $use_ajax
   *   TRUE if AJAX should be used, FALSE otherwise.
   */
  public function setAjaxEnabled($ajax_enabled) {
    $this->ajaxEnabled = (bool) $ajax_enabled;
  }

  /**
   * Whether or not AJAX should be used.
   *
   * @see \Drupal\views\ViewExecutable::setAjaxEnabled().
   *
   * @return bool
   */
  public function ajaxEnabled() {
    return $this->ajaxEnabled;
  }

  /**
   * Set the exposed filters input to an array. If unset they will be taken
   * from \Drupal::request()->query when the time comes.
   */
  public function setExposedInput($filters) {
    $this->exposed_input = $filters;
  }

  /**
   * Figure out what the exposed input for this view is.
   */
  public function getExposedInput() {
    // Fill our input either from \Drupal::request()->query or from something
    // previously set on the view.
    if (empty($this->exposed_input)) {
      $this->exposed_input = \Drupal::request()->query->all();
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
      $display_id = ($this->display_handler->isDefaulted('filters')) ? 'default' : $this->current_display;

      if (empty($this->exposed_input) && !empty($_SESSION['views'][$this->storage->id()][$display_id])) {
        $this->exposed_input = $_SESSION['views'][$this->storage->id()][$display_id];
      }
    }

    return $this->exposed_input;
  }

  /**
   * Set the display for this view and initialize the display handler.
   */
  public function initDisplay() {
    if (isset($this->current_display)) {
      return TRUE;
    }

    // Initialize the display cache array.
    $this->displayHandlers = new DisplayBag($this, Views::pluginManager('display'));

    $this->current_display = 'default';
    $this->display_handler = $this->displayHandlers->get('default');

    return TRUE;
  }

  /**
   * Get the first display that is accessible to the user.
   *
   * @param array|string $displays
   *   Either a single display id or an array of display ids.
   *
   * @return string
   *   The first accessible display id, at least default.
   */
  public function chooseDisplay($displays) {
    if (!is_array($displays)) {
      return $displays;
    }

    $this->initDisplay();

    foreach ($displays as $display_id) {
      if ($this->displayHandlers->get($display_id)->access($this->user)) {
        return $display_id;
      }
    }

    return 'default';
  }

  /**
   * Gets the current display plugin.
   *
   * @return \Drupal\views\Plugin\views\display\DisplayPluginBase
   */
  public function getDisplay() {
    if (!isset($this->display_handler)) {
      $this->initDisplay();
    }

    return $this->display_handler;
  }

  /**
   * Sets the current display.
   *
   * @param string $display_id
   *   The ID of the display to mark as current.
   *
   * @return bool
   *   TRUE if the display was correctly set, FALSE otherwise.
   */
  public function setDisplay($display_id = NULL) {
    // If we have not already initialized the display, do so.
    if (!isset($this->current_display)) {
      // This will set the default display and instantiate the default display
      // plugin.
      $this->initDisplay();
    }

    // If no display ID is passed, we either have initialized the default or
    // already have a display set.
    if (!isset($display_id)) {
      return TRUE;
    }

    $display_id = $this->chooseDisplay($display_id);

    // Ensure the requested display exists.
    if (!$this->displayHandlers->has($display_id)) {
      debug(format_string('setDisplay() called with invalid display ID "@display".', array('@display' => $display_id)));
      return FALSE;
    }

    // Reset if the display has changed. It could be called multiple times for
    // the same display, especially in the UI.
    if ($this->current_display != $display_id) {
      // Set the current display.
      $this->current_display = $display_id;

      // Reset the style and row plugins.
      $this->style_plugin = NULL;
      $this->plugin_name = NULL;
      $this->rowPlugin = NULL;
    }

    if ($display = $this->displayHandlers->get($display_id)) {
      // Set a shortcut.
      $this->display_handler = $display;
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Creates a new display and a display handler instance for it.
   *
   * @param string $plugin_id
   *   (optional) The plugin type from the Views plugin annotation. Defaults to
   *   'page'.
   * @param string $title
   *   (optional) The title of the display. Defaults to NULL.
   * @param string $id
   *   (optional) The ID to use, e.g., 'default', 'page_1', 'block_2'. Defaults
   *   to NULL.
   *
   * @return \Drupal\views\Plugin\views\display\DisplayPluginBase
   *   A new display plugin instance if executable is set, the new display ID
   *   otherwise.
   */
  public function newDisplay($plugin_id = 'page', $title = NULL, $id = NULL) {
    $this->initDisplay();

    $id = $this->storage->addDisplay($plugin_id, $title, $id);
    $this->displayHandlers->addInstanceId($id);

    $display = $this->displayHandlers->get($id);
    $display->newDisplay();
    return $display;
  }

  /**
   * Gets the current style plugin.
   *
   * @return \Drupal\views\Plugin\views\style\StylePluginBase
   */
  public function getStyle() {
    if (!isset($this->style_plugin)) {
      $this->initStyle();
    }

    return $this->style_plugin;
  }

  /**
   * Find and initialize the style plugin.
   *
   * Note that arguments may have changed which style plugin we use, so
   * check the view object first, then ask the display handler.
   */
  public function initStyle() {
    if (isset($this->style_plugin)) {
      return TRUE;
    }

    $this->style_plugin = $this->display_handler->getPlugin('style');

    if (empty($this->style_plugin)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Acquire and attach all of the handlers.
   */
  public function initHandlers() {
    $this->initDisplay();
    if (empty($this->inited)) {
      foreach ($this::getHandlerTypes() as $key => $info) {
        $this->_initHandler($key, $info);
      }
      $this->inited = TRUE;
    }
  }

  /**
   * Get the current pager plugin.
   *
   * @return \Drupal\views\Plugin\views\pager\PagerPluginBase
   */
  public function getPager() {
    if (!isset($this->pager)) {
      $this->initPager();
    }

    return $this->pager;
  }

  /**
   * Initialize the pager
   *
   * Like style initialization, pager initialization is held until late
   * to allow for overrides.
   */
  public function initPager() {
    if (!isset($this->pager)) {
      $this->pager = $this->display_handler->getPlugin('pager');

      if ($this->pager->usePager()) {
        $this->pager->setCurrentPage($this->current_page);
      }

      // These overrides may have been set earlier via $view->set_*
      // functions.
      if (isset($this->items_per_page)) {
        $this->pager->setItemsPerPage($this->items_per_page);
      }

      if (isset($this->offset)) {
        $this->pager->setOffset($this->offset);
      }
    }
  }

  /**
   * Render the pager, if necessary.
   */
  public function renderPager($exposed_input) {
    if (!empty($this->pager) && $this->pager->usePager()) {
      return $this->pager->render($exposed_input);
    }

    return '';
  }

  /**
   * Create a list of base tables eligible for this view. Used primarily
   * for the UI. Display must be already initialized.
   */
  public function getBaseTables() {
    $base_tables = array(
      $this->storage->get('base_table') => TRUE,
      '#global' => TRUE,
    );

    foreach ($this->display_handler->getHandlers('relationship') as $handler) {
      $base_tables[$handler->definition['base']] = TRUE;
    }
    return $base_tables;
  }

  /**
   * Run the preQuery() on all active handlers.
   */
  protected function _preQuery() {
    foreach ($this::getHandlerTypes() as $key => $info) {
      $handlers = &$this->$key;
      $position = 0;
      foreach ($handlers as $id => $handler) {
        $handlers[$id]->position = $position;
        $handlers[$id]->preQuery();
        $position++;
      }
    }
  }

  /**
   * Run the postExecute() on all active handlers.
   */
  protected function _postExecute() {
    foreach ($this::getHandlerTypes() as $key => $info) {
      $handlers = &$this->$key;
      foreach ($handlers as $id => $handler) {
        $handlers[$id]->postExecute($this->result);
      }
    }
  }

  /**
   * Attach all of the handlers for each type.
   *
   * @param $key
   *   One of 'argument', 'field', 'sort', 'filter', 'relationship'
   * @param $info
   *   The $info from getHandlerTypes for this object.
   */
  protected function _initHandler($key, $info) {
    // Load the requested items from the display onto the object.
    $this->$key = $this->display_handler->getHandlers($key);

    // This reference deals with difficult PHP indirection.
    $handlers = &$this->$key;

    // Run through and test for accessibility.
    foreach ($handlers as $id => $handler) {
      if (!$handler->access($this->user)) {
        unset($handlers[$id]);
      }
    }
  }

  /**
   * Build all the arguments.
   */
  protected function _buildArguments() {
    // Initially, we want to build sorts and fields. This can change, though,
    // if we get a summary view.
    if (empty($this->argument)) {
      return TRUE;
    }

    // build arguments.
    $position = -1;
    $substitutions = array();
    $status = TRUE;

    // Get the title.
    $title = $this->display_handler->getOption('title');

    // Iterate through each argument and process.
    foreach ($this->argument as $id => $arg) {
      $position++;
      $argument = $this->argument[$id];

      if ($argument->broken()) {
        continue;
      }

      $argument->setRelationship();

      $arg = isset($this->args[$position]) ? $this->args[$position] : NULL;
      $argument->position = $position;

      if (isset($arg) || $argument->hasDefaultArgument()) {
        if (!isset($arg)) {
          $arg = $argument->getDefaultArgument();
          // make sure default args get put back.
          if (isset($arg)) {
            $this->args[$position] = $arg;
          }
          // remember that this argument was computed, not passed on the URL.
          $argument->is_default = TRUE;
        }

        // Set the argument, which will also validate that the argument can be set.
        if (!$argument->setArgument($arg)) {
          $status = $argument->validateFail($arg);
          break;
        }

        if ($argument->isException()) {
          $arg_title = $argument->exceptionTitle();
        }
        else {
          $arg_title = $argument->getTitle();
          $argument->query($this->display_handler->useGroupBy());
        }

        // Add this argument's substitution
        $substitutions['%' . ($position + 1)] = $arg_title;
        $substitutions['!' . ($position + 1)] = strip_tags(decode_entities($arg));

        // Test to see if we should use this argument's title
        if (!empty($argument->options['title_enable']) && !empty($argument->options['title'])) {
          $title = $argument->options['title'];
        }
      }
      else {
        // determine default condition and handle.
        $status = $argument->defaultAction();
        break;
      }

      // Be safe with references and loops:
      unset($argument);
    }

    // set the title in the build info.
    if (!empty($title)) {
      $this->build_info['title'] = $title;
    }

    // Store the arguments for later use.
    $this->build_info['substitutions'] = $substitutions;

    return $status;
  }

  /**
   * Gets the current query plugin.
   *
   * @return \Drupal\views\Plugin\views\query\QueryPluginBase
   */
  public function getQuery() {
    if (!isset($this->query)) {
      $this->initQuery();
    }

    return $this->query;
  }

  /**
   * Do some common building initialization.
   */
  public function initQuery() {
    if (!empty($this->query)) {
      $class = get_class($this->query);
      if ($class && $class != 'stdClass') {
        // return if query is already initialized.
        return TRUE;
      }
    }

    // Create and initialize the query object.
    $views_data = Views::viewsData()->get($this->storage->get('base_table'));
    $this->storage->set('base_field', !empty($views_data['table']['base']['field']) ? $views_data['table']['base']['field'] : '');
    if (!empty($views_data['table']['base']['database'])) {
      $this->base_database = $views_data['table']['base']['database'];
    }

    $this->query = $this->display_handler->getPlugin('query');
    return TRUE;
  }

  /**
   * Build the query for the view.
   */
  public function build($display_id = NULL) {
    if (!empty($this->built)) {
      return;
    }

    if (empty($this->current_display) || $display_id) {
      if (!$this->setDisplay($display_id)) {
        return FALSE;
      }
    }

    // Let modules modify the view just prior to building it.
    $module_handler = \Drupal::moduleHandler();
    $module_handler->invokeAll('views_pre_build', array($this));

    // Attempt to load from cache.
    // @todo Load a build_info from cache.

    $start = microtime(TRUE);
    // If that fails, let's build!
    $this->build_info = array(
      'query' => '',
      'count_query' => '',
      'query_args' => array(),
    );

    $this->initQuery();

    // Call a module hook and see if it wants to present us with a
    // pre-built query or instruct us not to build the query for
    // some reason.
    // @todo: Implement this. Use the same mechanism Panels uses.

    // Run through our handlers and ensure they have necessary information.
    $this->initHandlers();

    // Let the handlers interact with each other if they really want.
    $this->_preQuery();

    if ($this->display_handler->usesExposed()) {
      $exposed_form = $this->display_handler->getPlugin('exposed_form');
      $this->exposed_widgets = $exposed_form->renderExposedForm();
      if (\Drupal::formBuilder()->getAnyErrors() || !empty($this->build_info['abort'])) {
        $this->built = TRUE;
        // Don't execute the query, $form_state, but rendering will still be executed to display the empty text.
        $this->executed = TRUE;
        return empty($this->build_info['fail']);
      }
    }

    // Build all the relationships first thing.
    $this->_build('relationship');

    // Set the filtering groups.
    if (!empty($this->filter)) {
      $filter_groups = $this->display_handler->getOption('filter_groups');
      if ($filter_groups) {
        $this->query->setGroupOperator($filter_groups['operator']);
        foreach ($filter_groups['groups'] as $id => $operator) {
          $this->query->setWhereGroup($operator, $id);
        }
      }
    }

    // Build all the filters.
    $this->_build('filter');

    $this->build_sort = TRUE;

    // Arguments can, in fact, cause this whole thing to abort.
    if (!$this->_buildArguments()) {
      $this->build_time = microtime(TRUE) - $start;
      $this->attachDisplays();
      return $this->built;
    }

    // Initialize the style; arguments may have changed which style we use,
    // so waiting as long as possible is important. But we need to know
    // about the style when we go to build fields.
    if (!$this->initStyle()) {
      $this->build_info['fail'] = TRUE;
      return FALSE;
    }

    if ($this->style_plugin->usesFields()) {
      $this->_build('field');
    }

    // Build our sort criteria if we were instructed to do so.
    if (!empty($this->build_sort)) {
      // Allow the style handler to deal with sorting.
      if ($this->style_plugin->buildSort()) {
        $this->_build('sort');
      }
      // allow the plugin to build second sorts as well.
      $this->style_plugin->buildSortPost();
    }

    // Allow area handlers to affect the query.
    $this->_build('header');
    $this->_build('footer');
    $this->_build('empty');

    // Allow display handler to affect the query:
    $this->display_handler->query($this->display_handler->useGroupBy());

    // Allow style handler to affect the query:
    $this->style_plugin->query($this->display_handler->useGroupBy());

    // Allow exposed form to affect the query:
    if (isset($exposed_form)) {
      $exposed_form->query();
    }

    if (\Drupal::config('views.settings')->get('sql_signature')) {
      $this->query->addSignature($this);
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
    $this->attachDisplays();

    // Let modules modify the view just after building it.
    $module_handler->invokeAll('views_post_build', array($this));

    return TRUE;
  }

  /**
   * Internal method to build an individual set of handlers.
   *
   * @todo Some filter needs this function, even it is internal.
   *
   * @param string $key
   *    The type of handlers (filter etc.) which should be iterated over to
   *    build the relationship and query information.
   */
  public function _build($key) {
    $handlers = &$this->$key;
    foreach ($handlers as $id => $data) {

      if (!empty($handlers[$id]) && is_object($handlers[$id])) {
        $multiple_exposed_input = array(0 => NULL);
        if ($handlers[$id]->multipleExposedInput()) {
          $multiple_exposed_input = $handlers[$id]->groupMultipleExposedInput($this->exposed_data);
        }
        foreach ($multiple_exposed_input as $group_id) {
          // Give this handler access to the exposed filter input.
          if (!empty($this->exposed_data)) {
            if ($handlers[$id]->isAGroup()) {
              $converted = $handlers[$id]->convertExposedInput($this->exposed_data, $group_id);
              $handlers[$id]->storeGroupInput($this->exposed_data, $converted);
              if (!$converted) {
                continue;
              }
            }
            $rc = $handlers[$id]->acceptExposedInput($this->exposed_data);
            $handlers[$id]->storeExposedInput($this->exposed_data, $rc);
            if (!$rc) {
              continue;
            }
          }
          $handlers[$id]->setRelationship();
          $handlers[$id]->query($this->display_handler->useGroupBy());
        }
      }
    }
  }

  /**
   * Execute the view's query.
   *
   * @param string $display_id
   *   The machine name of the display, which should be executed.
   *
   * @return bool
   *   Return whether the executing was successful, for example an argument
   *   could stop the process.
   */
  public function execute($display_id = NULL) {
    if (empty($this->built)) {
      if (!$this->build($display_id)) {
        return FALSE;
      }
    }

    if (!empty($this->executed)) {
      return TRUE;
    }

    // Don't allow to use deactivated displays, but display them on the live preview.
    if (!$this->display_handler->isEnabled() && empty($this->live_preview)) {
      $this->build_info['fail'] = TRUE;
      return FALSE;
    }

    // Let modules modify the view just prior to executing it.
    $module_handler = \Drupal::moduleHandler();
    $module_handler->invokeAll('views_pre_execute', array($this));

    // Check for already-cached results.
    if (!empty($this->live_preview)) {
      $cache = $this->display_handler->getPlugin('cache', 'none');
    }
    else {
      $cache = $this->display_handler->getPlugin('cache');
    }

    if ($cache->cacheGet('results')) {
      if ($this->pager->usePager()) {
        $this->pager->total_items = $this->total_rows;
        $this->pager->updatePageInfo();
      }
    }
    else {
      $this->query->execute($this);
      // Enforce the array key rule as documented in
      // views_plugin_query::execute().
      $this->result = array_values($this->result);
      $this->_postExecute();
      $cache->cacheSet('results');
    }

    // Let modules modify the view just after executing it.
    $module_handler->invokeAll('views_post_execute', array($this));

    $this->executed = TRUE;
  }

  /**
   * Render this view for a certain display.
   *
   * Note: You should better use just the preview function if you want to
   * render a view.
   *
   * @param string $display_id
   *   The machine name of the display, which should be rendered.
   *
   * @return string|null
   *   Return the output of the rendered view or NULL if something failed in the process.
   */
  public function render($display_id = NULL) {
    $this->execute($display_id);

    // Check to see if the build failed.
    if (!empty($this->build_info['fail'])) {
      return;
    }
    if (!empty($this->build_info['denied'])) {
      return;
    }

    $exposed_form = $this->display_handler->getPlugin('exposed_form');
    $exposed_form->preRender($this->result);

    $module_handler = \Drupal::moduleHandler();

    // Check for already-cached output.
    if (!empty($this->live_preview)) {
      $cache = FALSE;
    }
    else {
      $cache = $this->display_handler->getPlugin('cache');
    }

    if ($cache && $cache->cacheGet('output')) {
    }
    else {
      if ($cache) {
        $cache->cacheStart();
      }

      // Run preRender for the pager as it might change the result.
      if (!empty($this->pager)) {
        $this->pager->preRender($this->result);
      }

      // Initialize the style plugin.
      $this->initStyle();

      if (!isset($this->response)) {
        // Set the response so other parts can alter it.
        $this->response = new Response('', 200);
      }

      // Give field handlers the opportunity to perform additional queries
      // using the entire resultset prior to rendering.
      if ($this->style_plugin->usesFields()) {
        foreach ($this->field as $id => $handler) {
          if (!empty($this->field[$id])) {
            $this->field[$id]->preRender($this->result);
          }
        }
      }

      $this->style_plugin->preRender($this->result);

      // Let each area handler have access to the result set.
      $areas = array('header', 'footer');
      // Only call preRender() on the empty handlers if the result is empty.
      if (empty($this->result)) {
        $areas[] = 'empty';
      }
      foreach ($areas as $area) {
        foreach ($this->{$area} as $handler) {
          $handler->preRender($this->result);
        }
      }

      // Let modules modify the view just prior to rendering it.
      $module_handler->invokeAll('views_pre_render', array($this));

      // Let the themes play too, because pre render is a very themey thing.
      if (isset($GLOBALS['base_theme_info']) && isset($GLOBALS['theme'])) {
        foreach ($GLOBALS['base_theme_info'] as $base) {
          $module_handler->invoke($base->getName(), 'views_pre_render', array($this));
        }

        $module_handler->invoke($GLOBALS['theme'], 'views_pre_render', array($this));
      }

      $this->display_handler->output = $this->display_handler->render();
      if ($cache) {
        $cache->cacheSet('output');
      }
    }

    $exposed_form->postRender($this->display_handler->output);

    if ($cache) {
      $cache->postRender($this->display_handler->output);
    }

    // Let modules modify the view output after it is rendered.
    $module_handler->invokeAll('views_post_render', array($this, &$this->display_handler->output, $cache));

    // Let the themes play too, because post render is a very themey thing.
    if (isset($GLOBALS['base_theme_info']) && isset($GLOBALS['theme'])) {
      foreach ($GLOBALS['base_theme_info'] as $base) {
        $module_handler->invoke($base->getName(), 'views_post_render', array($this));
      }

      $module_handler->invoke($GLOBALS['theme'], 'views_post_render', array($this));
    }

    return $this->display_handler->output;
  }

  /**
   * Execute the given display, with the given arguments.
   * To be called externally by whatever mechanism invokes the view,
   * such as a page callback, hook_block, etc.
   *
   * This function should NOT be used by anything external as this
   * returns data in the format specified by the display. It can also
   * have other side effects that are only intended for the 'proper'
   * use of the display, such as setting page titles.
   *
   * If you simply want to view the display, use View::preview() instead.
   */
  public function executeDisplay($display_id = NULL, $args = array()) {
    if (empty($this->current_display) || $this->current_display != $this->chooseDisplay($display_id)) {
      if (!$this->setDisplay($display_id)) {
        return NULL;
      }
    }

    $this->preExecute($args);

    // Execute the view
    $output = $this->display_handler->execute();

    $this->postExecute();
    return $output;
  }

  /**
   * Preview the given display, with the given arguments.
   *
   * To be called externally, probably by an AJAX handler of some flavor.
   * Can also be called when views are embedded, as this guarantees
   * normalized output.
   *
   * This function does not do any access checks on the view. It is the
   * responsibility of the caller to check $view->access() or implement other
   * access logic. To render the view normally with access checks, use
   * views_embed_view() instead.
   */
  public function preview($display_id = NULL, $args = array()) {
    if (empty($this->current_display) || ((!empty($display_id)) && $this->current_display != $display_id)) {
      if (!$this->setDisplay($display_id)) {
        return FALSE;
      }
    }

    $this->preview = TRUE;
    $this->preExecute($args);
    // Preview the view.
    $output = $this->display_handler->preview();

    $this->postExecute();
    return $output;
  }

  /**
   * Run attachments and let the display do what it needs to do prior
   * to running.
   */
  public function preExecute($args = array()) {
    $this->old_view[] = views_get_current_view();
    views_set_current_view($this);
    $display_id = $this->current_display;

    // Prepare the view with the information we have, but only if we were
    // passed arguments, as they may have been set previously.
    if ($args) {
      $this->setArguments($args);
    }

    // Let modules modify the view just prior to executing it.
    \Drupal::moduleHandler()->invokeAll('views_pre_view', array($this, $display_id, &$this->args));

    // Allow hook_views_pre_view() to set the dom_id, then ensure it is set.
    $this->dom_id = !empty($this->dom_id) ? $this->dom_id : hash('sha256', $this->storage->id() . REQUEST_TIME . mt_rand());

    // Allow the display handler to set up for execution
    $this->display_handler->preExecute();
  }

  /**
   * Unset the current view, mostly.
   */
  public function postExecute() {
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
  public function attachDisplays() {
    if (!empty($this->is_attachment)) {
      return;
    }

    if (!$this->display_handler->acceptAttachments()) {
      return;
    }

    $this->is_attachment = TRUE;
    // Find out which other displays attach to the current one.
    foreach ($this->display_handler->getAttachedDisplays() as $id) {
      // Create a clone for the attachments to manipulate. 'static' refers to the current class name.
      $cloned_view = new static($this->storage, $this->user);
      $cloned_view->setRequest($this->getRequest());
      $this->displayHandlers->get($id)->attachTo($cloned_view, $this->current_display);
    }
    $this->is_attachment = FALSE;
  }

  /**
   * Returns default menu links from the view and the named display handler.
   *
   * @param string $display_id
   *   A display ID.
   * @param array $links
   *   An array of default menu link items passed from
   *   views_menu_link_defaults_alter().
   *
   * @return array|bool
   */
  public function executeHookMenuLinkDefaults($display_id = NULL, &$links = array()) {
    // Prepare the view with the information we have. This was probably already
    // called, but it's good to be safe.
    if (!$this->setDisplay($display_id)) {
      return FALSE;
    }

    // Execute the hook.
    if (isset($this->display_handler)) {
      return $this->display_handler->executeHookMenuLinkDefaults($links);
    }
  }

  /**
   * Determine if the given user has access to the view. Note that
   * this sets the display handler if it hasn't been.
   */
  public function access($displays = NULL, $account = NULL) {
    // No one should have access to disabled views.
    if (!$this->storage->status()) {
      return FALSE;
    }

    if (!isset($this->current_display)) {
      $this->initDisplay();
    }

    if (!$account) {
      $account = $this->user;
    }

    // We can't use choose_display() here because that function
    // calls this one.
    $displays = (array)$displays;
    foreach ($displays as $display_id) {
      if ($this->displayHandlers->has($display_id)) {
        if (($display = $this->displayHandlers->get($display_id)) && $display->access($account)) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * Sets the used response object of the view.
   *
   * @param Symfony\Component\HttpFoundation\Response $response
   *   The response object which should be set.
   */
  public function setResponse(Response $response) {
    $this->response = $response;
  }

  /**
   * Gets the response object used by the view.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response object of the view.
   */
  public function getResponse() {
    if (!isset($this->response)) {
      $this->response = new Response();
    }
    return $this->response;
  }

  /**
   * Sets the request object.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   */
  public function setRequest(Request $request) {
    $this->request = $request;
  }

  /**
   * Gets the request object.
   *
   * @return \Symfony\Component\HttpFoundation\Request $request
   *   Returns the request object.
   */
  public function getRequest() {
    return $this->request;
  }

  /**
   * Get the view's current title. This can change depending upon how it
   * was built.
   */
  public function getTitle() {
    if (empty($this->display_handler)) {
      if (!$this->setDisplay('default')) {
        return FALSE;
      }
    }

    // During building, we might find a title override. If so, use it.
    if (!empty($this->build_info['title'])) {
      $title = $this->build_info['title'];
    }
    else {
      $title = $this->display_handler->getOption('title');
    }

    // Allow substitutions from the first row.
    if ($this->initStyle()) {
      $title = $this->style_plugin->tokenizeValue($title, 0);
    }
    return $title;
  }

  /**
   * Override the view's current title.
   *
   * The tokens in the title get's replaced before rendering.
   */
  public function setTitle($title) {
    $this->build_info['title'] = $title;
    return TRUE;
  }

  /**
   * Force the view to build a title.
   */
  public function buildTitle() {
    $this->initDisplay();

    if (empty($this->built)) {
      $this->initQuery();
    }

    $this->initHandlers();

    $this->_buildArguments();
  }

  /**
   * Get the URL for the current view.
   *
   * This URL will be adjusted for arguments.
   */
  public function getUrl($args = NULL, $path = NULL) {
    if (!empty($this->override_url)) {
      return $this->override_url;
    }

    if (!isset($path)) {
      $path = $this->getPath();
    }
    if (!isset($args)) {
      $args = $this->args;

      // Exclude arguments that were computed, not passed on the URL.
      $position = 0;
      if (!empty($this->argument)) {
        foreach ($this->argument as $argument) {
          if (!empty($argument->is_default) && !empty($argument->options['default_argument_skip_url'])) {
            unset($args[$position]);
          }
          $position++;
        }
      }
    }
    // Don't bother working if there's nothing to do:
    if (empty($path) || (empty($args) && strpos($path, '%') === FALSE)) {
      return $path;
    }

    $pieces = array();
    $argument_keys = isset($this->argument) ? array_keys($this->argument) : array();
    $id = current($argument_keys);
    foreach (explode('/', $path) as $piece) {
      if ($piece != '%') {
        $pieces[] = $piece;
      }
      else {
        if (empty($args)) {
          // Try to never put % in a url; use the wildcard instead.
          if ($id && !empty($this->argument[$id]->options['exception']['value'])) {
            $pieces[] = $this->argument[$id]->options['exception']['value'];
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
  public function getPath() {
    if (!empty($this->override_path)) {
      return $this->override_path;
    }

    if (empty($this->display_handler)) {
      if (!$this->setDisplay('default')) {
        return FALSE;
      }
    }
    return $this->display_handler->getPath();
  }

  /**
   * Gets the current user.
   *
   * Views plugins can recieve the current user in order to not need dependency
   * injection.
   *
   * @return \Drupal\Core\Session\AccountInterface
   */
  public function getUser() {
    return $this->user;
  }

  /**
   * Creates a duplicate ViewExecutable object.
   *
   * Makes a copy of this view that has been sanitized of handlers, any runtime
   * data, ID, and UUID.
   */
  public function createDuplicate() {
    return $this->storage->createDuplicate()->getExecutable();
  }

  /**
   * Unset references so that a $view object may be properly garbage
   * collected.
   */
  public function destroy() {
    foreach ($this::getHandlerTypes() as $type => $info) {
      if (isset($this->$type)) {
        foreach ($this->{$type} as $handler) {
          $handler->destroy();
        }
      }
    }

    if (isset($this->style_plugin)) {
      $this->style_plugin->destroy();
    }

    $reflection = new \ReflectionClass($this);
    $defaults = $reflection->getDefaultProperties();
    // The external dependencies should not be reset. This is not generated by
    // the execution of a view.
    unset($defaults['storage'], $defaults['user'], $defaults['request']);

    foreach ($defaults as $property => $default) {
      $this->{$property} = $default;
    }
  }

  /**
   * Makes sure the view is completely valid.
   *
   * @return array
   *   An array of error strings. This will be empty if there are no validation
   *   errors.
   */
  public function validate() {
    $errors = array();

    $this->initDisplay();
    $current_display = $this->current_display;

    foreach ($this->displayHandlers as $id => $display) {
      if (!empty($display)) {
        if (!empty($display->display['deleted'])) {
          continue;
        }

        $result = $this->displayHandlers->get($id)->validate();
        if (!empty($result) && is_array($result)) {
          $errors[$id] =  $result;
        }
      }
    }

    $this->setDisplay($current_display);

    return $errors;
  }

  /**
   * Provide a list of views handler types used in a view, with some information
   * about them.
   *
   * @return array
   *   An array of associative arrays containing:
   *   - title: The title of the handler type.
   *   - ltitle: The lowercase title of the handler type.
   *   - stitle: A singular title of the handler type.
   *   - lstitle: A singular lowercase title of the handler type.
   *   - plural: Plural version of the handler type.
   *   - (optional) type: The actual internal used handler type. This key is
   *     just used for header,footer,empty to link to the internal type: area.
   */
  public static function getHandlerTypes() {
    return Views::getHandlerTypes();
  }

  /**
   * Returns the valid types of plugins that can be used.
   *
   * @return array
   *   An array of plugin type strings.
   */
  public static function getPluginTypes($type = NULL) {
    return Views::getPluginTypes($type);
  }

  /**
   * Adds an instance of a handler to the view.
   *
   * Items may be fields, filters, sort criteria, or arguments.
   *
   * @param string $display_id
   *   The machine name of the display.
   * @param string $type
   *   The type of handler being added.
   * @param string $table
   *   The name of the table this handler is from.
   * @param string $field
   *   The name of the field this handler is from.
   * @param array $options
   *   (optional) Extra options for this instance. Defaults to an empty array.
   * @param string $id
   *   (optional) A unique ID for this handler instance. Defaults to NULL, in
   *   which case one will be generated.
   *
   * @return string
   *   The unique ID for this handler instance.
   */
  public function addHandler($display_id, $type, $table, $field, $options = array(), $id = NULL) {
    $types = $this::getHandlerTypes();
    $this->setDisplay($display_id);

    $fields = $this->displayHandlers->get($display_id)->getOption($types[$type]['plural']);

    if (empty($id)) {
      $id = $this->generateHandlerId($field, $fields);
    }

    // If the desired type is not found, use the original value directly.
    $handler_type = !empty($types[$type]['type']) ? $types[$type]['type'] : $type;

    $fields[$id] = array(
      'id' => $id,
      'table' => $table,
      'field' => $field,
    ) + $options;

    // Load the plugin ID if available.
    $data = Views::viewsData()->get($table);
    if (isset($data[$field][$handler_type]['id'])) {
      $fields[$id]['plugin_id'] = $data[$field][$handler_type]['id'];
      if ($definition = Views::pluginManager($handler_type)->getDefinition($fields[$id]['plugin_id'], FALSE)) {
        $fields[$id]['provider'] = isset($definition['provider']) ? $definition['provider'] : 'views';
      }
    }

    $this->displayHandlers->get($display_id)->setOption($types[$type]['plural'], $fields);

    return $id;
  }

  /**
   * Generates a unique ID for an handler instance.
   *
   * These handler instances are typically fields, filters, sort criteria, or
   * arguments.
   *
   * @param string $requested_id
   *   The requested ID for the handler instance.
   * @param array $existing_items
   *   An array of existing handler instancess, keyed by their IDs.
   *
   * @return string
   *   A unique ID. This will be equal to $requested_id if no handler instance
   *   with that ID already exists. Otherwise, it will be appended with an
   *   integer to make it unique, e.g., "{$requested_id}_1",
   *   "{$requested_id}_2", etc.
   */
  public static function generateHandlerId($requested_id, $existing_items) {
    $count = 0;
    $id = $requested_id;
    while (!empty($existing_items[$id])) {
      $id = $requested_id . '_' . ++$count;
    }
    return $id;
  }

  /**
   * Gets an array of handler instances for the current display.
   *
   * @param string $type
   *   The type of handlers to retrieve.
   * @param string $display_id
   *   (optional) A specific display machine name to use. If NULL, the current
   *   display will be used.
   *
   * @return array
   *   An array of handler instances of a given type for this display.
   */
  public function getHandlers($type, $display_id = NULL) {
    $this->setDisplay($display_id);

    if (!isset($display_id)) {
      $display_id = $this->current_display;
    }

    // Get info about the types so we can get the right data.
    $types = static::getHandlerTypes();
    return $this->displayHandlers->get($display_id)->getOption($types[$type]['plural']);
  }

  /**
   * Gets the configuration of a handler instance on a given display.
   *
   * @param string $display_id
   *   The machine name of the display.
   * @param string $type
   *   The type of handler to retrieve.
   * @param string $id
   *   The ID of the handler to retrieve.
   *
   * @return array|null
   *   Either the handler instance's configuration, or NULL if the handler is
   *   not used on the display.
   */
  public function getHandler($display_id, $type, $id) {
    // Get info about the types so we can get the right data.
    $types = static::getHandlerTypes();
    // Initialize the display
    $this->setDisplay($display_id);

    // Get the existing configuration
    $fields = $this->displayHandlers->get($display_id)->getOption($types[$type]['plural']);

    return isset($fields[$id]) ? $fields[$id] : NULL;
  }

  /**
   * Sets the configuration of a handler instance on a given display.
   *
   * @param string $display_id
   *   The machine name of the display.
   * @param string $type
   *   The type of handler being set.
   * @param string $id
   *   The ID of the handler being set.
   * @param array|null $item
   *   An array of configuration for a handler, or NULL to remove this instance.
   *
   * @see set_item_option()
   */
  public function setHandler($display_id, $type, $id, $item) {
    // Get info about the types so we can get the right data.
    $types = static::getHandlerTypes();
    // Initialize the display.
    $this->setDisplay($display_id);

    // Get the existing configuration.
    $fields = $this->displayHandlers->get($display_id)->getOption($types[$type]['plural']);
    if (isset($item)) {
      $fields[$id] = $item;
    }

    // Store.
    $this->displayHandlers->get($display_id)->setOption($types[$type]['plural'], $fields);
  }

  /**
   * Removes configuration for a handler instance on a given display.
   *
   * @param string $display_id
   *   The machine name of the display.
   * @param string $type
   *   The type of handler being removed.
   * @param string $id
   *   The ID of the handler being removed.
   */
  public function removeHandler($display_id, $type, $id) {
    // Get info about the types so we can get the right data.
    $types = static::getHandlerTypes();
    // Initialize the display.
    $this->setDisplay($display_id);

    // Get the existing configuration.
    $fields = $this->displayHandlers->get($display_id)->getOption($types[$type]['plural']);
    // Unset the item.
    unset($fields[$id]);

    // Store.
    $this->displayHandlers->get($display_id)->setOption($types[$type]['plural'], $fields);
  }

  /**
   * Sets an option on a handler instance.
   *
   * Use this only if you have just 1 or 2 options to set; if you have many,
   * consider getting the handler instance, adding the options and using
   * set_item() directly.
   *
   * @param string $display_id
   *   The machine name of the display.
   * @param string $type
   *   The type of handler being set.
   * @param string $id
   *   The ID of the handler being set.
   * @param string $option
   *   The configuration key for the value being set.
   * @param mixed $value
   *   The value being set.
   *
   * @see set_item()
   */
  public function setHandlerOption($display_id, $type, $id, $option, $value) {
    $item = $this->getHandler($display_id, $type, $id);
    $item[$option] = $value;
    $this->setHandler($display_id, $type, $id, $item);
  }

  /**
   * Enables admin links on the rendered view.
   *
   * @param bool $show_admin_links
   *   TRUE if the admin links should be shown.
   */
  public function setShowAdminLinks($show_admin_links) {
    $this->showAdminLinks = (bool) $show_admin_links;
  }

  /**
   * Returns whether admin links should be rendered on the view.
   *
   * @return bool
   *  Returns TRUE if admin links should be rendered, else FALSE.
   */
  public function getShowAdminLinks() {
    return $this->showAdminLinks;
  }

  /**
   * Merges all plugin default values for each display.
   */
  public function mergeDefaults() {
    $this->initDisplay();
    // Initialize displays and merge all plugin defaults.
    foreach ($this->displayHandlers as $display) {
      $display->mergeDefaults();
    }
  }

  /**
   * Provide a full array of possible theme functions to try for a given hook.
   *
   * @param string $hook
   *   The hook to use. This is the base theme/template name.
   *
   * @return array
   *   An array of theme hook suggestions.
   */
  public function buildThemeFunctions($hook) {
    $themes = array();
    $display = isset($this->display_handler) ? $this->display_handler->display : NULL;
    $id = $this->storage->id();

    if ($display) {
      $themes[] = $hook . '__' . $id . '__' . $display['id'];
      $themes[] = $hook . '__' . $display['id'];
      // Add theme suggestions for each single tag.
      foreach (Tags::explode($this->storage->get('tag')) as $tag) {
        $themes[] = $hook . '__' . preg_replace('/[^a-z0-9]/', '_', strtolower($tag));
      }

      if ($display['id'] != $display['display_plugin']) {
        $themes[] = $hook . '__' . $id . '__' . $display['display_plugin'];
        $themes[] = $hook . '__' . $display['display_plugin'];
      }
    }
    $themes[] = $hook . '__' . $id;
    $themes[] = $hook;

    return $themes;
  }

  /**
   * Determines if this view has form elements.
   *
   * @return bool
   *   Returns TRUE if this view contains handlers with views form
   *   implementations, FALSE otherwise.
   */
  public function hasFormElements() {
    foreach ($this->field as $field) {
      if (property_exists($field, 'views_form_callback') || method_exists($field, 'viewsForm')) {
        return TRUE;
      }
    }
    $area_handlers = array_merge(array_values($this->header), array_values($this->footer));
    $empty = empty($this->result);
    foreach ($area_handlers as $area) {
      if (method_exists($area, 'viewsForm') && !$area->viewsFormEmpty($empty)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Calculates dependencies for the view.
   *
   * @see \Drupal\views\Entity\View::calculateDependencies()
   *
   * @return array
   *   An array of dependencies grouped by type (module, theme, entity).
   */
  public function calculateDependencies() {
    return $this->storage->calculateDependencies();
  }

}
