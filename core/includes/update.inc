<?php

/**
 * @file
 * Drupal database update API.
 *
 * This file contains functions to perform database updates for a Drupal
 * installation. It is included and used extensively by update.php.
 */

use Drupal\Component\Graph\Graph;
use Drupal\Component\Utility\Settings;
use Drupal\Component\Utility\String;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\ConfigException;
use Drupal\Core\DrupalKernel;
use Drupal\Component\Uuid\Uuid;
use Drupal\Component\Utility\NestedArray;
use Symfony\Component\HttpFoundation\Request;

/**
 * Minimum schema version of Drupal 7 required for upgrade to Drupal 8.
 *
 * Upgrades from Drupal 7 to Drupal 8 require that Drupal 7 be running
 * the most recent version, or the upgrade could fail. We can't easily
 * check the Drupal 7 version once the update process has begun, so instead
 * we check the schema version of system.module.
 */
const REQUIRED_D7_SCHEMA_VERSION = '7069';

/**
 * Disables any extensions that are incompatible with the current core version.
 */
function update_fix_compatibility() {
  foreach (array('module', 'theme') as $type) {
    $config = \Drupal::config("system.$type");
    $save = FALSE;
    foreach ($config->get('enabled') as $name => $weight) {
      if (update_check_incompatibility($name, $type)) {
        $config->clear("enabled.$name");
        $save = TRUE;
      }
    }
    if ($save) {
      if ($type == 'module') {
        $config->set('enabled', module_config_sort($config->get('enabled')));
      }
      $config->save();
    }
  }
}

/**
 * Tests the compatibility of a module or theme.
 */
function update_check_incompatibility($name, $type = 'module') {
  static $themes, $modules;

  // Store values of expensive functions for future use.
  if (empty($themes) || empty($modules)) {
    // We need to do a full rebuild here to make sure the database reflects any
    // code changes that were made in the filesystem before the update script
    // was initiated.
    $themes = system_rebuild_theme_data();
    $modules = system_rebuild_module_data();
  }

  if ($type == 'module' && isset($modules[$name])) {
    $file = $modules[$name];
  }
  elseif ($type == 'theme' && isset($themes[$name])) {
    $file = $themes[$name];
  }
  if (!isset($file)
      || !isset($file->info['core'])
      || $file->info['core'] != \Drupal::CORE_COMPATIBILITY
      || version_compare(phpversion(), $file->info['php']) < 0) {
    return TRUE;
  }
  return FALSE;
}

/**
 * Performs extra steps required to bootstrap when using a Drupal 7 database.
 *
 * Users who still have a Drupal 7 database (and are in the process of
 * updating to Drupal 8) need extra help before a full bootstrap can be
 * achieved. This function does the necessary preliminary work that allows
 * the bootstrap to be successful.
 *
 * No access check has been performed when this function is called, so no
 * irreversible changes to the database are made here.
 */
