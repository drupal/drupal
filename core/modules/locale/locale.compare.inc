<?php

/**
 * @file
 */

use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Utility\ProjectInfo;
use Drupal\locale\LocaleDefaultOptions;
use Drupal\locale\LocaleFetch;
use Drupal\locale\LocaleProjectChecker;
use Drupal\locale\LocaleProjectRepository;

/**
 * Load common APIs.
 */

/**
 * Clear the project data table.
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use
 *   \Drupal::service(LocaleProjectRepository::class)->deleteAll()
 *   instead.
 *
 * @see https://www.drupal.org/node/3037033
 */
function locale_translation_flush_projects(): void {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use \Drupal::service(LocaleProjectRepository::class)->deleteAll() instead. See https://www.drupal.org/node/3037033', E_USER_DEPRECATED);
  \Drupal::service(LocaleProjectRepository::class)->deleteAll();
}

/**
 * Builds list of projects and stores the result in the database.
 *
 * The project data is based on the project list supplied by the Update Status
 * module. Only the properties required by Locale module is included and
 * additional (custom) modules and translation server data is added.
 *
 * In case the Update Status module is disabled this function will return an
 * empty array.
 *
 * @return array
 *   Array of project data:
 *   - "name": Project system name.
 *   - "project_type": Project type, e.g. 'module', 'theme'.
 *   - "core": Core release version, e.g. 8.x
 *   - "version": Project release version, e.g. 8.x-1.0
 *   - "server_pattern": Translation server po file pattern.
 *   - "status": Project status, 1 = enabled.
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use
 *   \Drupal::service(LocaleProjectRepository::class)->buildProjects()
 *   instead.
 *
 * @see https://www.drupal.org/node/3037033
 */
function locale_translation_build_projects() {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use \Drupal::service(LocaleProjectRepository::class)->buildProjects() instead. See https://www.drupal.org/node/3037033', E_USER_DEPRECATED);
  return \Drupal::service(LocaleProjectRepository::class)->buildProjects();
}

/**
 * Fetch an array of projects for translation update.
 *
 * @return array
 *   Array of project data including .info.yml file data.
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. There
 *   is no replacement.
 *
 * @see https://www.drupal.org/node/3037033
 */
function locale_translation_project_list() {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. There is no replacement. See https://www.drupal.org/node/3037033', E_USER_DEPRECATED);
  $projects = &drupal_static(__FUNCTION__, []);
  if (empty($projects)) {
    $projects = [];

    $additional_allow_list = [
      'interface translation project',
      'interface translation server pattern',
    ];
    $module_data = _locale_translation_prepare_project_list(\Drupal::service('extension.list.module')->getList(), 'module');
    $theme_data = _locale_translation_prepare_project_list(\Drupal::service('extension.list.theme')->reset()->getList(), 'theme');
    $project_info = new ProjectInfo();
    $project_info->processInfoList($projects, $module_data, 'module', TRUE, $additional_allow_list);
    $project_info->processInfoList($projects, $theme_data, 'theme', TRUE, $additional_allow_list);

    // Allow other modules to alter projects before fetching and comparing.
    \Drupal::moduleHandler()->alter('locale_translation_projects', $projects);
  }
  return $projects;
}

/**
 * Prepare module and theme data.
 *
 * Modify .info.yml file data before it is processed by
 * \Drupal\Core\Utility\ProjectInfo->processInfoList(). In order for
 * \Drupal\Core\Utility\ProjectInfo->processInfoList() to recognize a project,
 * it requires the 'project' parameter in the .info.yml file data.
 *
 * Custom modules or themes can bring their own gettext translation file. To
 * enable import of this file the module or theme defines "interface translation
 * project = my_project" in its .info.yml file. This function will add a project
 * "my_project" to the info data.
 *
 * @param \Drupal\Core\Extension\Extension[] $data
 *   Array of .info.yml file data.
 * @param string $type
 *   The project type. i.e. module, theme.
 *
 * @return array
 *   Array of .info.yml file data.
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. There
 *   is no replacement.
 *
 * @see https://www.drupal.org/node/3037033
 */
function _locale_translation_prepare_project_list($data, $type) {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. There is no replacement. See https://www.drupal.org/node/3037033', E_USER_DEPRECATED);
  foreach ($data as $name => $file) {
    // Include interface translation projects. To allow
    // \Drupal\Core\Utility\ProjectInfo->processInfoList() to identify this as
    // a project the 'project' property is filled with the
    // 'interface translation project' value.
    if (isset($file->info['interface translation project'])) {
      $data[$name]->info['project'] = $file->info['interface translation project'];
    }
  }
  return $data;
}

/**
 * Retrieve data for default server.
 *
 * @return array
 *   Array of server parameters:
 *   - "pattern": URI containing po file pattern.
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. There
 *   is not replacement.
 *
 * @see https://www.drupal.org/node/3037033
 */
function locale_translation_default_translation_server() {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. There is no replacement. See https://www.drupal.org/node/3037033', E_USER_DEPRECATED);
  $pattern = \Drupal::config('locale.settings')->get('translation.default_server_pattern');
  // An additional check is required here. During the upgrade process
  // \Drupal::config()->get() returns NULL. We use the defined value as
  // fallback.
  return [
    'pattern' => $pattern ?: \Drupal::TRANSLATION_DEFAULT_SERVER_PATTERN,
  ];
}

/**
 * Check for the latest release of project translations.
 *
 * @param array $projects
 *   Array of project names to check. Defaults to all translatable projects.
 * @param array $langcodes
 *   Array of language codes. Defaults to all translatable languages.
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use
 *   \Drupal::service(LocaleProjectChecker::class)->checkProjects()
 *   instead.
 *
 * @see https://www.drupal.org/node/3037033
 */
