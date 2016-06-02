<?php

/**
 * @file
 * Hooks related to module and update systems.
 */

use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\Core\Utility\UpdateException;


/**
 * @defgroup update_api Update API
 * @{
 * Updating minor versions of modules
 *
 * When you update code in a module, you may need to update stored data so that
 * the stored data is compatible with the new code. If this update is between
 * two minor versions of your module within the same major version of Drupal,
 * you can use the Update API to update the data. This API is described in brief
 * here; for more details, see https://www.drupal.org/node/2535316. If you are
 * updating your module for a major version of Drupal (for instance, Drupal 7 to
 * Drupal 8), updates will not run and you will need to use the
 * @link migrate Migrate API @endlink instead.
 *
 * @section sec_when When to write update code
 * You need to provide code that performs an update to stored data whenever your
 * module makes a change to its data model. A data model change is any change
 * that makes stored data on an existing site incompatible with that site's
 * updated codebase. Examples:
 * - Configuration changes: adding/removing/renaming a config key, changing the
 *   expected data type or value structure, changing dependencies, schema
 *   changes, etc.
 * - Database schema changes: adding, changing, or removing a database table or
 *   field; moving stored data to different fields or tables; changing the
 *   format of stored data.
 * - Content entity or field changes: adding, changing, or removing a field
 *   definition, entity definition, or any of their properties.
 *
 * @section sec_how How to write update code
 * Update code for a module is put into an implementation of hook_update_N(),
 * which goes into file mymodule.install (if your module's machine name is
 * mymodule). See the documentation of hook_update_N() and
 * https://www.drupal.org/node/2535316 for details and examples.
 *
 * @section sec_test Testing update code
 * Update code should be tested both manually and by writing an automated test.
 * Automated tests for update code extend
 * \Drupal\system\Tests\Update\UpdatePathTestBase -- see that class for details,
 * and find classes that extend it for examples.
 *
 * @see migration
 * @}
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Defines one or more hooks that are exposed by a module.
 *
 * Normally hooks do not need to be explicitly defined. However, by declaring a
 * hook explicitly, a module may define a "group" for it. Modules that implement
 * a hook may then place their implementation in either $module.module or in
 * $module.$group.inc. If the hook is located in $module.$group.inc, then that
 * file will be automatically loaded when needed.
 * In general, hooks that are rarely invoked and/or are very large should be
 * placed in a separate include file, while hooks that are very short or very
 * frequently called should be left in the main module file so that they are
 * always available.
 *
 * See system_hook_info() for all hook groups defined by Drupal core.
 *
 * @return
 *   An associative array whose keys are hook names and whose values are an
 *   associative array containing:
 *   - group: A string defining the group to which the hook belongs. The module
 *     system will determine whether a file with the name $module.$group.inc
 *     exists, and automatically load it when required.
 *
 * @see hook_hook_info_alter()
 */
function hook_hook_info() {
  $hooks['token_info'] = array(
    'group' => 'tokens',
  );
  $hooks['tokens'] = array(
    'group' => 'tokens',
  );
  return $hooks;
}

/**
 * Alter the registry of modules implementing a hook.
 *
 * This hook is invoked during \Drupal::moduleHandler()->getImplementations().
 * A module may implement this hook in order to reorder the implementing
 * modules, which are otherwise ordered by the module's system weight.
 *
 * Note that hooks invoked using \Drupal::moduleHandler->alter() can have
 * multiple variations(such as hook_form_alter() and hook_form_FORM_ID_alter()).
 * \Drupal::moduleHandler->alter() will call all such variants defined by a
 * single module in turn. For the purposes of hook_module_implements_alter(),
 * these variants are treated as a single hook. Thus, to ensure that your
 * implementation of hook_form_FORM_ID_alter() is called at the right time,
 * you will have to change the order of hook_form_alter() implementation in
 * hook_module_implements_alter().
 *
 * @param $implementations
 *   An array keyed by the module's name. The value of each item corresponds
 *   to a $group, which is usually FALSE, unless the implementation is in a
 *   file named $module.$group.inc.
 * @param $hook
 *   The name of the module hook being implemented.
 */
function hook_module_implements_alter(&$implementations, $hook) {
  if ($hook == 'form_alter') {
    // Move my_module_form_alter() to the end of the list.
    // \Drupal::moduleHandler()->getImplementations()
    // iterates through $implementations with a foreach loop which PHP iterates
    // in the order that the items were added, so to move an item to the end of
    // the array, we remove it and then add it.
    $group = $implementations['my_module'];
    unset($implementations['my_module']);
    $implementations['my_module'] = $group;
  }
}