function update_prepare_d8_bootstrap() {
  include_once __DIR__ . '/install.inc';
  include_once __DIR__ . '/schema.inc';
  // Bootstrap to configuration.
  drupal_bootstrap(DRUPAL_BOOTSTRAP_CONFIGURATION);

  // During the bootstrap to DRUPAL_BOOTSTRAP_PAGE_CACHE, code will try to read
  // the cache but the cache tables are not compatible yet. Use the Null backend
  // by default to avoid exceptions.
  $settings = settings()->getAll();
  $settings['cache']['default'] = 'cache.backend.memory';
  new Settings($settings);

  // Enable UpdateServiceProvider service overrides.
  // @see update_flush_all_caches()
  $GLOBALS['conf']['container_service_providers']['UpdateServiceProvider'] = 'Drupal\Core\DependencyInjection\UpdateServiceProvider';

  // Check whether settings.php needs to be rewritten.
  $settings_exist = !empty($GLOBALS['config_directories']);

  if (!$settings_exist || !is_dir(config_get_config_directory('active')) || !is_dir(config_get_config_directory('staging'))) {
    drupal_install_config_directories();
  }

  // Bootstrap the kernel.
  // Do not attempt to dump and write it.
  $kernel = new DrupalKernel('update', drupal_classloader(), FALSE);
  $kernel->boot();

  // If any of the required settings needs to be written, then settings.php
  // needs to be writable.
  if (!$settings_exist) {
    $settings_file = conf_path() . '/settings.php';
    $writable = drupal_verify_install_file($settings_file, FILE_EXIST | FILE_READABLE | FILE_WRITABLE);
    $requirements['settings file']['title'] = 'Settings file';
    if ($writable) {
      $requirements['settings file'] += array(
        'value' => 'settings.php is writable.',
      );
    }
    else {
      $requirements['settings file'] += array(
        'value' => 'settings.php is not writable.',
        'severity' => REQUIREMENT_ERROR,
        'description' => 'Drupal requires write permissions to <em>' . $settings_file . '</em> during the update process. If you are unsure how to grant file permissions, consult the <a href="http://drupal.org/server-permissions">online handbook</a>.',
      );
    }
    update_extra_requirements($requirements);
  }

  // Bootstrap the database.
  require_once __DIR__ . '/database.inc';

  // module.inc is not yet loaded but there are calls to module_config_sort()
  // below.
  require_once __DIR__ . '/module.inc';

  // If the site has not updated to Drupal 8 yet, check to make sure that it is
  // running an up-to-date version of Drupal 7 before proceeding. Note this has
  // to happen AFTER the database bootstraps because of
  // drupal_get_installed_schema_version().
  try {
    $system_schema = drupal_get_installed_schema_version('system');
  }
  catch (\Exception $e) {
    $system_schema = db_query('SELECT schema_version FROM {system} WHERE name = :system', array(':system' => 'system'))->fetchField();
  }
  if ($system_schema < 8000) {
    $has_required_schema = $system_schema >= REQUIRED_D7_SCHEMA_VERSION;
    $requirements = array(
      'drupal 7 version' => array(
        'title' => 'Drupal 7 version',
        'value' => $has_required_schema ? 'You are running a current version of Drupal 7.' : 'You are not running a current version of Drupal 7',
        'severity' => $has_required_schema ? NULL : REQUIREMENT_ERROR,
        'description' => $has_required_schema ? '' : 'Please update your Drupal 7 installation to the most recent version before attempting to upgrade to Drupal 8',
      ),
    );
    update_extra_requirements($requirements);

    // @todo update.php stages seem to be completely screwed up; the initial
    //   requirements check is not supposed to change the system. All of the
    //   following code seems to have been mistakenly/unknowingly added here and
    //   does not belong into update_prepare_d8_bootstrap().
    if ($has_required_schema) {
      if (!db_table_exists('key_value')) {
        $specs = array(
          'description' => 'Generic key-value storage table. See the state system for an example.',
          'fields' => array(
            'collection' => array(
              'description' => 'A named collection of key and value pairs.',
              'type' => 'varchar',
              'length' => 128,
              'not null' => TRUE,
              'default' => '',
            ),
            'name' => array(
              'description' => 'The key of the key-value pair. As KEY is a SQL reserved keyword, name was chosen instead.',
              'type' => 'varchar',
              'length' => 128,
              'not null' => TRUE,
              'default' => '',
            ),
            'value' => array(
              'description' => 'The value.',
              'type' => 'blob',
              'not null' => TRUE,
              'size' => 'big',
              'translatable' => TRUE,
            ),
          ),
          'primary key' => array('collection', 'name'),
        );
        db_create_table('key_value', $specs);
      }
      if (!db_table_exists('cache_tags')) {
        $table = array(
          'description' => 'Cache table for tracking cache tags related to the cache bin.',
          'fields' => array(
            'tag' => array(
              'description' => 'Namespace-prefixed tag string.',
              'type' => 'varchar',
              'length' => 255,
              'not null' => TRUE,
              'default' => '',
            ),
            'invalidations' => array(
              'description' => 'Number incremented when the tag is invalidated.',
              'type' => 'int',
              'not null' => TRUE,
              'default' => 0,
            ),
            'deletions' => array(
              'description' => 'Number incremented when the tag is deleted.',
              'type' => 'int',
              'not null' => TRUE,
              'default' => 0,
            ),
          ),
          'primary key' => array('tag'),
        );
        db_create_table('cache_tags', $table);
      }
      if (!db_table_exists('cache_config')) {
        $spec = array(
          'description' =>  'Cache table for configuration data.',
          'fields' => array(
            'cid' => array(
              'description' => 'Primary Key: Unique cache ID.',
              'type' => 'varchar',
              'length' => 255,
              'not null' => TRUE,
              'default' => '',
            ),
            'data' => array(
              'description' => 'A collection of data to cache.',
              'type' => 'blob',
              'not null' => FALSE,
              'size' => 'big',
            ),
            'expire' => array(
              'description' => 'A Unix timestamp indicating when the cache entry should expire, or 0 for never.',
              'type' => 'int',
              'not null' => TRUE,
              'default' => 0,
            ),
            'created' => array(
              'description' => 'A Unix timestamp indicating when the cache entry was created.',
              'type' => 'int',
              'not null' => TRUE,
              'default' => 0,
            ),
            'serialized' => array(
              'description' => 'A flag to indicate whether content is serialized (1) or not (0).',
              'type' => 'int',
              'size' => 'small',
              'not null' => TRUE,
              'default' => 0,
            ),
            'tags' => array(
              'description' => 'Space-separated list of cache tags for this entry.',
              'type' => 'text',
              'size' => 'big',
              'not null' => FALSE,
            ),
            'checksum_invalidations' => array(
              'description' => 'The tag invalidation sum when this entry was saved.',
              'type' => 'int',
              'not null' => TRUE,
              'default' => 0,
            ),
            'checksum_deletions' => array(
              'description' => 'The tag deletion sum when this entry was saved.',
              'type' => 'int',
              'not null' => TRUE,
              'default' => 0,
            ),
          ),
          'indexes' => array(
            'expire' => array('expire'),
          ),
          'primary key' => array('cid'),
        );
        db_create_table('cache_config', $spec);
      }

      require_once DRUPAL_ROOT . '/core/modules/system/system.install';
      $tables = array(
        'cache',
        'cache_bootstrap',
        'cache_block',
        'cache_field',
        'cache_filter',
        'cache_form',
        'cache_image',
        'cache_menu',
        'cache_page',
        'cache_path',
        'cache_update',
      );

      foreach ($tables as $table) {
        update_add_cache_columns($table);
      }

      // Bootstrap variables so we can update theme while preparing the update
      // process.
      drupal_bootstrap(DRUPAL_BOOTSTRAP_VARIABLES);

      // Update the 'language_default' system variable, if configured.
      // Required to run before drupal_install_config_directories(), since that
      // triggers a call into system_stream_wrappers(), which calls t(), which
      // calls into language_default().
      $language_default = update_variable_get('language_default');
      if (!empty($language_default) && (isset($language_default->id) || isset($language_default->language))) {
        if (!isset($language_default->id)) {
          $language_default->id = $language_default->language;
        }
        unset($language_default->language);
        // In D8, the 'language_default' is not anymore an object, but an array,
        // so make sure that the new value that is saved into this variable is an
        // array.
        update_variable_set('language_default', (array) $language_default);
      }

      $module_config = \Drupal::config('system.module');
      $theme_config = \Drupal::config('system.theme');
      $disabled_themes = \Drupal::config('system.theme.disabled');
      $schema_store = \Drupal::keyValue('system.schema');

      // Load system.module, because update_prepare_d8_bootstrap() is called in
      // the initial minimal update.php bootstrap that performs the core
      // requirements check.
      require_once DRUPAL_ROOT . '/core/modules/system/system.module';

      // Make sure that the bootstrap cache is cleared as that might contain
      // incompatible data structures.
      cache('bootstrap')->deleteAll();

      // Retrieve all installed extensions from the {system} table.
      // Uninstalled extensions are ignored and not converted.
      $result = db_query('SELECT name, status, weight, schema_version, type FROM {system} WHERE type = :theme OR (type = :module AND schema_version <> :schema_uninstalled)', array(
        ':theme' => 'theme',
        ':module' => 'module',
        ':schema_uninstalled' => SCHEMA_UNINSTALLED,
      ));

      $module_data = _system_rebuild_module_data();

      // Migrate each extension into configuration, varying by the extension's
      // status, and record its schema version.
      foreach ($result as $record) {
        // Before migrating any extension into configuration, make sure the
        // extensions name length is not higher than the limit.
        if (drupal_strlen($record->name) > 50) {
          $requirements['module name too long ' . $record->name] = array(
            'title' => 'Module name too long',
            'value' => format_string('@name is @count characters long.', array('@name' => $record->name, '@count' => drupal_strlen($record->name))),
            'description' => 'Module names longer than 50 characters are <a href="https://drupal.org/node/2014073">no longer supported</a>.',
            'severity' => REQUIREMENT_ERROR,
          );
          update_extra_requirements($requirements);
        }

        if ($record->type == 'module') {
          if ($record->status && isset($module_data[$record->name])) {
            $module_config->set('enabled.' . $record->name, $record->weight);
          }
        }
        elseif ($record->type == 'theme') {
          if ($record->status) {
            $theme_config->set('enabled.' . $record->name, 0);
          }
          else {
            $disabled_themes->set($record->name, 0);
          }
        }
        $schema_store->set($record->name, $record->schema_version);
      }
      $sorted_modules = module_config_sort($module_config->get('enabled'));
      $module_config->set('enabled', $sorted_modules)->save();
      $sorted_with_filenames = array();
      foreach (array_keys($sorted_modules) as $m) {
        $sorted_with_filenames[$m] = drupal_get_filename('module', $m);
      }
      \Drupal::moduleHandler()->setModuleList($sorted_with_filenames);
      $theme_config->save();
      $disabled_themes->save();

      // Migrate the private key to state. This is used to create the token for
      // the upgrade batch so needs to be be done before the upgrade has begun.
      update_variables_to_state(array(
        'drupal_private_key' => 'system.private_key',
      ));

      // Update the dynamic include paths that might be used before running the
      // proper update functions.
      update_prepare_stored_includes();
      // Update the environment for the language bootstrap if needed.
      update_prepare_d8_language();
      // Rebuild kernel after new language fields are added in the database
      // because the translation service depends on them being there.
      \Drupal::service('kernel')->updateModules($sorted_with_filenames, $sorted_with_filenames);

      // Change language column to langcode in url_alias.
      if (db_table_exists('url_alias') && db_field_exists('url_alias', 'language')) {
        db_drop_index('url_alias', 'alias_language_pid');
        db_drop_index('url_alias', 'source_language_pid');
        $langcode_spec = array(
          'description' => "The language code this alias is for; if 'und', the alias will be used for unknown languages. Each Drupal path can have an alias for each supported language.",
          'type' => 'varchar',
          'length' => 12,
          'not null' => TRUE,
          'default' => '',
        );
        $langcode_indexes = array('indexes' =>
          array(
            'alias_langcode_pid' => array('alias', 'langcode', 'pid'),
            'source_langcode_pid' => array('source', 'langcode', 'pid'),
          ),
        );
        db_change_field('url_alias', 'language', 'langcode', $langcode_spec, $langcode_indexes);
      }
    }
  }
  // Moves install_profile from variable to settings. You can't do that in
  // system.install because _system_rebuild_module_data() needs the profile
  // directly. Check that it has not been set already. This is the case for
  // Simpletest upgrade path tests.
  if (!settings()->get('install_profile')) {
    $old_variable = unserialize(\Drupal::database()->query('SELECT value FROM {variable} WHERE name = :name', array(':name' => 'install_profile'))->fetchField());
    $settings = array(
      'settings' => array(
        'install_profile' => (object) array(
            'value' => $old_variable,
            'required' => TRUE,
        ),
      )
    );
    drupal_rewrite_settings($settings);
  }

  // Now remove the cache override.
  $settings = settings()->getAll();
  unset($settings['cache']['default']);
  new Settings($settings);
  $kernel = new DrupalKernel('update', drupal_classloader(), FALSE);
  $kernel->boot();

  // Clear the D7 caches, to ensure that for example the theme_registry does not
  // take part in the upgrade process.
  Drupal::cache('cache')->deleteAll();
}

