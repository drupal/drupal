<?php

namespace Drupal\Core\Update;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ExtensionDiscovery;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides all and missing update implementations.
 *
 * Note: This registry is specific to a type of updates, like 'post_update' as
 * example.
 *
 * It therefore scans for functions named like the type of updates, so it looks
 * like EXTENSION_UPDATETYPE_NAME() with NAME being a machine name.
 */
class UpdateRegistry implements EventSubscriberInterface {

  /**
   * The used update name.
   *
   * @var string
   */
  protected $updateType = 'post_update';

  /**
   * The app root.
   *
   * @var string
   */
  protected $root;

  /**
   * The filename of the log file.
   *
   * @var string
   */
  protected $logFilename;

  /**
   * @var string[]
   */
  protected $enabledExtensions;

  /**
   * The key value storage.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $keyValue;

  /**
   * Should we respect update functions in tests.
   *
   * @var bool|null
   */
  protected $includeTests = NULL;

  /**
   * The site path.
   *
   * @var string
   */
  protected $sitePath;

  /**
   * Constructs a new UpdateRegistry.
   *
   * @param string $root
   *   The app root.
   * @param string $site_path
   *   The site path.
   * @param string[] $enabled_extensions
   *   A list of enabled extensions.
   * @param \Drupal\Core\KeyValueStore\KeyValueStoreInterface $key_value
   *   The key value store.
   * @param bool|null $include_tests
   *   (optional) A flag whether to include tests in the scanning of extensions.
   */
  public function __construct($root, $site_path, array $enabled_extensions, KeyValueStoreInterface $key_value, $include_tests = NULL) {
    $this->root = $root;
    $this->sitePath = $site_path;
    $this->enabledExtensions = $enabled_extensions;
    $this->keyValue = $key_value;
    $this->includeTests = $include_tests;
  }

  /**
   * Gets removed hook_post_update_NAME() implementations for an extension.
   *
   * @return string[]
   *   A list of post-update functions that have been removed.
   */
  public function getRemovedPostUpdates($extension) {
    $this->scanExtensionsAndLoadUpdateFiles($extension);
    $function = "{$extension}_removed_post_updates";
    if (function_exists($function)) {
      return $function();
    }
    return [];
  }

  /**
   * Gets all available update functions.
   *
   * @return callable[]
   *   A list of update functions.
   */
  protected function getAvailableUpdateFunctions() {
    $regexp = '/^(?<extension>.+)_' . $this->updateType . '_(?<name>.+)$/';
    $functions = get_defined_functions();

    $updates = [];
    foreach (preg_grep('/_' . $this->updateType . '_/', $functions['user']) as $function) {
      // If this function is an extension update function, add it to the list of
      // extension updates.
      if (preg_match($regexp, $function, $matches)) {
        if (in_array($matches['extension'], $this->enabledExtensions)) {
          $function_name = $matches['extension'] . '_' . $this->updateType . '_' . $matches['name'];
          if ($this->updateType === 'post_update') {
            $removed = array_keys($this->getRemovedPostUpdates($matches['extension']));
            if (array_search($function_name, $removed) !== FALSE) {
              throw new RemovedPostUpdateNameException(sprintf('The following update is specified as removed in hook_removed_post_updates() but still exists in the code base: %s', $function_name));
            }
          }
          $updates[] = $function_name;
        }
      }
    }
    // Ensure that the update order is deterministic.
    sort($updates);
    return $updates;
  }

  /**
   * Find all update functions that haven't been executed.
   *
   * @return callable[]
   *   A list of update functions.
   */
  public function getPendingUpdateFunctions() {
    // We need a) the list of active extensions (we get that from the config
    // bootstrap factory) and b) the path to the extensions, we use extension
    // discovery for that.

    $this->scanExtensionsAndLoadUpdateFiles();

    // First figure out which hook_{$this->updateType}_NAME got executed
    // already.
    $existing_update_functions = $this->keyValue->get('existing_updates', []);

    $available_update_functions = $this->getAvailableUpdateFunctions();
    $not_executed_update_functions = array_diff($available_update_functions, $existing_update_functions);

    return $not_executed_update_functions;
  }