/**
 * Alter the information parsed from module and theme .info.yml files.
 *
 * This hook is invoked in _system_rebuild_module_data() and in
 * \Drupal\Core\Extension\ThemeHandlerInterface::rebuildThemeData(). A module
 * may implement this hook in order to add to or alter the data generated by
 * reading the .info.yml file with \Drupal\Core\Extension\InfoParser.
 *
 * Using implementations of this hook to make modules required by setting the
 * $info['required'] key is discouraged. Doing so will slow down the module
 * installation and uninstallation process. Instead, use
 * \Drupal\Core\Extension\ModuleUninstallValidatorInterface.
 *
 * @param array $info
 *   The .info.yml file contents, passed by reference so that it can be altered.
 * @param \Drupal\Core\Extension\Extension $file
 *   Full information about the module or theme.
 * @param string $type
 *   Either 'module' or 'theme', depending on the type of .info.yml file that
 *   was passed.
 *
 * @see \Drupal\Core\Extension\ModuleUninstallValidatorInterface
 */
function hook_system_info_alter(array &$info, \Drupal\Core\Extension\Extension $file, $type) {
  // Only fill this in if the .info.yml file does not define a 'datestamp'.
  if (empty($info['datestamp'])) {
    $info['datestamp'] = $file->getMTime();
  }
}

/**
 * Perform necessary actions before a module is installed.
 *
 * @param string $module
 *   The name of the module about to be installed.
 */
function hook_module_preinstall($module) {
  mymodule_cache_clear();
}

/**
 * Perform necessary actions after modules are installed.
 *
 * This function differs from hook_install() in that it gives all other modules
 * a chance to perform actions when a module is installed, whereas
 * hook_install() is only called on the module actually being installed. See
 * \Drupal\Core\Extension\ModuleHandler::install() for a detailed description of
 * the order in which install hooks are invoked.
 *
 * This hook should be implemented in a .module file, not in an .install file.
 *
 * @param $modules
 *   An array of the modules that were installed.
 *
 * @see \Drupal\Core\Extension\ModuleHandler::install()
 * @see hook_install()
 */
function hook_modules_installed($modules) {
  if (in_array('lousy_module', $modules)) {
    \Drupal::state()->set('mymodule.lousy_module_compatibility', TRUE);
  }
}

/**
 * Perform setup tasks when the module is installed.
 *
 * If the module implements hook_schema(), the database tables will
 * be created before this hook is fired.
 *
 * If the module provides a MODULE.routing.yml or alters routing information
 * these changes will not be available when this hook is fired. If up-to-date
 * router information is required, for example to use \Drupal\Core\Url, then
 * (preferably) use hook_modules_installed() or rebuild the router in the
 * hook_install() implementation.
 *
 * Implementations of this hook are by convention declared in the module's
 * .install file. The implementation can rely on the .module file being loaded.
 * The hook will only be called when a module is installed. The module's schema
 * version will be set to the module's greatest numbered update hook. Because of
 * this, any time a hook_update_N() is added to the module, this function needs
 * to be updated to reflect the current version of the database schema.
 *
 * See the @link https://www.drupal.org/node/146843 Schema API documentation
 * @endlink for details on hook_schema and how database tables are defined.
 *
 * Note that since this function is called from a full bootstrap, all functions
 * (including those in modules enabled by the current page request) are
 * available when this hook is called. Use cases could be displaying a user
 * message, or calling a module function necessary for initial setup, etc.
 *
 * Please be sure that anything added or modified in this function that can
 * be removed during uninstall should be removed with hook_uninstall().
 *
 * @see hook_schema()
 * @see \Drupal\Core\Extension\ModuleHandler::install()
 * @see hook_uninstall()
 * @see hook_modules_installed()
 */
function hook_install() {
  // Create the styles directory and ensure it's writable.
  $directory = file_default_scheme() . '://styles';
  $mode = isset($GLOBALS['install_state']['mode']) ? $GLOBALS['install_state']['mode'] : NULL;
  file_prepare_directory($directory, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS, $mode);
}

/**
 * Perform necessary actions before a module is uninstalled.
 *
 * @param string $module
 *   The name of the module about to be uninstalled.
 */
function hook_module_preuninstall($module) {
  mymodule_cache_clear();
}

/**
 * Perform necessary actions after modules are uninstalled.
 *
 * This function differs from hook_uninstall() in that it gives all other
 * modules a chance to perform actions when a module is uninstalled, whereas
 * hook_uninstall() is only called on the module actually being uninstalled.
 *
 * It is recommended that you implement this hook if your module stores
 * data that may have been set by other modules.
 *
 * @param $modules
 *   An array of the modules that were uninstalled.
 *
 * @see hook_uninstall()
 */
function hook_modules_uninstalled($modules) {
  if (in_array('lousy_module', $modules)) {
    \Drupal::state()->delete('mymodule.lousy_module_compatibility');
  }
  mymodule_cache_rebuild();
}

