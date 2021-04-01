<?php

/**
 * @file
 * Common functions that many Drupal modules will need to reference.
 *
 * The functions that are critical and need to be available even when serving
 * a cached page are instead located in bootstrap.inc.
 */

use Drupal\Component\Utility\Bytes;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\SortArray;
use Drupal\Core\Cache\Cache;
use Drupal\Core\DrupalKernel;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * @defgroup php_wrappers PHP wrapper functions
 * @{
 * Functions that are wrappers or custom implementations of PHP functions.
 *
 * Certain PHP functions should not be used in Drupal. Instead, Drupal's
 * replacement functions should be used.
 *
 * For example, for improved or more secure UTF8-handling, or RFC-compliant
 * handling of URLs in Drupal.
 *
 * For ease of use and memorizing, all these wrapper functions use the same name
 * as the original PHP function, but prefixed with "drupal_". Beware, however,
 * that not all wrapper functions support the same arguments as the original
 * functions.
 *
 * You should always use these wrapper functions in your code.
 *
 * Wrong:
 * @code
 *   $my_substring = substr($original_string, 0, 5);
 * @endcode
 *
 * Correct:
 * @code
 *   $my_substring = mb_substr($original_string, 0, 5);
 * @endcode
 *
 * @}
 */

/**
 * Return status for saving which involved creating a new item.
 */
const SAVED_NEW = 1;

/**
 * Return status for saving which involved an update to an existing item.
 */
const SAVED_UPDATED = 2;

/**
 * Return status for saving which deleted an existing item.
 */
const SAVED_DELETED = 3;

/**
 * The default aggregation group for CSS files added to the page.
 */
const CSS_AGGREGATE_DEFAULT = 0;

/**
 * The default aggregation group for theme CSS files added to the page.
 */
const CSS_AGGREGATE_THEME = 100;

/**
 * The default weight for CSS rules that style HTML elements ("base" styles).
 */
const CSS_BASE = -200;

/**
 * The default weight for CSS rules that layout a page.
 */
const CSS_LAYOUT = -100;

/**
 * The default weight for CSS rules that style design components (and their associated states and themes.)
 */
const CSS_COMPONENT = 0;

/**
 * The default weight for CSS rules that style states and are not included with components.
 */
const CSS_STATE = 100;

/**
 * The default weight for CSS rules that style themes and are not included with components.
 */
const CSS_THEME = 200;

/**
 * The default group for JavaScript settings added to the page.
 */
const JS_SETTING = -200;

/**
 * The default group for JavaScript and jQuery libraries added to the page.
 */
const JS_LIBRARY = -100;

/**
 * The default group for module JavaScript code added to the page.
 */
const JS_DEFAULT = 0;

/**
 * The default group for theme JavaScript code added to the page.
 */
const JS_THEME = 100;

/**
 * @defgroup format Formatting
 * @{
 * Functions to format numbers, strings, dates, etc.
 */

/**
 * Generates a string representation for the given byte count.
 *
 * @param $size
 *   A size in bytes.
 * @param $langcode
 *   Optional language code to translate to a language other than what is used
 *   to display the page.
 *
 * @return \Drupal\Core\StringTranslation\TranslatableMarkup
 *   A translated string representation of the size.
 */
function format_size($size, $langcode = NULL) {
  $absolute_size = abs($size);
  if ($absolute_size < Bytes::KILOBYTE) {
    return \Drupal::translation()->formatPlural($size, '1 byte', '@count bytes', [], ['langcode' => $langcode]);
  }
  // Create a multiplier to preserve the sign of $size.
  $sign = $absolute_size / $size;
  foreach (['KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'] as $unit) {
    $absolute_size /= Bytes::KILOBYTE;
    $rounded_size = round($absolute_size, 2);
    if ($rounded_size < Bytes::KILOBYTE) {
      break;
    }
  }
  $args = ['@size' => $rounded_size * $sign];
  $options = ['langcode' => $langcode];
  switch ($unit) {
    case 'KB':
      return new TranslatableMarkup('@size KB', $args, $options);

    case 'MB':
      return new TranslatableMarkup('@size MB', $args, $options);

    case 'GB':
      return new TranslatableMarkup('@size GB', $args, $options);

    case 'TB':
      return new TranslatableMarkup('@size TB', $args, $options);

    case 'PB':
      return new TranslatableMarkup('@size PB', $args, $options);

    case 'EB':
      return new TranslatableMarkup('@size EB', $args, $options);

    case 'ZB':
      return new TranslatableMarkup('@size ZB', $args, $options);

    case 'YB':
      return new TranslatableMarkup('@size YB', $args, $options);
  }
}