/**
 * Fixes stored include paths to match the "/core" migration.
 */
function update_prepare_stored_includes() {
  // Retrieve the currently stored language types. Default to the hardcoded D7
  // values.
  $default_language_types = array('language' => TRUE, 'language_content' => FALSE, 'language_url' => FALSE);
  $language_types = array_keys(update_variable_get('language_types', $default_language_types));

  // Update language negotiation settings.
  foreach ($language_types as $language_type) {
    $negotiation = update_variable_get("language_negotiation_$language_type", array());
    foreach ($negotiation as &$method) {
      if (isset($method['file']) && $method['file'] == 'includes/locale.inc') {
        $method['file'] = 'core/modules/language/language.negotiation.inc';
      }
    }
    update_variable_set("language_negotiation_$language_type", $negotiation);
  }
}

/**
 * Prepares Drupal 8 language changes for the bootstrap if needed.
 */
function update_prepare_d8_language() {
  if (db_table_exists('languages')) {
    \Drupal::moduleHandler()->install(array('language'));

    $languages = db_select('languages', 'l')
      ->fields('l')
      ->execute();
    $plurals = array();
    $javascript = array();
    $prefixes = array();
    $domains = array();
    foreach ($languages as $language) {
      $plurals[$language->language] = array(
        'plurals' => $language->plurals,
        'formula' => $language->formula,
      );
      $javascript[$language->language] = $language->javascript;
      $prefixes[$language->language] = $language->prefix;
      $domains[$language->language] = $language->domain;
    }
    \Drupal::state()->set('locale.translation.plurals', $plurals);
    \Drupal::state()->set('locale.translation.javascript', $javascript);
    \Drupal::config('language.negotiation')
      ->set('url.prefixes', $prefixes)
      ->set('url.domains', $domains)
      ->save();

    // Drop now unneeded columns.
    db_drop_field('languages', 'plurals');
    db_drop_field('languages', 'formula');
    db_drop_field('languages', 'javascript');
    db_drop_field('languages', 'prefix');
    db_drop_field('languages', 'domain');
    db_drop_field('languages', 'native');
    db_drop_field('languages', 'enabled');

    // Update language count.
    \Drupal::state()->set('language_count', db_query('SELECT COUNT(language) FROM {languages}')->fetchField());

    // Rename the languages table to language.
    db_rename_table('languages', 'language');

    // Rename language column to langcode and set it again as the primary key.
    if (db_field_exists('language', 'language')) {
      db_drop_primary_key('language');
      $langcode_spec = array(
        'type' => 'varchar',
        'length' => 12,
        'not null' => TRUE,
        'default' => '',
        'description' => "Language code, e.g. 'de' or 'en-US'.",
      );
      db_change_field('language', 'language', 'langcode', $langcode_spec, array('primary key' => array('langcode')));
    }

    // Adds the locked column and saves the special languages.
    if (!db_field_exists('language', 'locked')) {
      $locked_spec = array(
        'type' => 'int',
        'size' => 'tiny',
        'not null' => TRUE,
        'default' => 0,
        'description' => 'A boolean indicating whether the administrator can edit or delete the language.',
      );
      db_add_field('language', 'locked', $locked_spec);

      $max_language_weight = db_query('SELECT MAX(weight) FROM {language}')->fetchField();
      $languages = language_default_locked_languages($max_language_weight);
      foreach ($languages as $language) {
        db_insert('language')
          ->fields(array(
            'langcode' => $language->id,
            'name' => $language->name,
            'weight' => $language->weight,
            // These languages are locked, default to enabled.
            'locked' => 1,
          ))
          ->execute();
      }
    }

    // Update the 'language_default' system variable with the langcode change.
    $language_default = update_variable_get('language_default');
    if (!empty($language_default)) {
      if (isset($language_default->language)) {
        $language_default->id = $language_default->language;
        unset($language_default->language);
      }
      unset($language_default->enabled);
      // In D8, the 'language_default' is not anymore an object, but an array,
      // so make sure that the new value that is saved into this variable is an
      // array.
      $language_default = (array) $language_default;
      $language_default['langcode'] = 'en';
      update_variable_set('language_default', $language_default);
    }

    // Convert languages to config entities.
    $result = db_query('SELECT * FROM {language}');
    $uuid = \Drupal::service('uuid');
    foreach ($result as $language) {
      \Drupal::config('language.entity.' . $language->langcode)
        ->set('id', $language->langcode)
        ->set('uuid', $uuid->generate())
        ->set('label', $language->name)
        ->set('direction', $language->direction)
        ->set('weight', $language->weight)
        ->set('locked', $language->locked)
        ->set('langcode', 'en')
        ->save();
    }

    // Add column to track customized string status to locales_target.
    // When updating in a non-English language, the locale translation system is
    // triggered, which attempts to query string translations already.
    if (db_table_exists('locales_target') && !db_field_exists('locales_target', 'customized')) {
      $spec = array(
        'type' => 'int',
        'not null' => TRUE,
        'default' => 0, // LOCALE_NOT_CUSTOMIZED
        'description' => 'Boolean indicating whether the translation is custom to this site.',
      );
      db_add_field('locales_target', 'customized', $spec);
    }
    // Add locales_location table to track string locations.
    // When updating in a non-English language, this table is used for
    // refreshing JavaScript translations.
    if (db_table_exists('locales_source') && !db_table_exists('locales_location')) {
      $table = array(
        'description' => 'Location information for source strings.',
        'fields' => array(
          'lid' => array(
            'type' => 'serial',
            'not null' => TRUE,
            'description' => 'Unique identifier of this location.',
          ),
          'sid' => array(
            'type' => 'int',
            'not null' => TRUE,
            'description' => 'Unique identifier of this string.',
          ),
          'type' => array(
            'type' => 'varchar',
            'length' => 50,
            'not null' => TRUE,
            'default' => '',
            'description' => 'The location type (file, config, path, etc).',
          ),
          'name' => array(
            'type' => 'varchar',
            'length' => 255,
            'not null' => TRUE,
            'default' => '',
            'description' => 'Type dependent location information (file name, path, etc).',
          ),
          'version' => array(
            'type' => 'varchar',
            'length' => 20,
            'not null' => TRUE,
            'default' => 'none',
            'description' => 'Version of Drupal where the location was found.',
          ),
        ),
        'primary key' => array('lid'),
        'foreign keys' => array(
          'locales_source' => array(
            'table' => 'locales_source',
            'columns' => array('sid' => 'lid'),
          ),
        ),
        'indexes' => array(
          'string_id' => array('sid'),
          'string_type' => array('sid', 'type'),
        ),
      );

      db_create_table('locales_location', $table);
    }
  }
}