/**
 * Remove any information that the module sets.
 *
 * The information that the module should remove includes:
 * - state that the module has set using \Drupal::state()
 * - modifications to existing tables
 *
 * The module should not remove its entry from the module configuration.
 * Database tables defined by hook_schema() will be removed automatically.
 *
 * The uninstall hook must be implemented in the module's .install file. It
 * will fire when the module gets uninstalled but before the module's database
 * tables are removed, allowing your module to query its own tables during
 * this routine.
 *
 * @see hook_install()
 * @see hook_schema()
 * @see hook_modules_uninstalled()
 */
function hook_uninstall() {
  // Remove the styles directory and generated images.
  file_unmanaged_delete_recursive(file_default_scheme() . '://styles');
}

/**
 * Return an array of tasks to be performed by an installation profile.
 *
 * Any tasks you define here will be run, in order, after the installer has
 * finished the site configuration step but before it has moved on to the
 * final import of languages and the end of the installation. This is invoked
 * by install_tasks(). You can have any number of custom tasks to perform
 * during this phase.
 *
 * Each task you define here corresponds to a callback function which you must
 * separately define and which is called when your task is run. This function
 * will receive the global installation state variable, $install_state, as
 * input, and has the opportunity to access or modify any of its settings. See
 * the install_state_defaults() function in the installer for the list of
 * $install_state settings used by Drupal core.
 *
 * At the end of your task function, you can indicate that you want the
 * installer to pause and display a page to the user by returning any themed
 * output that should be displayed on that page (but see below for tasks that
 * use the form API or batch API; the return values of these task functions are
 * handled differently). You should also use #title within the task
 * callback function to set a custom page title. For some tasks, however, you
 * may want to simply do some processing and pass control to the next task
 * without ending the page request; to indicate this, simply do not send back
 * a return value from your task function at all. This can be used, for
 * example, by installation profiles that need to configure certain site
 * settings in the database without obtaining any input from the user.
 *
 * The task function is treated specially if it defines a form or requires
 * batch processing; in that case, you should return either the form API
 * definition or batch API array, as appropriate. See below for more
 * information on the 'type' key that you must define in the task definition
 * to inform the installer that your task falls into one of those two
 * categories. It is important to use these APIs directly, since the installer
 * may be run non-interactively (for example, via a command line script), all
 * in one page request; in that case, the installer will automatically take
 * care of submitting forms and processing batches correctly for both types of
 * installations. You can inspect the $install_state['interactive'] boolean to
 * see whether or not the current installation is interactive, if you need
 * access to this information.
 *
 * Remember that a user installing Drupal interactively will be able to reload
 * an installation page multiple times, so you should use \Drupal::state() to
 * store any data that you may need later in the installation process. Any
 * temporary state must be removed using \Drupal::state()->delete() before
 * your last task has completed and control is handed back to the installer.
 *
 * @param array $install_state
 *   An array of information about the current installation state.
 *
 * @return array
 *   A keyed array of tasks the profile will perform during the final stage of
 *   the installation. Each key represents the name of a function (usually a
 *   function defined by this profile, although that is not strictly required)
 *   that is called when that task is run. The values are associative arrays
 *   containing the following key-value pairs (all of which are optional):
 *   - display_name: The human-readable name of the task. This will be
 *     displayed to the user while the installer is running, along with a list
 *     of other tasks that are being run. Leave this unset to prevent the task
 *     from appearing in the list.
 *   - display: This is a boolean which can be used to provide finer-grained
 *     control over whether or not the task will display. This is mostly useful
 *     for tasks that are intended to display only under certain conditions;
 *     for these tasks, you can set 'display_name' to the name that you want to
 *     display, but then use this boolean to hide the task only when certain
 *     conditions apply.
 *   - type: A string representing the type of task. This parameter has three
 *     possible values:
 *     - normal: (default) This indicates that the task will be treated as a
 *       regular callback function, which does its processing and optionally
 *       returns HTML output.
 *     - batch: This indicates that the task function will return a batch API
 *       definition suitable for batch_set() or an array of batch definitions
 *       suitable for consecutive batch_set() calls. The installer will then
 *       take care of automatically running the task via batch processing.
 *     - form: This indicates that the task function will return a standard
 *       form API definition (and separately define validation and submit
 *       handlers, as appropriate). The installer will then take care of
 *       automatically directing the user through the form submission process.
 *   - run: A constant representing the manner in which the task will be run.
 *     This parameter has three possible values:
 *     - INSTALL_TASK_RUN_IF_NOT_COMPLETED: (default) This indicates that the
 *       task will run once during the installation of the profile.
 *     - INSTALL_TASK_SKIP: This indicates that the task will not run during
 *       the current installation page request. It can be used to skip running
 *       an installation task when certain conditions are met, even though the
 *       task may still show on the list of installation tasks presented to the
 *       user.
 *     - INSTALL_TASK_RUN_IF_REACHED: This indicates that the task will run on
 *       each installation page request that reaches it. This is rarely
 *       necessary for an installation profile to use; it is primarily used by
 *       the Drupal installer for bootstrap-related tasks.
 *   - function: Normally this does not need to be set, but it can be used to
 *     force the installer to call a different function when the task is run
 *     (rather than the function whose name is given by the array key). This
 *     could be used, for example, to allow the same function to be called by
 *     two different tasks.
 *
 * @see install_state_defaults()
 * @see batch_set()
 * @see hook_install_tasks_alter()
 * @see install_tasks()
 */