  /**
   * Loads all update files for a given list of extension.
   *
   * @param \Drupal\Core\Extension\Extension[] $extensions
   *   The extensions used for loading.
   */
  protected function loadUpdateFiles(array $extensions) {
    // Load all the {$this->updateType}.php files.
    foreach ($this->enabledExtensions as $extension) {
      if (isset($extensions[$extension])) {
        $this->loadUpdateFile($extensions[$extension]);
      }
    }
  }

  /**
   * Loads the {$this->updateType}.php file for a given extension.
   *
   * @param \Drupal\Core\Extension\Extension $extension
   *   The extension object to load its file.
   */
  protected function loadUpdateFile(Extension $extension) {
    $filename = $this->root . '/' . $extension->getPath() . '/' . $extension->getName() . ".{$this->updateType}.php";
    if (file_exists($filename)) {
      include_once $filename;
    }
  }

  /**
   * Returns a list of all the pending updates.
   *
   * @return array[]
   *   An associative array keyed by extension name which contains all
   *   information about database updates that need to be run, and any updates
   *   that are not going to proceed due to missing requirements.
   *
   *   The subarray for each extension can contain the following keys:
   *   - start: The starting update that is to be processed. If this does not
   *       exist then do not process any updates for this extension as there are
   *       other requirements that need to be resolved.
   *   - pending: An array of all the pending updates for the extension
   *       including the description from source code comment for each update
   *       function. This array is keyed by the update name.
   */
  public function getPendingUpdateInformation() {
    $functions = $this->getPendingUpdateFunctions();

    $ret = [];
    foreach ($functions as $function) {
      [$extension, $update] = explode("_{$this->updateType}_", $function);
      // The description for an update comes from its Doxygen.
      $func = new \ReflectionFunction($function);
      $description = trim(str_replace(["\n", '*', '/'], '', $func->getDocComment()), ' ');
      $ret[$extension]['pending'][$update] = $description;
      if (!isset($ret[$extension]['start'])) {
        $ret[$extension]['start'] = $update;
      }
    }
    return $ret;
  }

  /**
   * Registers that update functions were executed.
   *
   * @param string[] $function_names
   *   The executed update functions.
   *
   * @return $this
   */
  public function registerInvokedUpdates(array $function_names) {
    $executed_updates = $this->keyValue->get('existing_updates', []);
    $executed_updates = array_merge($executed_updates, $function_names);
    $this->keyValue->set('existing_updates', $executed_updates);

    return $this;
  }

  /**
   * Returns all available updates for a given extension.
   *
   * @param string $extension_name
   *   The extension name.
   *
   * @return callable[]
   *   A list of update functions.
   */
  public function getUpdateFunctions($extension_name) {
    $this->scanExtensionsAndLoadUpdateFiles($extension_name);
    $all_functions = $this->getAvailableUpdateFunctions();

    return array_filter($all_functions, function ($function_name) use ($extension_name) {
      [$function_extension_name] = explode("_{$this->updateType}_", $function_name);
      return $function_extension_name === $extension_name;
    });
  }

  /**
   * Returns all available updates for a given module.
   *
   * @param string $module_name
   *   The module name.
   *
   * @return callable[]
   *   A list of update functions.
   *
   * @deprecated in drupal:9.4.0 and is removed from drupal:10.0.0. Use
   *   \Drupal\Core\Update\UpdateRegistry::getUpdateFunctions() instead.
   *
   * @see https://www.drupal.org/node/3260162
   */
  public function getModuleUpdateFunctions($module_name) {
    @trigger_error(__CLASS__ . '\getModuleUpdateFunctions() is deprecated in drupal:9.4.0 and is removed from drupal:10.0.0. Use \Drupal\Core\Update\UpdateRegistry::getUpdateFunctions() instead. See https://www.drupal.org/node/3260162', E_USER_DEPRECATED);
    return $this->getUpdateFunctions($module_name);
  }