/**
 * @} End of "defgroup format".
 */

/**
 * Returns the base URL path (i.e., directory) of the Drupal installation.
 *
 * Function base_path() adds a "/" to the beginning and end of the returned path
 * if the path is not empty. At the very least, this will return "/".
 *
 * Examples:
 * - http://example.com returns "/" because the path is empty.
 * - http://example.com/drupal/folder returns "/drupal/folder/".
 */
function base_path() {
  return $GLOBALS['base_path'];
}

/**
 * Constructs an array of the defaults that are used for JavaScript assets.
 *
 * @param $data
 *   (optional) The default data parameter for the JavaScript asset array.
 *
 * @see hook_js_alter()
 */
function drupal_js_defaults($data = NULL) {
  return [
    'type' => 'file',
    'group' => JS_DEFAULT,
    'weight' => 0,
    'scope' => 'header',
    'cache' => TRUE,
    'preprocess' => TRUE,
    'attributes' => [],
    'version' => NULL,
    'data' => $data,
    'browsers' => [],
  ];
}

/**
 * Assists in attaching the tableDrag JavaScript behavior to a themed table.
 *
 * Draggable tables should be used wherever an outline or list of sortable items
 * needs to be arranged by an end-user. Draggable tables are very flexible and
 * can manipulate the value of form elements placed within individual columns.
 *
 * To set up a table to use drag and drop in place of weight select-lists or in
 * place of a form that contains parent relationships, the form must be themed
 * into a table. The table must have an ID attribute set and it
 * may be set as follows:
 * @code
 * $table = array(
 *   '#type' => 'table',
 *   '#header' => $header,
 *   '#rows' => $rows,
 *   '#attributes' => array(
 *     'id' => 'my-module-table',
 *   ),
 * );
 * return \Drupal::service('renderer')->render($table);
 * @endcode
 *
 * In the theme function for the form, a special class must be added to each
 * form element within the same column, "grouping" them together.
 *
 * In a situation where a single weight column is being sorted in the table, the
 * classes could be added like this (in the theme function):
 * @code
 * $form['my_elements'][$delta]['weight']['#attributes']['class'] = array('my-elements-weight');
 * @endcode
 *
 * Each row of the table must also have a class of "draggable" in order to
 * enable the drag handles:
 * @code
 * $row = array(...);
 * $rows[] = array(
 *   'data' => $row,
 *   'class' => array('draggable'),
 * );
 * @endcode
 *
 * When tree relationships are present, the two additional classes
 * 'tabledrag-leaf' and 'tabledrag-root' can be used to refine the behavior:
 * - Rows with the 'tabledrag-leaf' class cannot have child rows.
 * - Rows with the 'tabledrag-root' class cannot be nested under a parent row.
 *
 * Calling drupal_attach_tabledrag() would then be written as such:
 * @code
 * drupal_attach_tabledrag('my-module-table', array(
 *   'action' => 'order',
 *   'relationship' => 'sibling',
 *   'group' => 'my-elements-weight',
 * );
 * @endcode
 *
 * In a more complex case where there are several groups in one column (such as
 * the block regions on the admin/structure/block page), a separate subgroup
 * class must also be added to differentiate the groups.
 * @code
 * $form['my_elements'][$region][$delta]['weight']['#attributes']['class'] = array('my-elements-weight', 'my-elements-weight-' . $region);
 * @endcode
 *
 * The 'group' option is still 'my-element-weight', and the additional
 * 'subgroup' option will be passed in as 'my-elements-weight-' . $region. This
 * also means that you'll need to call drupal_attach_tabledrag() once for every
 * region added.
 *
 * @code
 * foreach ($regions as $region) {
 *   drupal_attach_tabledrag('my-module-table', array(
 *     'action' => 'order',
 *     'relationship' => 'sibling',
 *     'group' => 'my-elements-weight',
 *     'subgroup' => 'my-elements-weight-' . $region,
 *   ));
 * }
 * @endcode
 *
 * In a situation where tree relationships are present, adding multiple
 * subgroups is not necessary, because the table will contain indentations that
 * provide enough information about the sibling and parent relationships. See
 * MenuForm::BuildOverviewForm for an example creating a table
 * containing parent relationships.
 *
 * @param $element
 *   A form element to attach the tableDrag behavior to.
 * @param array $options
 *   These options are used to generate JavaScript settings necessary to
 *   configure the tableDrag behavior appropriately for this particular table.
 *   An associative array containing the following keys:
 *   - 'table_id': String containing the target table's id attribute.
 *     If the table does not have an id, one will need to be set,
 *     such as <table id="my-module-table">.
 *   - 'action': String describing the action to be done on the form item.
 *      Either 'match' 'depth', or 'order':
 *     - 'match' is typically used for parent relationships.
 *     - 'order' is typically used to set weights on other form elements with
 *       the same group.
 *     - 'depth' updates the target element with the current indentation.
 *   - 'relationship': String describing where the "action" option
 *     should be performed. Either 'parent', 'sibling', 'group', or 'self':
 *     - 'parent' will only look for fields up the tree.
 *     - 'sibling' will look for fields in the same group in rows above and
 *       below it.
 *     - 'self' affects the dragged row itself.
 *     - 'group' affects the dragged row, plus any children below it (the entire
 *       dragged group).
 *   - 'group': A class name applied on all related form elements for this action.
 *   - 'subgroup': (optional) If the group has several subgroups within it, this
 *     string should contain the class name identifying fields in the same
 *     subgroup.
 *   - 'source': (optional) If the $action is 'match', this string should contain
 *     the classname identifying what field will be used as the source value
 *     when matching the value in $subgroup.
 *   - 'hidden': (optional) The column containing the field elements may be
 *     entirely hidden from view dynamically when the JavaScript is loaded. Set
 *     to FALSE if the column should not be hidden.
 *   - 'limit': (optional) Limit the maximum amount of parenting in this table.
 *
 * @see MenuForm::BuildOverviewForm()
 */
