<?php

/**
 * @file
 * Common functions that many Drupal modules will need to reference.
 *
 * The functions that are critical and need to be available even when serving
 * a cached page are instead located in bootstrap.inc.
 */

use Drupal\Component\Utility\SortArray;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheTagsPurgeInterface;
use Drupal\Core\DrupalKernel;

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
 *
 * @deprecated in drupal:11.2.0 and is removed from drupal:12.0.0.
 *   There is no replacement.
 *
 * @see https://www.drupal.org/node/3328750
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
 * The default weight for CSS rules that style design components.
 *
 * This applies to the associated states and themes of the component.
 */
const CSS_COMPONENT = 0;

/**
 * The default weight for rules that style states not included with components.
 */
const CSS_STATE = 100;

/**
 * The default weight for rules that style themes not included with components.
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
 * $table = [
 *   '#type' => 'table',
 *   '#header' => $header,
 *   '#rows' => $rows,
 *   '#attributes' => [
 *     'id' => 'my-module-table',
 *   ],
 * ];
 * return \Drupal::service('renderer')->render($table);
 * @endcode
 *
 * In the theme function for the form, a special class must be added to each
 * form element within the same column, "grouping" them together.
 *
 * In a situation where a single weight column is being sorted in the table, the
 * classes could be added like this (in the theme function):
 * @code
 * $form['my_elements'][$delta]['weight']['#attributes']['class'] = ['my-elements-weight'];
 * @endcode
 *
 * Each row of the table must also have a class of "draggable" in order to
 * enable the drag handles:
 * @code
 * $row = [...];
 * $rows[] = [
 *   'data' => $row,
 *   'class' => ['draggable'],
 * ];
 * @endcode
 *
 * When tree relationships are present, the two additional classes
 * 'tabledrag-leaf' and 'tabledrag-root' can be used to refine the behavior:
 * - Rows with the 'tabledrag-leaf' class cannot have child rows.
 * - Rows with the 'tabledrag-root' class cannot be nested under a parent row.
 *
 * Calling drupal_attach_tabledrag() would then be written as such:
 * @code
 * drupal_attach_tabledrag('my-module-table', [
 *   'action' => 'order',
 *   'relationship' => 'sibling',
 *   'group' => 'my-elements-weight',
 * ];
 * @endcode
 *
 * In a more complex case where there are several groups in one column (such as
 * the block regions on the admin/structure/block page), a separate subgroup
 * class must also be added to differentiate the groups.
 * @code
 * $form['my_elements'][$region][$delta]['weight']['#attributes']['class'] = ['my-elements-weight', 'my-elements-weight-' . $region];
 * @endcode
 *
 * The 'group' option is still 'my-element-weight', and the additional
 * 'subgroup' option will be passed in as 'my-elements-weight-' . $region. This
 * also means that you'll need to call drupal_attach_tabledrag() once for every
 * region added.
 *
 * @code
 * foreach ($regions as $region) {
 *   drupal_attach_tabledrag('my-module-table', [
 *     'action' => 'order',
 *     'relationship' => 'sibling',
 *     'group' => 'my-elements-weight',
 *     'subgroup' => 'my-elements-weight-' . $region,
 *   ]);
 * }
 * @endcode
 *
 * In a situation where tree relationships are present, adding multiple
 * subgroups is not necessary, because the table will contain indentations that
 * provide enough information about the sibling and parent relationships. See
 * MenuForm::BuildOverviewForm for an example creating a table
 * containing parent relationships.
 *
 * @param array $element
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
 *   - 'group': A class name applied on all related form elements for this
 *      action.
 *   - 'subgroup': (optional) If the group has several subgroups within it, this
 *     string should contain the class name identifying fields in the same
 *     subgroup.
 *   - 'source': (optional) If the $action is 'match', this string should
 *     contain the classname identifying what field will be used as the source
 *     value when matching the value in $subgroup.
 *   - 'hidden': (optional) The column containing the field elements may be
 *     entirely hidden from view dynamically when the JavaScript is loaded. Set
 *     to FALSE if the column should not be hidden.
 *   - 'limit': (optional) Limit the maximum amount of parenting in this table.
 *
 * @see MenuForm::BuildOverviewForm()
 */