function hook_install_tasks(&$install_state) {
  // Here, we define a variable to allow tasks to indicate that a particular,
  // processor-intensive batch process needs to be triggered later on in the
  // installation.
  $myprofile_needs_batch_processing = \Drupal::state()->get('myprofile.needs_batch_processing', FALSE);
  $tasks = array(
    // This is an example of a task that defines a form which the user who is
    // installing the site will be asked to fill out. To implement this task,
    // your profile would define a function named myprofile_data_import_form()
    // as a normal form API callback function, with associated validation and
    // submit handlers. In the submit handler, in addition to saving whatever
    // other data you have collected from the user, you might also call
    // \Drupal::state()->set('myprofile.needs_batch_processing', TRUE) if the
    // user has entered data which requires that batch processing will need to
    // occur later on.
    'myprofile_data_import_form' => array(
      'display_name' => t('Data import options'),
      'type' => 'form',
    ),
    // Similarly, to implement this task, your profile would define a function
    // named myprofile_settings_form() with associated validation and submit
    // handlers. This form might be used to collect and save additional
    // information from the user that your profile needs. There are no extra
    // steps required for your profile to act as an "installation wizard"; you
    // can simply define as many tasks of type 'form' as you wish to execute,
    // and the forms will be presented to the user, one after another.
    'myprofile_settings_form' => array(
      'display_name' => t('Additional options'),
      'type' => 'form',
    ),
    // This is an example of a task that performs batch operations. To
    // implement this task, your profile would define a function named
    // myprofile_batch_processing() which returns a batch API array definition
    // that the installer will use to execute your batch operations. Due to the
    // 'myprofile.needs_batch_processing' variable used here, this task will be
    // hidden and skipped unless your profile set it to TRUE in one of the
    // previous tasks.
    'myprofile_batch_processing' => array(
      'display_name' => t('Import additional data'),
      'display' => $myprofile_needs_batch_processing,
      'type' => 'batch',
      'run' => $myprofile_needs_batch_processing ? INSTALL_TASK_RUN_IF_NOT_COMPLETED : INSTALL_TASK_SKIP,
    ),
    // This is an example of a task that will not be displayed in the list that
    // the user sees. To implement this task, your profile would define a
    // function named myprofile_final_site_setup(), in which additional,
    // automated site setup operations would be performed. Since this is the
    // last task defined by your profile, you should also use this function to
    // call \Drupal::state()->delete('myprofile.needs_batch_processing') and
    // clean up the state that was used above. If you want the user to pass
    // to the final Drupal installation tasks uninterrupted, return no output
    // from this function. Otherwise, return themed output that the user will
    // see (for example, a confirmation page explaining that your profile's
    // tasks are complete, with a link to reload the current page and therefore
    // pass on to the final Drupal installation tasks when the user is ready to
    // do so).
    'myprofile_final_site_setup' => array(
    ),
  );
  return $tasks;
}

/**
 * Alter the full list of installation tasks.
 *
 * You can use this hook to change or replace any part of the Drupal
 * installation process that occurs after the installation profile is selected.
 *
 * This hook is invoked on the install profile in install_tasks().
 *
 * @param $tasks
 *   An array of all available installation tasks, including those provided by
 *   Drupal core. You can modify this array to change or replace individual
 *   steps within the installation process.
 * @param $install_state
 *   An array of information about the current installation state.
 *
 * @see hook_install_tasks()
 * @see install_tasks()
 */
function hook_install_tasks_alter(&$tasks, $install_state) {
  // Replace the entire site configuration form provided by Drupal core
  // with a custom callback function defined by this installation profile.
  $tasks['install_configure_form']['function'] = 'myprofile_install_configure_form';
}

