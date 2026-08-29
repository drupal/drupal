<?php

declare(strict_types=1);

namespace Drupal\Core\Config;

use Drupal\Core\Cache\CacheCollectorInterface;
use Drupal\Core\Extension\Exception\UnknownExtensionException;
use Drupal\Core\Extension\ExtensionList;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * The factory class to create the config.storage.sync service.
 */
class SyncFactory {

  public function __construct(
    #[Autowire(param: 'app.root')]
    protected readonly string $root,
    protected readonly Settings $settings,
    #[Autowire(service: 'config.storage')]
    protected readonly StorageInterface $activeConfig,
    protected readonly ModuleExtensionList $moduleExtensionList,
    protected readonly ThemeExtensionList $themeExtensionList,
    #[Autowire(service: 'config.parsing_cache')]
    protected readonly CacheCollectorInterface $yamlCacheCollector,
  ) {
  }

  /**
   * Gets a StorageInterface object working with the sync config directory.
   *
   * The FileStorage object is wrapped in an AutoloadingStorage object to ensure
   * we can read configuration from new extensions.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   The storage object for working with the config sync directory.
   *
   * @throws \Drupal\Core\Config\ConfigDirectoryNotDefinedException
   *   In case the sync directory does not exist or is not defined in
   *   $settings['config_sync_directory'].
   */
  public function get(): StorageInterface {
    $directory = $this->settings->get('config_sync_directory', FALSE);
    if ($directory === FALSE) {
      throw new ConfigDirectoryNotDefinedException('The config sync directory is not defined in $settings["config_sync_directory"]');
    }
    $storage = new FileStorage($directory, StorageInterface::DEFAULT_COLLECTION, $this->yamlCacheCollector);
    return $this->wrapStorageWithAutoloadingStorage($storage);
  }

  /**
   * Wraps a StorageInterface object in an AutoloadingStorage object.
   *
   * The FileStorage object is wrapped in an AutoloadingStorage object to ensure
   * we can read configuration from new extensions.
   *
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   The storage object to wrap.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   The wrapped storage object.
   */
  public function wrapStorageWithAutoloadingStorage(StorageInterface $storage): StorageInterface {
    $storage_core_extension = $storage->read('core.extension') ?? [];
    $active_core_extension = $this->activeConfig->read('core.extension') ?? [];

    return new AutoloadingStorage(
      $storage,
      array_merge(
        $this->getNewExtensions($this->moduleExtensionList, $storage_core_extension['module'] ?? [], $active_core_extension['module'] ?? []),
        $this->getNewExtensions($this->themeExtensionList, $storage_core_extension['theme'] ?? [], $active_core_extension['theme'] ?? []),
      )
    );
  }

  /**
   * Gets a list of new extensions in the sync storage.
   *
   * @param \Drupal\Core\Extension\ExtensionList $extension_list
   *   The extension list service to use to find the extension's path.
   * @param array<string,string> $storage_extensions
   *   The list of extensions from the sync storage. The array keys are
   *   extension names.
   * @param array<string,string> $active_extensions
   *   The list of extensions from the active storage. The array keys are
   *   extension names.
   *
   * @return array<string,string>
   *   The list of extensions only in the sync storage. The array keys are the
   *   extensions names and the values are the absolute paths to the extension.
   */
  protected function getNewExtensions(ExtensionList $extension_list, array $storage_extensions, array $active_extensions): array {
    foreach (array_diff_key($storage_extensions, $active_extensions) as $name => $weight) {
      try {
        $new_extensions[$name] = $this->root . '/' . $extension_list->getPath($name);
      }
      catch (UnknownExtensionException) {
        // If the extension is missing this will surface when reading or at
        // validation time.
      }
    }
    return $new_extensions ?? [];
  }

}