/**
 * Performs Drupal 7.x to 8.x required update.php updates.
 *
 * This function runs when update.php is run the first time for 8.x,
 * even before updates are selected or performed. It is important
 * that if updates are not ultimately performed that no changes are
 * made which make it impossible to continue using the prior version.
 */
function update_fix_d8_requirements() {
  if (drupal_get_installed_schema_version('system') < 8000 && !update_variable_get('update_d8_requirements', FALSE)) {

    // Make sure that file.module is enabled as it is required for the user
    // picture upgrade path.
    \Drupal::moduleHandler()->install(array('file'));

    $schema = array(
      'description' => 'Generic key/value storage table with an expiration.',
      'fields' => array(
        'collection' => array(
          'description' => 'A named collection of key and value pairs.',
          'type' => 'varchar',
          'length' => 128,
          'not null' => TRUE,
          'default' => '',
        ),
        'name' => array(
          // KEY is an SQL reserved word, so use 'name' as the key's field name.
          'description' => 'The key of the key/value pair.',
          'type' => 'varchar',
          'length' => 128,
          'not null' => TRUE,
          'default' => '',
        ),
        'value' => array(
          'description' => 'The value of the key/value pair.',
          'type' => 'blob',
          'not null' => TRUE,
          'size' => 'big',
        ),
        'expire' => array(
          'description' => 'The time since Unix epoch in seconds when this item expires. Defaults to the maximum possible time.',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 2147483647,
        ),
      ),
      'primary key' => array('collection', 'name'),
      'indexes' => array(
        'all' => array('name', 'collection', 'expire'),
      ),
    );
    db_create_table('key_value_expire', $schema);

    // Views module is required to convert all previously existing listings into
    // views configurations.
    // Like any other module APIs and services, Views' services are not available
    // in update.php. Existing listings are migrated into configuration, using
    // the limited standard tools of raw database queries and \Drupal::config().
    \Drupal::moduleHandler()->install(array('views'));

    update_variable_set('update_d8_requirements', TRUE);
  }
}

/**
 * Forces a module to a given schema version.
 *
 * This function is rarely necessary.
 *
 * @param string $module
 *   Name of the module.
 * @param string $schema_version
 *   The schema version the module should be set to.
 */
function update_set_schema($module, $schema_version) {
  \Drupal::keyValue('system.schema')->set($module, $schema_version);
  // system_list_reset() is in module.inc but that would only be available
  // once the variable bootstrap is done.
  require_once __DIR__ . '/module.inc';
  system_list_reset();
}

/**
 * Performs one update and stores the results for display on the results page.
 *
 * If an update function completes successfully, it should return a message
 * as a string indicating success, for example:
 * @code
 * return t('New index added successfully.');
 * @endcode
 *
 * Alternatively, it may return nothing. In that case, no message
 * will be displayed at all.
 *
 * If it fails for whatever reason, it should throw an instance of
 * Drupal\Core\Utility\UpdateException with an appropriate error message, for
 * example:
 * @code
 * use Drupal\Core\Utility\UpdateException;
 * throw new UpdateException(t('Description of what went wrong'));
 * @endcode
 *
 * If an exception is thrown, the current update and all updates that depend on
 * it will be aborted. The schema version will not be updated in this case, and
 * all the aborted updates will continue to appear on update.php as updates
 * that have not yet been run.
 *
 * If an update function needs to be re-run as part of a batch process, it
 * should accept the $sandbox array by reference as its first parameter
 * and set the #finished property to the percentage completed that it is, as a
 * fraction of 1.
 *
 * @param $module
 *   The module whose update will be run.
 * @param $number
 *   The update number to run.
 * @param $dependency_map
 *   An array whose keys are the names of all update functions that will be
 *   performed during this batch process, and whose values are arrays of other
 *   update functions that each one depends on.
 * @param $context
 *   The batch context array.
 *
 * @see update_resolve_dependencies()
 */
function update_do_one($module, $number, $dependency_map, &$context) {
  $function = $module . '_update_' . $number;

  // If this update was aborted in a previous step, or has a dependency that
  // was aborted in a previous step, go no further.
  if (!empty($context['results']['#abort']) && array_intersect($context['results']['#abort'], array_merge($dependency_map, array($function)))) {
    return;
  }

  $ret = array();
  if (function_exists($function)) {
    try {
      $ret['results']['query'] = $function($context['sandbox']);
      $ret['results']['success'] = TRUE;
    }
    // @TODO We may want to do different error handling for different
    // exception types, but for now we'll just log the exception and
    // return the message for printing.
    catch (Exception $e) {
      watchdog_exception('update', $e);

      require_once __DIR__ . '/errors.inc';
      $variables = _drupal_decode_exception($e);
      unset($variables['backtrace']);
      // The exception message is run through
      // \Drupal\Component\Utility\String::checkPlain() by
      // _drupal_decode_exception().
      $ret['#abort'] = array('success' => FALSE, 'query' => t('%type: !message in %function (line %line of %file).', $variables));
    }
  }

  if (isset($context['sandbox']['#finished'])) {
    $context['finished'] = $context['sandbox']['#finished'];
    unset($context['sandbox']['#finished']);
  }

  if (!isset($context['results'][$module])) {
    $context['results'][$module] = array();
  }
  if (!isset($context['results'][$module][$number])) {
    $context['results'][$module][$number] = array();
  }
  $context['results'][$module][$number] = array_merge($context['results'][$module][$number], $ret);

  if (!empty($ret['#abort'])) {
    // Record this function in the list of updates that were aborted.
    $context['results']['#abort'][] = $function;
  }

  // Record the schema update if it was completed successfully.
  if ($context['finished'] == 1 && empty($ret['#abort'])) {
    drupal_set_installed_schema_version($module, $number);
  }

  $context['message'] = 'Updating ' . String::checkPlain($module) . ' module';
}