/**
 * Perform a single update between minor versions.
 *
 * hook_update_N() can only be used to update between minor versions of a
 * module. To upgrade between major versions of Drupal (for example, between
 * Drupal 7 and 8), use the @link migrate Migrate API @endlink instead.
 *
 * @section sec_naming Naming and documenting your function
 * For each change in a module that requires one or more actions to be performed
 * when updating a site, add a new implementation of hook_update_N() to your
 * mymodule.install file (assuming mymodule is the machine name of your module).
 * Implementations of hook_update_N() are named (module name)_update_(number).
 * The numbers are normally composed of three parts:
 * - 1 or 2 digits for Drupal core compatibility (Drupal 8, 9, 10, etc.). This
 *   convention must be followed.
 * - 1 digit for your module's major release version; for example, for 8.x-1.*
 *   use 1, for 8.x-2.* use 2, for Core 8.0.x use 0, and for Core 8.1.x use 1.
 *   This convention is optional but suggested for clarity.
 * - 2 digits for sequential counting, starting with 01. Note that the x000
 *   number can never be used: the lowest update number that will be recognized
 *   and run for major version x is x001.
 * Examples:
 * - node_update_8001(): The first update for the Drupal 8.0.x version of the
 *   Drupal Core node module.
 * - mymodule_update_8101(): The first update for your custom or contributed
 *   module's 8.x-1.x versions.
 * - mymodule_update_8201(): The first update for the 8.x-2.x versions.
 *
 * Never renumber update functions. The numeric part of the hook implementation
 * function is stored in the database to keep track of which updates have run,
 * so it is important to maintain this information consistently.
 *
 * The documentation block preceding this function is stripped of newlines and
 * used as the description for the update on the pending updates task list,
 * which users will see when they run the update.php script.
 *
 * @section sec_notes Notes about the function body
 * Writing hook_update_N() functions is tricky. There are several reasons why
 * this is the case:
 * - You do not know when updates will be run: someone could be keeping up with
 *   every update and run them when the database and code are in the same state
 *   as when you wrote your update function, or they could have waited until a
 *   few more updates have come out, and run several at the same time.
 * - You do not know the state of other modules' updates either.
 * - Other modules can use hook_update_dependencies() to run updates between
 *   your module's updates, so you also cannot count on your functions running
 *   right after one another.
 * - You do not know what environment your update will run in (which modules
 *   are installed, whether certain hooks are implemented or not, whether
 *   services are overridden, etc.).
 *
 * Because of these reasons, you'll need to use care in writing your update
 * function. Some things to think about:
 * - Never assume that the database schema is the same when the update will run
 *   as it is when you wrote the update function. So, when updating a database
 *   table or field, put the schema information you want to update to directly
 *   into your function instead of calling your hook_schema() function to
 *   retrieve it (this is one case where the right thing to do is copy and paste
 *   the code).
 * - Never assume that the configuration schema is the same when the update will
 *   run as it is when you wrote the update function. So, when saving
 *   configuration, use the $has_trusted_data = TRUE parameter so that schema is
 *   ignored, and make sure that the configuration data you are saving matches
 *   the configuration schema at the time when you write the update function
 *   (later updates may change it again to match new schema changes).
 * - Never assume your field or entity type definitions are the same when the
 *   update will run as they are when you wrote the update function. Always
 *   retrieve the correct version via
 *   \Drupal::entityDefinitionUpdateManager()::getEntityType() or
 *   \Drupal::entityDefinitionUpdateManager()::getFieldStorageDefinition(). When
 *   adding a new definition always replicate it in the update function body as
 *   you would do with a schema definition.
 * - Never call \Drupal::entityDefinitionUpdateManager()::applyUpdates() in an
 *   update function, as it will apply updates for any module not only yours,
 *   which will lead to unpredictable results.
 * - Be careful about API functions and especially CRUD operations that you use
 *   in your update function. If they invoke hooks or use services, they may
 *   not behave as expected, and it may actually not be appropriate to use the
 *   normal API functions that invoke all the hooks, use the database schema,
 *   and/or use services in an update function -- you may need to switch to
 *   using a more direct method (database query, etc.).
 * - In particular, loading, saving, or performing any other CRUD operation on
 *   an entity is never safe to do (they always involve hooks and services).
 * - Never rebuild the router during an update function.
 *
 * The following actions are examples of things that are safe to do during
 * updates:
 * - Cache invalidation.
 * - Using \Drupal::configFactory()->getEditable() and \Drupal::config(), as
 *   long as you make sure that your update data matches the schema, and you
 *   use the $has_trusted_data argument in the save operation.
 * - Marking a container for rebuild.
 * - Using the API provided by \Drupal::entityDefinitionUpdateManager() to
 *   update the entity schema based on changes in entity type or field
 *   definitions provided by your module.
 *
 * See https://www.drupal.org/node/2535316 for more on writing update functions.
 *
 * @section sec_bulk Batch updates
 * If running your update all at once could possibly cause PHP to time out, use
 * the $sandbox parameter to indicate that the Batch API should be used for your
 * update. In this case, your update function acts as an implementation of
 * callback_batch_operation(), and $sandbox acts as the batch context
 * parameter. In your function, read the state information from the previous
 * run from $sandbox (or initialize), run a chunk of updates, save the state in
 * $sandbox, and set $sandbox['#finished'] to a value between 0 and 1 to
 * indicate the percent completed, or 1 if it is finished (you need to do this
 * explicitly in each pass).
 *
 * See the @link batch Batch operations topic @endlink for more information on
 * how to use the Batch API.
 *
 * @param array $sandbox
 *   Stores information for batch updates. See above for more information.
 *
 * @return string|null
 *   Optionally, update hooks may return a translated string that will be
 *   displayed to the user after the update has completed. If no message is
 *   returned, no message will be presented to the user.
 *
 * @throws \Drupal\Core\Utility\UpdateException|PDOException
 *   In case of error, update hooks should throw an instance of
 *   Drupal\Core\Utility\UpdateException with a meaningful message for the user.
 *   If a database query fails for whatever reason, it will throw a
 *   PDOException.
 *
 * @ingroup update_api
 *
 * @see batch
 * @see schemaapi
 * @see hook_update_last_removed()
 * @see update_get_update_list()
 * @see \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface
 * @see node_update_8001
 * @see system_update_8004
 * @see https://www.drupal.org/node/2535316
 */