function drupal_attach_tabledrag(&$element, array $options): void {
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
  $target = $options['subgroup'] ?? $group;
  $source = $options['source'] ?? $target;
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
 * Hides an element from later rendering.
 *
 * The first time render() or RenderInterface::render() is called on an element
 * tree, as each element in the tree is rendered, it is marked with a #printed
 * flag and the rendered children of the element are cached. Subsequent calls to
 * render() or RenderInterface::render() will not traverse the child tree of
 * this element again: they will just use the cached children. So if you want to
 * hide an element, be sure to call hide() on the element before its parent tree
 * is rendered for the first time, as it will have no effect on subsequent
 * renderings of the parent tree.
 *
 * @param array $element
 *   The element to be hidden.
 *
 * @return array
 *   The element.
 *
 * @see \Drupal\Core\Render\RendererInterface
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
 * The first time render() or RenderInterface::render() is called on an element
 * tree, as each element in the tree is rendered, it is marked with a #printed
 * flag and the rendered children of the element are cached. Subsequent calls to
 * render() or RenderInterface::render() will not traverse the child tree of
 * this element again: they will just use the cached children. So if you want to
 * show an element, be sure to call show() on the element before its parent tree
 * is rendered for the first time, as it will have no effect on subsequent
 * renderings of the parent tree.
 *
 * @param array $element
 *   The element to be shown.
 *
 * @return array
 *   The element.
 *
 * @see \Drupal\Core\Render\RendererInterface
 * @see render()
 * @see hide()
 */
function show(&$element) {
  $element['#printed'] = FALSE;
  return $element;
}

/**
 * Re-initializes the entire system.
 *
 * To re-initialize the system, the container is rebuilt, all persistent caches
 * are flushed, all variables are reset and all data structures are rebuilt.
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
 * This function also resets the theme, which means it is not initialized
 * anymore and all previously added JavaScript and CSS is gone. Normally, this
 * function is called as an end-of-POST-request operation that is followed by a
 * redirect, so this effect is not visible. Since the full reset is the whole
 * point of this function, callers need to take care for backing up all needed
 * variables and properly restoring or re-initializing them on their own. For
 * convenience, this function automatically re-initializes the maintenance theme
 * if it was initialized before.
 *
 * @param \Drupal\Core\DrupalKernel|array $kernel
 *   (optional) The Drupal Kernel. It is the caller's responsibility to rebuild
 *   the container if this is passed in. Sometimes drupal_flush_all_caches is
 *   used as a batch operation so $kernel will be an array, in this instance it
 *   will be treated as if it is NULL.
 *
 * @see \Drupal\Core\Cache\CacheHelper::getBins()
 * @see hook_cache_flush()
 * @see hook_rebuild()
 *
 * @todo Try to clear page/JS/CSS caches last, so cached pages can still be
 *   served during this possibly long-running operation. (Conflict on bootstrap
 *   cache though.)
 * @todo Add a global lock to ensure that caches are not primed in concurrent
 *   requests.
 */
function drupal_flush_all_caches($kernel = NULL): void {
  // This is executed based on old/previously known information if $kernel is
  // not passed in, which is sufficient, since new extensions cannot have any
  // primed caches yet.
  $module_handler = \Drupal::moduleHandler();
  // Flush all persistent caches.
  $module_handler->invokeAll('cache_flush');
  // Purge cache tags immediately before flushing cache bins. If a cache tag is
  // invalidated between the tags being purged and cache bins are flushed, then
  // it will be included in the checksum of any new cache items, but still valid
  // because the tag was written before the creation of cache item.
  if (($invalidator = \Drupal::service('cache_tags.invalidator')) && ($invalidator instanceof CacheTagsPurgeInterface)) {
    $invalidator->purge();
  }
  foreach (Cache::getBins() as $cache_backend) {
    $cache_backend->deleteAll();
  }

  // Flush asset file caches.
  \Drupal::service('asset.css.collection_optimizer')->deleteAll();
  \Drupal::service('asset.js.collection_optimizer')->deleteAll();
  \Drupal::service('asset.query_string')->reset();

  // Reset all static caches.
  drupal_static_reset();

  // Wipe the Twig PHP Storage cache.
  \Drupal::service('twig')->invalidate();

  // Rebuild profile, profile, theme_engine and theme data.
  \Drupal::service('extension.list.profile')->reset();
  \Drupal::service('extension.list.theme_engine')->reset();
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

  // Reload modules.
  \Drupal::moduleHandler()->reload();

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
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. There is no replacement. Use composer to manage the code for your site. See https://www.drupal.org/node/3512364', E_USER_DEPRECATED);

  $updaters = &drupal_static(__FUNCTION__);
  if (!isset($updaters)) {
    $updaters = \Drupal::moduleHandler()->invokeAllDeprecated('There is no replacement. Use composer to manage the code for your site. See https://www.drupal.org/node/3512364', 'updater_info');
    \Drupal::moduleHandler()->alterDeprecated('There is no replacement. Use composer to manage the code for your site. See https://www.drupal.org/node/3512364', 'updater_info', $updaters);
    uasort($updaters, [SortArray::class, 'sortByWeightElement']);
  }
  return $updaters;
}

/**
 * Assembles the Drupal FileTransfer registry.
 *
 * @return array
 *   The Drupal FileTransfer class registry.
 *
 * @see \Drupal\Core\FileTransfer\FileTransfer
 * @see hook_filetransfer_info()
 * @see hook_filetransfer_info_alter()
 */
function drupal_get_filetransfer_info() {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. There is no replacement. Use composer to manage the code for your site. See https://www.drupal.org/node/3512364', E_USER_DEPRECATED);

  $info = &drupal_static(__FUNCTION__);
  if (!isset($info)) {
    $info = \Drupal::moduleHandler()->invokeAllDeprecated('There is no replacement. Use composer to manage the code for your site. See https://www.drupal.org/node/3512364', 'filetransfer_info');
    \Drupal::moduleHandler()->alterDeprecated('There is no replacement. Use composer to manage the code for your site. See https://www.drupal.org/node/3512364', 'filetransfer_info', $info);
    uasort($info, [SortArray::class, 'sortByWeightElement']);
  }
  return $info;
}