/**
 * Starts the database update batch process.
 *
 * @param $start
 *   An array whose keys contain the names of modules to be updated during the
 *   current batch process, and whose values contain the number of the first
 *   requested update for that module. The actual updates that are run (and the
 *   order they are run in) will depend on the results of passing this data
 *   through the update dependency system.
 * @param $redirect
 *   Path to redirect to when the batch has finished processing.
 * @param $url
 *   URL of the batch processing page (should only be used for separate
 *   scripts like update.php).
 * @param $batch
 *   Optional parameters to pass into the batch API.
 * @param $redirect_callback
 *   (optional) Specify a function to be called to redirect to the progressive
 *   processing page.
 *
 * @see update_resolve_dependencies()
 */
function update_batch($start, $redirect = NULL, $url = NULL, $batch = array(), $redirect_callback = NULL) {
  // During the update, bring the site offline so that schema changes do not
  // affect visiting users.
  $maintenance_mode = \Drupal::config('system.maintenance')->get('enabled');
  if (isset($maintenance_mode)) {
    $_SESSION['maintenance_mode'] = $maintenance_mode;
  }
  if (empty($_SESSION['maintenance_mode'])) {
    if (db_table_exists('state')) {
      \Drupal::state()->set('system.maintenance_mode', TRUE);
    }
  }

  // Resolve any update dependencies to determine the actual updates that will
  // be run and the order they will be run in.
  $updates = update_resolve_dependencies($start);

  // Store the dependencies for each update function in an array which the
  // batch API can pass in to the batch operation each time it is called. (We
  // do not store the entire update dependency array here because it is
  // potentially very large.)
  $dependency_map = array();
  foreach ($updates as $function => $update) {
    $dependency_map[$function] = !empty($update['reverse_paths']) ? array_keys($update['reverse_paths']) : array();
  }

  $operations = array();
  foreach ($updates as $update) {
    if ($update['allowed']) {
      // Set the installed version of each module so updates will start at the
      // correct place. (The updates are already sorted, so we can simply base
      // this on the first one we come across in the above foreach loop.)
      if (isset($start[$update['module']])) {
        drupal_set_installed_schema_version($update['module'], $update['number'] - 1);
        unset($start[$update['module']]);
      }
      // Add this update function to the batch.
      $function = $update['module'] . '_update_' . $update['number'];
      $operations[] = array('update_do_one', array($update['module'], $update['number'], $dependency_map[$function]));
    }
  }
  $batch['operations'] = $operations;
  $batch += array(
    'title' => 'Updating',
    'init_message' => 'Starting updates',
    'error_message' => 'An unrecoverable error has occurred. You can find the error message below. It is advised to copy it to the clipboard for reference.',
    'finished' => 'update_finished',
    'file' => 'core/includes/update.inc',
  );
  batch_set($batch);
  return batch_process($redirect, $url, $redirect_callback);
}

/**
 * Finishes the update process and stores the results for eventual display.
 *
 * After the updates run, all caches are flushed. The update results are
 * stored into the session (for example, to be displayed on the update results
 * page in update.php). Additionally, if the site was off-line, now that the
 * update process is completed, the site is set back online.
 *
 * @param $success
 *   Indicate that the batch API tasks were all completed successfully.
 * @param $results
 *   An array of all the results that were updated in update_do_one().
 * @param $operations
 *   A list of all the operations that had not been completed by the batch API.
 *
 * @see update_batch()
 */
function update_finished($success, $results, $operations) {
  // Clear the caches in case the data has been updated.
  update_flush_all_caches();

  $_SESSION['update_results'] = $results;
  $_SESSION['update_success'] = $success;
  $_SESSION['updates_remaining'] = $operations;

  // Now that the update is done, we can put the site back online if it was
  // previously in maintenance mode.
  if (isset($_SESSION['maintenance_mode'])) {
    \Drupal::state()->set('system.maintenance_mode', FALSE);
    unset($_SESSION['maintenance_mode']);
  }
}

/**
 * Returns a list of all the pending database updates.
 *
 * @return
 *   An associative array keyed by module name which contains all information
 *   about database updates that need to be run, and any updates that are not
 *   going to proceed due to missing requirements. The system module will
 *   always be listed first.
 *
 *   The subarray for each module can contain the following keys:
 *   - start: The starting update that is to be processed. If this does not
 *       exist then do not process any updates for this module as there are
 *       other requirements that need to be resolved.
 *   - warning: Any warnings about why this module can not be updated.
 *   - pending: An array of all the pending updates for the module including
 *       the update number and the description from source code comment for
 *       each update function. This array is keyed by the update number.
 */
function update_get_update_list() {
  // Make sure that the system module is first in the list of updates.
  $ret = array('system' => array());

  $modules = drupal_get_installed_schema_version(NULL, FALSE, TRUE);
  foreach ($modules as $module => $schema_version) {
    // Skip uninstalled and incompatible modules.
    if ($schema_version == SCHEMA_UNINSTALLED || update_check_incompatibility($module)) {
      continue;
    }
    // Otherwise, get the list of updates defined by this module.
    $updates = drupal_get_schema_versions($module);
    if ($updates !== FALSE) {
      // module_invoke returns NULL for nonexisting hooks, so if no updates
      // are removed, it will == 0.
      $last_removed = module_invoke($module, 'update_last_removed');
      if ($schema_version < $last_removed) {
        $ret[$module]['warning'] = '<em>' . $module . '</em> module can not be updated. Its schema version is ' . $schema_version . '. Updates up to and including ' . $last_removed . ' have been removed in this release. In order to update <em>' . $module . '</em> module, you will first <a href="http://drupal.org/upgrade">need to upgrade</a> to the last version in which these updates were available.';
        continue;
      }

      $updates = drupal_map_assoc($updates);
      foreach (array_keys($updates) as $update) {
        if ($update > $schema_version) {
          // The description for an update comes from its Doxygen.
          $func = new ReflectionFunction($module . '_update_' . $update);
          $description = str_replace(array("\n", '*', '/'), '', $func->getDocComment());
          $ret[$module]['pending'][$update] = "$update - $description";
          if (!isset($ret[$module]['start'])) {
            $ret[$module]['start'] = $update;
          }
        }
      }
      if (!isset($ret[$module]['start']) && isset($ret[$module]['pending'])) {
        $ret[$module]['start'] = $schema_version;
      }
    }
  }

  if (empty($ret['system'])) {
    unset($ret['system']);
  }
  return $ret;
}