function hook_update_N(&$sandbox) {
  // For non-batch updates, the signature can simply be:
  // function hook_update_N() {

  // Example function body for adding a field to a database table, which does
  // not require a batch operation:
  $spec = array(
    'type' => 'varchar',
    'description' => "New Col",
    'length' => 20,
    'not null' => FALSE,
  );
  $schema = Database::getConnection()->schema();
  $schema->addField('mytable1', 'newcol', $spec);

  // Example of what to do if there is an error during your update.
  if ($some_error_condition_met) {
    throw new UpdateException('Something went wrong; here is what you should do.');
  }

  // Example function body for a batch update. In this example, the values in
  // a database field are updated.
  if (!isset($sandbox['progress'])) {
    // This must be the first run. Initialize the sandbox.
    $sandbox['progress'] = 0;
    $sandbox['current_pk'] = 0;
    $sandbox['max'] = Database::getConnection()->query('SELECT COUNT(myprimarykey) FROM {mytable1}')->fetchField() - 1;
  }

  // Update in chunks of 20.
  $records = Database::getConnection()->select('mytable1', 'm')
    ->fields('m', array('myprimarykey', 'otherfield'))
    ->condition('myprimarykey', $sandbox['current_pk'], '>')
    ->range(0, 20)
    ->orderBy('myprimarykey', 'ASC')
    ->execute();
  foreach ($records as $record) {
    // Here, you would make an update something related to this record. In this
    // example, some text is added to the other field.
    Database::getConnection()->update('mytable1')
      ->fields(array('otherfield' => $record->otherfield . '-suffix'))
      ->condition('myprimarykey', $record->myprimarykey)
      ->execute();

    $sandbox['progress']++;
    $sandbox['current_pk'] = $record->myprimarykey;
  }

  $sandbox['#finished'] = empty($sandbox['max']) ? 1 : ($sandbox['progress'] / $sandbox['max']);

  // To display a message to the user when the update is completed, return it.
  // If you do not want to display a completion message, return nothing.
  return t('All foo bars were updated with the new suffix');
}

/**
 * Executes an update which is intended to update data, like entities.
 *
 * These implementations have to be placed in a MODULE.post_update.php file.
 *
 * These updates are executed after all hook_update_N() implementations. At this
 * stage Drupal is already fully repaired so you can use any API as you wish.
 *
 * NAME can be arbitrary machine names. In contrast to hook_update_N() the order
 * of functions in the file is the only thing which ensures the execution order
 * of those functions.
 *
 * Drupal also ensures to not execute the same hook_post_update_NAME() function
 * twice.
 *
 * @param array $sandbox
 *   Stores information for batch updates. See above for more information.
 *
 * @return string|null
 *   Optionally, hook_post_update_NAME() hooks may return a translated string
 *   that will be displayed to the user after the update has completed. If no
 *   message is returned, no message will be presented to the user.
 *
 * @throws \Drupal\Core\Utility\UpdateException|PDOException
 *   In case of error, update hooks should throw an instance of
 *   \Drupal\Core\Utility\UpdateException with a meaningful message for the
 *   user. If a database query fails for whatever reason, it will throw a
 *   PDOException.
 *
 * @ingroup update_api
 *
 * @see hook_update_N()
 */