function drupal_attach_tabledrag(&$element, array $options) {
  // Add default values to elements.
  $options = $options + [
    'subgroup' => NULL,
    'source' => NULL,
    'hidden' => TRUE,
    'limit' => 0,
  ];

  $group = $options['group'];

  $tabledrag_id = &drupal_static(__FUNCTION__);
  $tabledrag_id = (!isset($tabledrag_id)) ? 0 : $tabledrag_id + 1;

  // If a subgroup or source isn't set, assume it is the same as the group.
  $target = isset($options['subgroup']) ? $options['subgroup'] : $group;
  $source = isset($options['source']) ? $options['source'] : $target;
  $element['#attached']['drupalSettings']['tableDrag'][$options['table_id']][$group][$tabledrag_id] = [
    'target' => $target,
    'source' => $source,
    'relationship' => $options['relationship'],
    'action' => $options['action'],
    'hidden' => $options['hidden'],
    'limit' => $options['limit'],
  ];

  $element['#attached']['library'][] = 'core/drupal.tabledrag';
}

/**
 * Renders an element.
 *
 * This function renders an element. The top level element is shown with show()
 * before rendering, so it will always be rendered even if hide() had been
 * previously used on it.
 *
 * @param $element
 *   The element to be rendered.
 *
 * @return
 *   The rendered element.
 *
 * @see \Drupal\Core\Render\RendererInterface
 * @see show()
 * @see hide()
 */
function render(&$element) {
  if (!$element && $element !== 0) {
    return NULL;
  }
  if (is_array($element)) {
    // Early return if this element was pre-rendered (no need to re-render).
    if (isset($element['#printed']) && $element['#printed'] == TRUE && isset($element['#markup']) && strlen($element['#markup']) > 0) {
      return $element['#markup'];
    }
    show($element);
    return \Drupal::service('renderer')->render($element);
  }
  else {
    // Safe-guard for inappropriate use of render() on flat variables: return
    // the variable as-is.
    return $element;
  }
}

/**
 * Hides an element from later rendering.
 *
 * The first time render() or drupal_render() is called on an element tree,
 * as each element in the tree is rendered, it is marked with a #printed flag
 * and the rendered children of the element are cached. Subsequent calls to
 * render() or drupal_render() will not traverse the child tree of this element
 * again: they will just use the cached children. So if you want to hide an
 * element, be sure to call hide() on the element before its parent tree is
 * rendered for the first time, as it will have no effect on subsequent
 * renderings of the parent tree.
 *
 * @param $element
 *   The element to be hidden.
 *
 * @return
 *   The element.
 *
 * @see render()
 * @see show()
 */