/**
 * Resolves dependencies in a set of module updates, and orders them correctly.
 *
 * This function receives a list of requested module updates and determines an
 * appropriate order to run them in such that all update dependencies are met.
 * Any updates whose dependencies cannot be met are included in the returned
 * array but have the key 'allowed' set to FALSE; the calling function should
 * take responsibility for ensuring that these updates are ultimately not
 * performed.
 *
 * In addition, the returned array also includes detailed information about the
 * dependency chain for each update, as provided by the depth-first search
 * algorithm in Drupal\Component\Graph\Graph::searchAndSort().
 *
 * @param $starting_updates
 *   An array whose keys contain the names of modules with updates to be run
 *   and whose values contain the number of the first requested update for that
 *   module.
 *
 * @return
 *   An array whose keys are the names of all update functions within the
 *   provided modules that would need to be run in order to fulfill the
 *   request, arranged in the order in which the update functions should be
 *   run. (This includes the provided starting update for each module and all
 *   subsequent updates that are available.) The values are themselves arrays
 *   containing all the keys provided by the
 *   Drupal\Component\Graph\Graph::searchAndSort() algorithm, which encode
 *   detailed information about the dependency chain for this update function
 *   (for example: 'paths', 'reverse_paths', 'weight', and 'component'), as
 *   well as the following additional keys:
 *   - 'allowed': A boolean which is TRUE when the update function's
 *     dependencies are met, and FALSE otherwise. Calling functions should
 *     inspect this value before running the update.
 *   - 'missing_dependencies': An array containing the names of any other
 *     update functions that are required by this one but that are unavailable
 *     to be run. This array will be empty when 'allowed' is TRUE.
 *   - 'module': The name of the module that this update function belongs to.
 *   - 'number': The number of this update function within that module.
 *
 * @see \Drupal\Component\Graph\Graph::searchAndSort()
 */
function update_resolve_dependencies($starting_updates) {
  // Obtain a dependency graph for the requested update functions.
  $update_functions = update_get_update_function_list($starting_updates);
  $graph = update_build_dependency_graph($update_functions);

  // Perform the depth-first search and sort on the results.
  $graph_object = new Graph($graph);
  $graph = $graph_object->searchAndSort();
  uasort($graph, 'drupal_sort_weight');

  foreach ($graph as $function => &$data) {
    $module = $data['module'];
    $number = $data['number'];
    // If the update function is missing and has not yet been performed, mark
    // it and everything that ultimately depends on it as disallowed.
    if (update_is_missing($module, $number, $update_functions) && !update_already_performed($module, $number)) {
      $data['allowed'] = FALSE;
      foreach (array_keys($data['paths']) as $dependent) {
        $graph[$dependent]['allowed'] = FALSE;
        $graph[$dependent]['missing_dependencies'][] = $function;
      }
    }
    elseif (!isset($data['allowed'])) {
      $data['allowed'] = TRUE;
      $data['missing_dependencies'] = array();
    }
    // Now that we have finished processing this function, remove it from the
    // graph if it was not part of the original list. This ensures that we
    // never try to run any updates that were not specifically requested.
    if (!isset($update_functions[$module][$number])) {
      unset($graph[$function]);
    }
  }

  return $graph;
}

/**
 * Returns an organized list of update functions for a set of modules.
 *
 * @param $starting_updates
 *   An array whose keys contain the names of modules and whose values contain
 *   the number of the first requested update for that module.
 *
 * @return
 *   An array containing all the update functions that should be run for each
 *   module, including the provided starting update and all subsequent updates
 *   that are available. The keys of the array contain the module names, and
 *   each value is an ordered array of update functions, keyed by the update
 *   number.
 *
 * @see update_resolve_dependencies()
 */
function update_get_update_function_list($starting_updates) {
  // Go through each module and find all updates that we need (including the
  // first update that was requested and any updates that run after it).
  $update_functions = array();
  foreach ($starting_updates as $module => $version) {
    $update_functions[$module] = array();
    $updates = drupal_get_schema_versions($module);
    if ($updates !== FALSE) {
      $max_version = max($updates);
      if ($version <= $max_version) {
        foreach ($updates as $update) {
          if ($update >= $version) {
            $update_functions[$module][$update] = $module . '_update_' . $update;
          }
        }
      }
    }
  }
  return $update_functions;
}

/**
 * Constructs a graph which encodes the dependencies between module updates.
 *
 * This function returns an associative array which contains a "directed graph"
 * representation of the dependencies between a provided list of update
 * functions, as well as any outside update functions that they directly depend
 * on but that were not in the provided list. The vertices of the graph
 * represent the update functions themselves, and each edge represents a
 * requirement that the first update function needs to run before the second.
 * For example, consider this graph:
 *
 * system_update_8000 ---> system_update_8001 ---> system_update_8002
 *
 * Visually, this indicates that system_update_8000() must run before
 * system_update_8001(), which in turn must run before system_update_8002().
 *
 * The function takes into account standard dependencies within each module, as
 * shown above (i.e., the fact that each module's updates must run in numerical
 * order), but also finds any cross-module dependencies that are defined by
 * modules which implement hook_update_dependencies(), and builds them into the
 * graph as well.
 *
 * @param $update_functions
 *   An organized array of update functions, in the format returned by
 *   update_get_update_function_list().
 *
 * @return
 *   A multidimensional array representing the dependency graph, suitable for
 *   passing in to Drupal\Component\Graph\Graph::searchAndSort(), but with extra
 *   information about each update function also included. Each array key
 *   contains the name of an update function, including all update functions
 *   from the provided list as well as any outside update functions which they
 *   directly depend on. Each value is an associative array containing the
 *   following keys:
 *   - 'edges': A representation of any other update functions that immediately
 *     depend on this one. See Drupal\Component\Graph\Graph::searchAndSort() for
 *     more details on the format.
 *   - 'module': The name of the module that this update function belongs to.
 *   - 'number': The number of this update function within that module.
 *
 * @see \Drupal\Component\Graph\Graph::searchAndSort()
 * @see update_resolve_dependencies()
 */
function update_build_dependency_graph($update_functions) {
  // Initialize an array that will define a directed graph representing the
  // dependencies between update functions.
  $graph = array();

  // Go through each update function and build an initial list of dependencies.
  foreach ($update_functions as $module => $functions) {
    $previous_function = NULL;
    foreach ($functions as $number => $function) {
      // Add an edge to the directed graph representing the fact that each
      // update function in a given module must run after the update that
      // numerically precedes it.
      if ($previous_function) {
        $graph[$previous_function]['edges'][$function] = TRUE;
      }
      $previous_function = $function;

      // Define the module and update number associated with this function.
      $graph[$function]['module'] = $module;
      $graph[$function]['number'] = $number;
    }
  }

  // Now add any explicit update dependencies declared by modules.
  $update_dependencies = update_retrieve_dependencies();
  foreach ($graph as $function => $data) {
    if (!empty($update_dependencies[$data['module']][$data['number']])) {
      foreach ($update_dependencies[$data['module']][$data['number']] as $module => $number) {
        $dependency = $module . '_update_' . $number;
        $graph[$dependency]['edges'][$function] = TRUE;
        $graph[$dependency]['module'] = $module;
        $graph[$dependency]['number'] = $number;
      }
    }
  }

  return $graph;
}

/**
 * Determines if a module update is missing or unavailable.
 *
 * @param $module
 *   The name of the module.
 * @param $number
 *   The number of the update within that module.
 * @param $update_functions
 *   An organized array of update functions, in the format returned by
 *   update_get_update_function_list(). This should represent all module
 *   updates that are requested to run at the time this function is called.
 *
 * @return
 *   TRUE if the provided module update is not installed or is not in the
 *   provided list of updates to run; FALSE otherwise.
 */