function hook_post_update_NAME(&$sandbox) {
  // Example of updating some content.
  $node = \Drupal\node\Entity\Node::load(123);
  $node->setTitle('foo');
  $node->save();

  $result = t('Node %nid saved', ['%nid' => $node->id()]);

  // Example of disabling blocks with missing condition contexts. Note: The
  // block itself is in a state which is valid at that point.
  // @see block_update_8001()
  // @see block_post_update_disable_blocks_with_missing_contexts()
  $block_update_8001 = \Drupal::keyValue('update_backup')->get('block_update_8001', []);

  $block_ids = array_keys($block_update_8001);
  $block_storage = \Drupal::entityManager()->getStorage('block');
  $blocks = $block_storage->loadMultiple($block_ids);
  /** @var $blocks \Drupal\block\BlockInterface[] */
  foreach ($blocks as $block) {
    // This block has had conditions removed due to an inability to resolve
    // contexts in block_update_8001() so disable it.

    // Disable currently enabled blocks.
    if ($block_update_8001[$block->id()]['status']) {
      $block->setStatus(FALSE);
      $block->save();
    }
  }

  return $result;
}

/**
 * Return an array of information about module update dependencies.
 *
 * This can be used to indicate update functions from other modules that your
 * module's update functions depend on, or vice versa. It is used by the update
 * system to determine the appropriate order in which updates should be run, as
 * well as to search for missing dependencies.
 *
 * Implementations of this hook should be placed in a mymodule.install file in
 * the same directory as mymodule.module.
 *
 * @return
 *   A multidimensional array containing information about the module update
 *   dependencies. The first two levels of keys represent the module and update
 *   number (respectively) for which information is being returned, and the
 *   value is an array of information about that update's dependencies. Within
 *   this array, each key represents a module, and each value represents the
 *   number of an update function within that module. In the event that your
 *   update function depends on more than one update from a particular module,
 *   you should always list the highest numbered one here (since updates within
 *   a given module always run in numerical order).
 *
 * @ingroup update_api
 *
 * @see update_resolve_dependencies()
 * @see hook_update_N()
 */
function hook_update_dependencies() {
  // Indicate that the mymodule_update_8001() function provided by this module
  // must run after the another_module_update_8003() function provided by the
  // 'another_module' module.
  $dependencies['mymodule'][8001] = array(
    'another_module' => 8003,
  );
  // Indicate that the mymodule_update_8002() function provided by this module
  // must run before the yet_another_module_update_8005() function provided by
  // the 'yet_another_module' module. (Note that declaring dependencies in this
  // direction should be done only in rare situations, since it can lead to the
  // following problem: If a site has already run the yet_another_module
  // module's database updates before it updates its codebase to pick up the
  // newest mymodule code, then the dependency declared here will be ignored.)
  $dependencies['yet_another_module'][8005] = array(
    'mymodule' => 8002,
  );
  return $dependencies;
}

/**
 * Return a number which is no longer available as hook_update_N().
 *
 * If you remove some update functions from your mymodule.install file, you
 * should notify Drupal of those missing functions. This way, Drupal can
 * ensure that no update is accidentally skipped.
 *
 * Implementations of this hook should be placed in a mymodule.install file in
 * the same directory as mymodule.module.
 *
 * @return
 *   An integer, corresponding to hook_update_N() which has been removed from
 *   mymodule.install.
 *
 * @ingroup update_api
 *
 * @see hook_update_N()
 */
function hook_update_last_removed() {
  // We've removed the 8.x-1.x version of mymodule, including database updates.
  // The next update function is mymodule_update_8200().
  return 8103;
}

/**
 * Provide information on Updaters (classes that can update Drupal).
 *
 * Drupal\Core\Updater\Updater is a class that knows how to update various parts
 * of the Drupal file system, for example to update modules that have newer
 * releases, or to install a new theme.
 *
 * @return
 *   An associative array of information about the updater(s) being provided.
 *   This array is keyed by a unique identifier for each updater, and the
 *   values are subarrays that can contain the following keys:
 *   - class: The name of the PHP class which implements this updater.
 *   - name: Human-readable name of this updater.
 *   - weight: Controls what order the Updater classes are consulted to decide
 *     which one should handle a given task. When an update task is being run,
 *     the system will loop through all the Updater classes defined in this
 *     registry in weight order and let each class respond to the task and
 *     decide if each Updater wants to handle the task. In general, this
 *     doesn't matter, but if you need to override an existing Updater, make
 *     sure your Updater has a lighter weight so that it comes first.
 *
 * @ingroup update_api
 *
 * @see drupal_get_updaters()
 * @see hook_updater_info_alter()
 */
function hook_updater_info() {
  return array(
    'module' => array(
      'class' => 'Drupal\Core\Updater\Module',
      'name' => t('Update modules'),
      'weight' => 0,
    ),
    'theme' => array(
      'class' => 'Drupal\Core\Updater\Theme',
      'name' => t('Update themes'),
      'weight' => 0,
    ),
  );
}