function hide(&$element) {
  $element['#printed'] = TRUE;
  return $element;
}

/**
 * Shows a hidden element for later rendering.
 *
 * You can also use render($element), which shows the element while rendering
 * it.
 *
 * The first time render() or drupal_render() is called on an element tree,
 * as each element in the tree is rendered, it is marked with a #printed flag
 * and the rendered children of the element are cached. Subsequent calls to
 * render() or drupal_render() will not traverse the child tree of this element
 * again: they will just use the cached children. So if you want to show an
 * element, be sure to call show() on the element before its parent tree is
 * rendered for the first time, as it will have no effect on subsequent
 * renderings of the parent tree.
 *
 * @param $element
 *   The element to be shown.
 *
 * @return
 *   The element.
 *
 * @see render()
 * @see hide()
 */
function show(&$element) {
  $element['#printed'] = FALSE;
  return $element;
}

/**
 * Rebuilds the container, flushes all persistent caches, resets all variables, and rebuilds all data structures.
 *
 * At times, it is necessary to re-initialize the entire system to account for
 * changed or new code. This function:
 * - Rebuilds the container if $kernel is not passed in.
 * - Clears all persistent caches:
 *   - The bootstrap cache bin containing base system, module system, and theme
 *     system information.
 *   - The common 'default' cache bin containing arbitrary caches.
 *   - The page cache.
 *   - The URL alias path cache.
 * - Resets all static variables that have been defined via drupal_static().
 * - Clears asset (JS/CSS) file caches.
 * - Updates the system with latest information about extensions (modules and
 *   themes).
 * - Updates the bootstrap flag for modules implementing bootstrap_hooks().
 * - Rebuilds the full database schema information (invoking hook_schema()).
 * - Rebuilds data structures of all modules (invoking hook_rebuild()). In
 *   core this means
 *   - blocks, node types, date formats and actions are synchronized with the
 *     database
 *   - The 'active' status of fields is refreshed.
 * - Rebuilds the menu router.
 *
 * It's discouraged to call this during a regular page request.
 * If you call this function in tests, every code afterwards should use the new
 * container.
 *
 * This means the entire system is reset so all caches and static variables are
 * effectively empty. After that is guaranteed, information about the currently
 * active code is updated, and rebuild operations are successively called in
 * order to synchronize the active system according to the current information
 * defined in code.
 *
 * All modules need to ensure that all of their caches are flushed when
 * hook_cache_flush() is invoked; any previously known information must no
 * longer exist. All following hook_rebuild() operations must be based on fresh
 * and current system data. All modules must be able to rely on this contract.
 *
 * @see \Drupal\Core\Cache\CacheHelper::getBins()
 * @see hook_cache_flush()
 * @see hook_rebuild()
 *
 * This function also resets the theme, which means it is not initialized
 * anymore and all previously added JavaScript and CSS is gone. Normally, this
 * function is called as an end-of-POST-request operation that is followed by a
 * redirect, so this effect is not visible. Since the full reset is the whole
 * point of this function, callers need to take care for backing up all needed
 * variables and properly restoring or re-initializing them on their own. For
 * convenience, this function automatically re-initializes the maintenance theme
 * if it was initialized before.
 *
 * @todo Try to clear page/JS/CSS caches last, so cached pages can still be
 *   served during this possibly long-running operation. (Conflict on bootstrap
 *   cache though.)
 * @todo Add a global lock to ensure that caches are not primed in concurrent
 *   requests.
 *
 * @param \Drupal\Core\DrupalKernel|array $kernel
 *   (optional) The Drupal Kernel. It is the caller's responsibility to rebuild
 *   the container if this is passed in. Sometimes drupal_flush_all_caches is
 *   used as a batch operation so $kernel will be an array, in this instance it
 *   will be treated as if it it NULL.
 */