function locale_translation_check_projects($projects = [], $langcodes = []): void {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use \Drupal::service(LocaleProjectChecker::class)->checkProjects() instead. See https://www.drupal.org/node/3037033', E_USER_DEPRECATED);
  if (empty($projects)) {
    $projects = array_keys(\Drupal::service(LocaleProjectRepository::class)->getAll());
  }
  \Drupal::service(LocaleProjectChecker::class)->checkProjects($projects, $langcodes);
}

/**
 * Gets and stores the status and timestamp of remote po files.
 *
 * A batch process is used to check for po files at remote locations and (when
 * configured) to check for po files in the local file system. The most recent
 * translation source states are stored in the state variable
 * 'locale.translation_status'.
 *
 * @param array $projects
 *   Array of project names to check. Defaults to all translatable projects.
 * @param array $langcodes
 *   Array of language codes. Defaults to all translatable languages.
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use
 *   \Drupal::service(LocaleProjectChecker::class)->triggerBatch()
 *   instead.
 *
 * @see https://www.drupal.org/node/3589759
 */
function locale_translation_check_projects_batch($projects = [], $langcodes = []): void {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use \Drupal::service(LocaleProjectChecker::class)->triggerBatch() instead. See https://www.drupal.org/node/3589759', E_USER_DEPRECATED);
  \Drupal::service(LocaleProjectChecker::class)->triggerBatch($projects, $langcodes);
}

/**
 * Builds a batch to get the status of remote and local translation files.
 *
 * The batch process fetches the state of both local and (if configured) remote
 * translation files. The data of the most recent translation is stored per
 * per project and per language. This data is stored in a state variable
 * 'locale.translation_status'. The timestamp it was last updated is stored
 * in the state variable 'locale.translation_last_checked'.
 *
 * @param array $projects
 *   Array of project names for which to check the state of translation files.
 *   Defaults to all translatable projects.
 * @param array $langcodes
 *   Array of language codes. Defaults to all translatable languages.
 *
 * @return array
 *   Batch definition array.
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use
 *   \Drupal::service(LocaleProjectChecker::class)->triggerBatch()
 *   instead, this now calls batch_set immediately instead of returning the
 *   array.
 *
 * @see https://www.drupal.org/node/3589759
 */
function locale_translation_batch_status_build($projects = [], $langcodes = []) {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use \Drupal::service(LocaleProjectChecker::class)->triggerBatch() instead, this now calls batch_set immediately instead of returning the array. See https://www.drupal.org/node/3589759', E_USER_DEPRECATED);
  $projects = $projects ?: array_keys(\Drupal::service(LocaleProjectRepository::class)->getAll());
  $langcodes = $langcodes ?: array_keys(locale_translatable_language_list());
  $options = LocaleDefaultOptions::updateOptions();

  $operations = \Drupal::service(LocaleFetch::class)->getStatusOperations($projects, $langcodes, $options);

  $batch_builder = (new BatchBuilder())
    ->setTitle(t('Checking translations'))
    ->setErrorMessage(t('Error checking translation updates.'))
    ->setFinishCallback(LocaleProjectChecker::class . ':batchFinished');

  array_walk($operations, function ($operation) use ($batch_builder) {
    call_user_func_array([$batch_builder, 'addOperation'], $operation);
  });

  return $batch_builder->toArray();
}

/**
 * Constructs batch operations for checking remote translation status.
 *
 * @param array $projects
 *   Array of project names to be processed.
 * @param array $langcodes
 *   Array of language codes.
 * @param array $options
 *   Batch processing options.
 *
 * @return array
 *   Array of batch operations.
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use
 *   \Drupal::service(LocaleFetch::class)->getStatusOperations()
 *   instead.
 *
 * @see https://www.drupal.org/node/3589759
 */
function _locale_translation_batch_status_operations($projects, $langcodes, $options = []): array {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use \Drupal::service(LocaleFetch::class)->getStatusOperations(). See https://www.drupal.org/node/3589759', E_USER_DEPRECATED);
  return \Drupal::service(LocaleFetch::class)->getStatusOperations($projects, $langcodes, $options);
}

/**
 * Check and store the status and timestamp of local po files.
 *
 * Only po files in the local file system are checked. Any remote translation
 * files will be ignored.
 *
 * Projects may contain a server_pattern option containing a pattern of the
 * path to the po source files. If no server_pattern is defined the default
 * translation directory is checked for the po file. When a server_pattern is
 * defined the specified location is checked. The server_pattern can be set in
 * the module's .info.yml file or by using
 * hook_locale_translation_projects_alter().
 *
 * @param array $projects
 *   Array of project names for which to check the state of translation files.
 *   Defaults to all translatable projects.
 * @param array $langcodes
 *   Array of language codes. Defaults to all translatable languages.
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use
 *   \Drupal::service(LocaleProjectChecker::class)->checkLocalProjects()
 *   instead.
 *
 * @see https://www.drupal.org/node/3037033
 */
function locale_translation_check_projects_local($projects = [], $langcodes = []): void {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use \Drupal::service(LocaleProjectChecker::class)->checkLocalProjects() instead. See https://www.drupal.org/node/3037033', E_USER_DEPRECATED);
  if (empty($projects)) {
    $projects = array_keys(\Drupal::service(LocaleProjectRepository::class)->getAll());
  }
  \Drupal::service(LocaleProjectChecker::class)->checkLocalProjects($projects, $langcodes);
}