/**
 * Alter the Updater information array.
 *
 * An Updater is a class that knows how to update various parts of the Drupal
 * file system, for example to update modules that have newer releases, or to
 * install a new theme.
 *
 * @param array $updaters
 *   Associative array of updaters as defined through hook_updater_info().
 *   Alter this array directly.
 *
 * @ingroup update_api
 *
 * @see drupal_get_updaters()
 * @see hook_updater_info()
 */
function hook_updater_info_alter(&$updaters) {
  // Adjust weight so that the theme Updater gets a chance to handle a given
  // update task before module updaters.
  $updaters['theme']['weight'] = -1;
}

/**
 * Check installation requirements and do status reporting.
 *
 * This hook has three closely related uses, determined by the $phase argument:
 * - Checking installation requirements ($phase == 'install').
 * - Checking update requirements ($phase == 'update').
 * - Status reporting ($phase == 'runtime').
 *
 * Note that this hook, like all others dealing with installation and updates,
 * must reside in a module_name.install file, or it will not properly abort
 * the installation of the module if a critical requirement is missing.
 *
 * During the 'install' phase, modules can for example assert that
 * library or server versions are available or sufficient.
 * Note that the installation of a module can happen during installation of
 * Drupal itself (by install.php) with an installation profile or later by hand.
 * As a consequence, install-time requirements must be checked without access
 * to the full Drupal API, because it is not available during install.php.
 * If a requirement has a severity of REQUIREMENT_ERROR, install.php will abort
 * or at least the module will not install.
 * Other severity levels have no effect on the installation.
 * Module dependencies do not belong to these installation requirements,
 * but should be defined in the module's .info.yml file.
 *
 * During installation (when $phase == 'install'), if you need to load a class
 * from your module, you'll need to include the class file directly.
 *
 * The 'runtime' phase is not limited to pure installation requirements
 * but can also be used for more general status information like maintenance
 * tasks and security issues.
 * The returned 'requirements' will be listed on the status report in the
 * administration section, with indication of the severity level.
 * Moreover, any requirement with a severity of REQUIREMENT_ERROR severity will
 * result in a notice on the administration configuration page.
 *
 * @param $phase
 *   The phase in which requirements are checked:
 *   - install: The module is being installed.
 *   - update: The module is enabled and update.php is run.
 *   - runtime: The runtime requirements are being checked and shown on the
 *     status report page.
 *
 * @return
 *   An associative array where the keys are arbitrary but must be unique (it
 *   is suggested to use the module short name as a prefix) and the values are
 *   themselves associative arrays with the following elements:
 *   - title: The name of the requirement.
 *   - value: The current value (e.g., version, time, level, etc). During
 *     install phase, this should only be used for version numbers, do not set
 *     it if not applicable.
 *   - description: The description of the requirement/status.
 *   - severity: The requirement's result/severity level, one of:
 *     - REQUIREMENT_INFO: For info only.
 *     - REQUIREMENT_OK: The requirement is satisfied.
 *     - REQUIREMENT_WARNING: The requirement failed with a warning.
 *     - REQUIREMENT_ERROR: The requirement failed with an error.
 */
function hook_requirements($phase) {
  $requirements = array();

  // Report Drupal version
  if ($phase == 'runtime') {
    $requirements['drupal'] = array(
      'title' => t('Drupal'),
      'value' => \Drupal::VERSION,
      'severity' => REQUIREMENT_INFO
    );
  }

  // Test PHP version
  $requirements['php'] = array(
    'title' => t('PHP'),
    'value' => ($phase == 'runtime') ? \Drupal::l(phpversion(), new Url('system.php')) : phpversion(),
  );
  if (version_compare(phpversion(), DRUPAL_MINIMUM_PHP) < 0) {
    $requirements['php']['description'] = t('Your PHP installation is too old. Drupal requires at least PHP %version.', array('%version' => DRUPAL_MINIMUM_PHP));
    $requirements['php']['severity'] = REQUIREMENT_ERROR;
  }

  // Report cron status
  if ($phase == 'runtime') {
    $cron_last = \Drupal::state()->get('system.cron_last');

    if (is_numeric($cron_last)) {
      $requirements['cron']['value'] = t('Last run @time ago', array('@time' => \Drupal::service('date.formatter')->formatTimeDiffSince($cron_last)));
    }
    else {
      $requirements['cron'] = array(
        'description' => t('Cron has not run. It appears cron jobs have not been setup on your system. Check the help pages for <a href=":url">configuring cron jobs</a>.', array(':url' => 'https://www.drupal.org/cron')),
        'severity' => REQUIREMENT_ERROR,
        'value' => t('Never run'),
      );
    }

    $requirements['cron']['description'] .= ' ' . t('You can <a href=":cron">run cron manually</a>.', array(':cron' => \Drupal::url('system.run_cron')));

    $requirements['cron']['title'] = t('Cron maintenance tasks');
  }

  return $requirements;
}

/**
 * @} End of "addtogroup hooks".
 */