function drupal_flush_all_caches($kernel = NULL) {
  // This is executed based on old/previously known information if $kernel is
  // not passed in, which is sufficient, since new extensions cannot have any
  // primed caches yet.
  $module_handler = \Drupal::moduleHandler();
  // Flush all persistent caches.
  $module_handler->invokeAll('cache_flush');
  foreach (Cache::getBins() as $service_id => $cache_backend) {
    $cache_backend->deleteAll();
  }

  // Flush asset file caches.
  \Drupal::service('asset.css.collection_optimizer')->deleteAll();
  \Drupal::service('asset.js.collection_optimizer')->deleteAll();
  _drupal_flush_css_js();

  // Reset all static caches.
  drupal_static_reset();

  // Wipe the Twig PHP Storage cache.
  \Drupal::service('twig')->invalidate();

  // Rebuild theme data that is stored in state.
  \Drupal::service('theme_handler')->refreshInfo();
  // In case the active theme gets requested later in the same request we need
  // to reset the theme manager.
  \Drupal::theme()->resetActiveTheme();

  if (!$kernel instanceof DrupalKernel) {
    $kernel = \Drupal::service('kernel');
    $kernel->invalidateContainer();
    $kernel->rebuildContainer();
  }

  // Rebuild module data that is stored in state.
  \Drupal::service('extension.list.module')->reset();

  // Rebuild all information based on new module data.
  \Drupal::moduleHandler()->invokeAll('rebuild');

  // Clear all plugin caches.
  \Drupal::service('plugin.cache_clearer')->clearCachedDefinitions();

  // Rebuild the menu router based on all rebuilt data.
  // Important: This rebuild must happen last, so the menu router is guaranteed
  // to be based on up to date information.
  \Drupal::service('router.builder')->rebuild();

  // Re-initialize the maintenance theme, if the current request attempted to
  // use it. Unlike regular usages of this function, the installer and update
  // scripts need to flush all caches during GET requests/page building.
  if (function_exists('_drupal_maintenance_theme')) {
    \Drupal::theme()->resetActiveTheme();
    drupal_maintenance_theme();
  }
}

/**
 * Changes the dummy query string added to all CSS and JavaScript files.
 *
 * Changing the dummy query string appended to CSS and JavaScript files forces
 * all browsers to reload fresh files.
 */
function _drupal_flush_css_js() {
  // The timestamp is converted to base 36 in order to make it more compact.
  Drupal::state()->set('system.css_js_query_string', base_convert(REQUEST_TIME, 10, 36));
}

/**
 * Outputs debug information.
 *
 * The debug information is passed on to trigger_error() after being converted
 * to a string using print_r() or var_export().
 *
 * @param $data
 *   Data to be output.
 * @param $label
 *   Label to prefix the data.
 * @param $print_r
 *   Flag to switch between print_r() and var_export() for data conversion to
 *   string. Set $print_r to FALSE to use var_export() instead of print_r().
 *   Passing recursive data structures to var_export() will generate an error.
 *
 * @deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. Use dump()
 *   instead.
 *
 * @see https://www.drupal.org/node/3192283
 */
function debug($data, $label = NULL, $print_r = TRUE) {
  @trigger_error('debug() is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. Use dump() instead. See https://www.drupal.org/node/3192283', E_USER_DEPRECATED);
  // Print $data contents to string.
  $string = Html::escape($print_r ? print_r($data, TRUE) : var_export($data, TRUE));

  // Display values with pre-formatting to increase readability.
  $string = '<pre>' . $string . '</pre>';

  trigger_error(trim($label ? "$label: $string" : $string));
}

/**
 * Assembles the Drupal Updater registry.
 *
 * An Updater is a class that knows how to update various parts of the Drupal
 * file system, for example to update modules that have newer releases, or to
 * install a new theme.
 *
 * @return array
 *   The Drupal Updater class registry.
 *
 * @see \Drupal\Core\Updater\Updater
 * @see hook_updater_info()
 * @see hook_updater_info_alter()
 */
function drupal_get_updaters() {
  $updaters = &drupal_static(__FUNCTION__);
  if (!isset($updaters)) {
    $updaters = \Drupal::moduleHandler()->invokeAll('updater_info');
    \Drupal::moduleHandler()->alter('updater_info', $updaters);
    uasort($updaters, [SortArray::class, 'sortByWeightElement']);
  }
  return $updaters;
}

/**
 * Assembles the Drupal FileTransfer registry.
 *
 * @return
 *   The Drupal FileTransfer class registry.
 *
 * @see \Drupal\Core\FileTransfer\FileTransfer
 * @see hook_filetransfer_info()
 * @see hook_filetransfer_info_alter()
 */
function drupal_get_filetransfer_info() {
  $info = &drupal_static(__FUNCTION__);
  if (!isset($info)) {
    $info = \Drupal::moduleHandler()->invokeAll('filetransfer_info');
    \Drupal::moduleHandler()->alter('filetransfer_info', $info);
    uasort($info, [SortArray::class, 'sortByWeightElement']);
  }
  return $info;
}