function update_is_missing($module, $number, $update_functions) {
  return !isset($update_functions[$module][$number]) || !function_exists($update_functions[$module][$number]);
}

/**
 * Determines if a module update has already been performed.
 *
 * @param $module
 *   The name of the module.
 * @param $number
 *   The number of the update within that module.
 *
 * @return
 *   TRUE if the database schema indicates that the update has already been
 *   performed; FALSE otherwise.
 */
function update_already_performed($module, $number) {
  return $number <= drupal_get_installed_schema_version($module);
}

/**
 * Invokes hook_update_dependencies() in all installed modules.
 *
 * This function is similar to \Drupal::moduleHandler()->invokeAll(), with the
 * main difference that it does not require that a module be enabled to invoke
 * its hook, only that it be installed. This allows the update system to
 * properly perform updates even on modules that are currently disabled.
 *
 * @return
 *   An array of return values obtained by merging the results of the
 *   hook_update_dependencies() implementations in all installed modules.
 *
 * @see \Drupal::moduleHandler()->invokeAll()
 * @see hook_update_dependencies()
 */
function update_retrieve_dependencies() {
  $return = array();
  // Get a list of installed modules, arranged so that we invoke their hooks in
  // the same order that \Drupal::moduleHandler()->invokeAll() does.
  foreach (\Drupal::keyValue('system.schema')->getAll() as $module => $schema) {
    if ($schema == SCHEMA_UNINSTALLED) {
      // Nothing to upgrade.
      continue;
    }
    $function = $module . '_update_dependencies';
    // Ensure install file is loaded.
    module_load_install($module);
    if (function_exists($function)) {
      $result = $function();
      // Each implementation of hook_update_dependencies() returns a
      // multidimensional, associative array containing some keys that
      // represent module names (which are strings) and other keys that
      // represent update function numbers (which are integers). We cannot use
      // array_merge_recursive() to properly merge these results, since it
      // treats strings and integers differently. Therefore, we have to
      // explicitly loop through the expected array structure here and perform
      // the merge manually.
      if (isset($result) && is_array($result)) {
        foreach ($result as $module => $module_data) {
          foreach ($module_data as $update => $update_data) {
            foreach ($update_data as $module_dependency => $update_dependency) {
              // If there are redundant dependencies declared for the same
              // update function (so that it is declared to depend on more than
              // one update from a particular module), record the dependency on
              // the highest numbered update here, since that automatically
              // implies the previous ones. For example, if one module's
              // implementation of hook_update_dependencies() required this
              // ordering:
              //
              // system_update_8001 ---> user_update_8000
              //
              // but another module's implementation of the hook required this
              // one:
              //
              // system_update_8002 ---> user_update_8000
              //
              // we record the second one, since system_update_8001() is always
              // guaranteed to run before system_update_8002() anyway (within
              // an individual module, updates are always run in numerical
              // order).
              if (!isset($return[$module][$update][$module_dependency]) || $update_dependency > $return[$module][$update][$module_dependency]) {
                $return[$module][$update][$module_dependency] = $update_dependency;
              }
            }
          }
        }
      }
    }
  }

  return $return;
}

/**
 * Gets the value of a variable from the database during 7.x-8.x upgrades.
 *
 * Use this during the 7.x-8.x upgrade path instead of variable_get().
 *
 * @param string $name
 *   The name of the variable.
 * @param mixed $default
 *   The default value of the variable.
 *
 * @return mixed
 *   The value of the variable in the database unserialized, or NULL if not set.
 *
 * @see update_variable_set()
 * @see update_variable_del()
 * @ingroup config_upgrade
 */
function update_variable_get($name, $default = NULL) {
  $result = db_query('SELECT value FROM {variable} WHERE name = :name', array(':name' => $name))->fetchField();
  if ($result !== FALSE) {
    return unserialize($result);
  }
  return $default;
}

/**
 * Sets a persistent variable during the 7.x-8.x upgrade path.
 *
 * Use this during the 7.x-8.x upgrade path instead of variable_set().
 *
 * @param string $name
 *   The name of the variable to set.
 * @param mixed $value
 *   The value to set. This can be any PHP data type; these functions take care
 *   of serialization as necessary.
 *
 * @see update_variable_get()
 * @see update_variable_del()
 * @ingroup config_upgrade
 */
function update_variable_set($name, $value) {
  db_merge('variable')
    ->key(array(
      'name' => $name,
    ))
    ->fields(array(
      'value' => serialize($value),
    ))
    ->execute();
}

/**
 * Delete a variable from the database during the 7.x-8.x upgrade path.
 *
 * Use this during the 7.x-8.x upgrade path instead of variable_del().
 *
 * @param string $name
 *   The name of the variable to delete.
 *
 * @see update_variable_get()
 * @see update_variable_set()
 * @ingroup config_upgrade
 */
function update_variable_del($name) {
  db_delete('variable')
    ->condition('name', $name)
    ->execute();
}

/**
 * Updates config with values set on Drupal 7.x.
 *
 * Provides a generalised method to migrate variables from Drupal 7 to
 * Drupal 8's configuration management system.
 *
 * @param string $config_name
 *   The configuration object name to retrieve.
 * @param array $variable_map
 *   An associative array that maps old variables names to new configuration
 *   object keys; e.g.:
 *   @code
 *     array('old_variable' => 'new_config.sub_key')
 *   @endcode
 *   This would migrate the value contained in variable name 'old_variable' into
 *   the data key 'new_config.sub_key' of the configuration object $config_name.
 */
function update_variables_to_config($config_name, array $variable_map) {
  // Build the new configuration object.
  // This potentially loads an existing configuration object, in case another
  // update function migrated configuration values into $config_name already.
  $config = \Drupal::config($config_name);
  $original_data = $config->get();

  // Extract the module namespace/owner from the configuration object name.
  $module = strtok($config_name, '.');

  // Load and set default configuration values.
  $file = new FileStorage(drupal_get_path('module', $module) . '/config');
  if (!$file->exists($config_name)) {
    throw new ConfigException("Default configuration file $config_name for $module extension not found but is required to exist.");
  }
  $default_data = $file->read($config_name);

  // Apply the default values.
  $config->setData($default_data);

  // Merge any possibly existing original data into default values.
  // Only relevant when being called repetitively on the same config object.
  if (!empty($original_data)) {
    $config->merge($original_data);
  }

  // Fetch existing variables.
  $variables = db_query('SELECT name, value FROM {variable} WHERE name IN (:variables)', array(':variables' => array_keys($variable_map)))->fetchAllKeyed();

  // Set configuration values according to the provided variable mapping.
  foreach ($variable_map as $variable_name => $config_key) {
    // This function migrates variables regardless of their value, including
    // NULL values. Any possibly required customizations need to be performed
    // manually, either via variable_set() before calling this function or via
    // \Drupal::config() after calling this function.
    if (isset($variables[$variable_name])) {
      $value = unserialize($variables[$variable_name]);
      $config->set($config_key, $value);
    }
  }

  // Save the configuration object.
  $config->save();

  // Delete the migrated variables.
  db_delete('variable')->condition('name', array_keys($variable_map), 'IN')->execute();
}