  /**
   * Scans all module, theme, and profile extensions and load the update files.
   *
   * @param string|null $extension
   *   (optional) Limits the extension update files loaded to the provided
   *   extension.
   */
  protected function scanExtensionsAndLoadUpdateFiles(string $extension = NULL) {
    // Scan for extensions.
    $extension_discovery = new ExtensionDiscovery($this->root, TRUE, [], $this->sitePath);
    $module_extensions = $extension_discovery->scan('module');
    $theme_extensions = $this->includeThemes() ? $extension_discovery->scan('theme') : [];
    $profile_extensions = $extension_discovery->scan('profile');
    $extensions = array_merge($module_extensions, $theme_extensions, $profile_extensions);

    // Limit to a single extension.
    if ($extension) {
      $extensions = array_intersect_key($extensions, [$extension => TRUE]);
    }

    $this->loadUpdateFiles($extensions);
  }

  /**
   * Filters out already executed update functions by extension.
   *
   * @param string $extension
   *   The extension name.
   */
  public function filterOutInvokedUpdatesByExtension(string $extension) {
    $existing_update_functions = $this->keyValue->get('existing_updates', []);

    $remaining_update_functions = array_filter($existing_update_functions, function ($function_name) use ($extension) {
      return strpos($function_name, "{$extension}_{$this->updateType}_") !== 0;
    });

    $this->keyValue->set('existing_updates', array_values($remaining_update_functions));
  }

  /**
   * Filters out already executed update functions by module.
   *
   * @param string $module
   *   The module name.
   *
   * @deprecated in drupal:9.4.0 and is removed from drupal:10.0.0. Use
   *   \Drupal\Core\Update\UpdateRegistry::filterOutInvokedUpdatesByExtension()
   *   instead.
   *
   * @see https://www.drupal.org/node/3260162
   */
  public function filterOutInvokedUpdatesByModule($module) {
    @trigger_error(__CLASS__ . '\filterOutInvokedUpdatesByModule() is deprecated in drupal:9.4.0 and is removed from drupal:10.0.0. Use \Drupal\Core\Update\UpdateRegistry::filterOutInvokedUpdatesByExtension() instead. See https://www.drupal.org/node/3260162', E_USER_DEPRECATED);
    $this->filterOutInvokedUpdatesByExtension($module);
  }

  /**
   * @return bool
   */
  protected function includeThemes(): bool {
    return $this->updateType === 'post_update';
  }

  /**
   * Processes the list of installed extensions when core.extension changes.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The Event to process.
   */
  public function onConfigSave(ConfigCrudEvent $event) {
    $config = $event->getConfig();
    if ($config->getName() === 'core.extension') {
      // Build the old extension configuration list from configuration rather
      // than using $this->enabledExtensions. This ensures that if the
      // UpdateRegistry is constructed after _drupal_maintenance_theme() has
      // added a theme to the theme handler it will not be considered as already
      // installed.
      $old_extension_list = array_keys($config->getOriginal('module') ?? []);
      $new_extension_list = array_keys($config->get('module'));
      if ($this->includeThemes()) {
        $new_extension_list = array_merge($new_extension_list, array_keys($config->get('theme')));
        $old_extension_list = array_merge($old_extension_list, array_keys($config->getOriginal('theme') ?? []));
      }

      // The list of extensions installed or uninstalled. In regular operation
      // only one of the lists will have a single value. This is because Drupal
      // can only install one extension at a time.
      $uninstalled_extensions = array_diff($old_extension_list, $new_extension_list);
      $installed_extensions = array_diff($new_extension_list, $old_extension_list);

      // Set the list of enabled extensions correctly so update function
      // discovery works as expected.
      $this->enabledExtensions = $new_extension_list;

      foreach ($uninstalled_extensions as $uninstalled_extension) {
        $this->filterOutInvokedUpdatesByExtension($uninstalled_extension);
      }
      foreach ($installed_extensions as $installed_extension) {
        // Ensure that all post_update functions are registered already. This
        // should include existing post-updates, as well as any specified as
        // having been previously removed, to ensure that newly installed and
        // updated sites have the same entries in the registry.
        $this->registerInvokedUpdates(array_merge(
          $this->getUpdateFunctions($installed_extension),
          array_keys($this->getRemovedPostUpdates($installed_extension))
        ));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ConfigEvents::SAVE][] = ['onConfigSave'];
    return $events;
  }

}