/**
 * Installs a default configuration file into the active store.
 *
 * Provide a generalised method to save a default configuration object for an
 * already enabled module or theme as part of an update from Drupal 7 to Drupal
 * 8's configuration management system.
 *
 * @param string $type
 *   The extension type; e.g., 'module' or 'theme'.
 * @param string $config_name
 *   The configuration object name to retrieve.
 * @param string $name
 *   (optional) The owner of the config. Defaults to NULL, in which case the
 *   name will be derived from the $config_name.
 *
 * @return boolean
 *   True on success, false if config file does not exist.
 */
function update_install_default_config($type, $config_name, $name = NULL) {
  // Build the new configuration object.
  $config = \Drupal::config($config_name);

  // Extract the extension namespace/owner from the configuration object name.
  if (!$name) {
    $name = strtok($config_name, '.');
  }

  // Load and set default configuration values.
  $file = new FileStorage(drupal_get_path($type, $name) . '/config');
  if (!$file->exists($config_name)) {
    return FALSE;
  }

  // Apply and save the default values.
  $config->setData($file->read($config_name))->save();
  return TRUE;
}

/**
 * Updates 7.x variables to state records.
 *
 * Provides a generalized method to migrate variables from 7.x to 8.x's
 * \Drupal::state() system.
 *
 * @param array $variable_map
 *   An associative array that maps old variables names to new state record
 *   names; e.g.:
 *   @code
 *     array('old_variable' => 'extension.new_name')
 *   @endcode
 *   This would migrate the value contained in variable name 'old_variable' into
 *   the state item 'extension.new_name'.
 *   Non-existing variables and variables with NULL values are omitted.
 */
function update_variables_to_state(array $variable_map) {
  foreach ($variable_map as $variable_name => $state_name) {
    if (NULL !== $value = update_variable_get($variable_name)) {
      \Drupal::state()->set($state_name, $value);
    }
  }

  // Delete the migrated variables.
  db_delete('variable')
    ->condition('name', array_keys($variable_map))
    ->execute();
}

/**
 * Helper function to update entities with uuid.
 *
 * @param array $sandbox
 *   A sandbox where conversion happens.
 * @param string $table
 *   A table whose data should be updated.
 * @param string $primary_key
 *   A $table primary key column.
 * @param array $values
 *   A $primary_key values of rows to be updated.
 */
function update_add_uuids(&$sandbox, $table, $primary_key, $values) {
  $uuid = \Drupal::service('uuid');
  foreach ($values as $value) {
    db_update($table)
      ->fields(array(
        'uuid' => $uuid->generate(),
      ))
      ->condition($primary_key, $value)
      ->isNull('uuid')
      ->execute();
    $sandbox['progress']++;
    $sandbox['last'] = $value;
  }
}


/**
 * Adds tags, checksum_invalidations, checksum_deletions to a cache table.
 *
 * @param string $table
 *   Name of the cache table.
 */
function update_add_cache_columns($table) {
  if (db_table_exists($table) && !db_field_exists($table, 'tags')) {
    db_add_field($table, 'tags', array(
      'description' => 'Space-separated list of cache tags for this entry.',
      'type' => 'text',
      'size' => 'big',
      'not null' => FALSE,
    ));
    db_add_field($table, 'checksum_invalidations', array(
      'description' => 'The tag invalidation sum when this entry was saved.',
      'type' => 'int',
      'not null' => TRUE,
      'default' => 0,
    ));
    db_add_field($table, 'checksum_deletions', array(
      'description' => 'The tag deletion sum when this entry was saved.',
      'type' => 'int',
      'not null' => TRUE,
      'default' => 0,
    ));
  }
}

/**
 * Replace permissions during update.
 *
 * This function can replace one permission to several or even delete an old
 * one.
 *
 * @param array $replace
 *   An associative array. The keys are the old permissions the values are lists
 *   of new permissions. If the list is an empty array, the old permission is
 *   removed.
 */
function update_replace_permissions($replace) {
  $prefix = 'user.role.';
  $cut = strlen($prefix);
  $role_names = \Drupal::service('config.storage')->listAll($prefix);
  foreach ($role_names as $role_name) {
    $rid = substr($role_name, $cut);
    $config = \Drupal::config("user.role.$rid");
    $permissions = $config->get('permissions') ?: array();
    foreach ($replace as $old_permission => $new_permissions) {
      if (($index = array_search($old_permission, $permissions)) !== FALSE) {
        unset($permissions[$index]);
        $permissions = array_unique(array_merge($permissions, $new_permissions));
      }
    }
    $config
      ->set('permissions', $permissions)
      ->save();
  }
}

/**
 * Returns a list of languages set up on the site during upgrades.
 *
 * @param $flags
 *   (optional) Specifies the state of the languages that have to be returned.
 *   It can be: Language::STATE_CONFIGURABLE, Language::STATE_LOCKED,
 *   Language::STATE_ALL.
 *
 * @return array
 *   An associative array of languages, keyed by the language code, ordered by
 *   weight ascending and name ascending.
 */
function update_language_list($flags = Language::STATE_CONFIGURABLE) {

  $languages = &drupal_static(__FUNCTION__);

  // Initialize master language list.
  if (!isset($languages)) {
    // Initialize local language list cache.
   $languages = array();

    // Fill in master language list based on current configuration.
    $default = language_default();
    if (language_multilingual() || \Drupal::moduleHandler()->moduleExists('language')) {
      // Use language module configuration if available. We can not use
      // entity_load_multiple() because this breaks during updates.
      $language_entities = config_get_storage_names_with_prefix('language.entity.');

      // Initialize default property so callers have an easy reference and can
      // save the same object without data loss.
      foreach ($language_entities as $langcode_config_name) {
        $langcode = substr($langcode_config_name, strlen('language.entity.'));
        $info = \Drupal::config($langcode_config_name)->get();
        $languages[$langcode] = new Language(array(
          'default' => ($info['id'] == $default->id),
          'name' => $info['label'],
          'id' => $info['id'],
          'direction' => $info['direction'],
          'locked' => $info['locked'],
          'weight' => $info['weight'],
        ));
      }
      Language::sort($languages);
    }
    else {
      // No language module, so use the default language only.
      $languages = array($default->id => $default);
      // Add the special languages, they will be filtered later if needed.
      $languages += language_default_locked_languages($default->weight);
    }
  }

  // Filter the full list of languages based on the value of the $all flag. By
  // default we remove the locked languages, but the caller may request for
  // those languages to be added as well.
  $filtered_languages = array();

  // Add the site's default language if flagged as allowed value.
  if ($flags & Language::STATE_SITE_DEFAULT) {
    $default = isset($default) ? $default : language_default();
    // Rename the default language.
    $default->name = t("Site's default language (@lang_name)", array('@lang_name' => $default->name));
    $filtered_languages['site_default'] = $default;
  }

  foreach ($languages as $langcode => $language) {
    if (($language->locked && !($flags & Language::STATE_LOCKED)) || (!$language->locked && !($flags & Language::STATE_CONFIGURABLE))) {
      continue;
     }
    $filtered_languages[$langcode] = $language;
  }

  return $filtered_languages;
}
