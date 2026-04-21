<?php

declare(strict_types=1);

namespace Drupal\Core\Recipe;

use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Extension\ModuleExtensionList;

/**
 * Provides a read-only config storage spanning multiple modules' config.
 *
 * When the recipe runner installs modules, it puts
 * \Drupal\Core\Config\ConfigInstaller into config sync mode. In sync mode,
 * \Drupal\Core\Config\ConfigInstaller::installDefaultConfig() only installs
 * simple configuration from modules; config entities are skipped because they
 * are handled later by the recipe installer in
 * \Drupal\Core\Recipe\RecipeRunner::processConfiguration().
 *
 * This storage is used as the source storage for the config installer during
 * module installation. It combines the config/install directories of all the
 * modules being installed together, keyed by module name. It ensures that
 * configuration is only read from the module that provides the configuration
 * (based on the configuration name prefix matching the module name). This
 * prevents a module from overriding another module's configuration during a
 * multi-module install.
 *
 * @internal
 *   This API is experimental.
 *
 * @see \Drupal\Core\Recipe\RecipeRunner::installModules()
 * @see \Drupal\Core\Config\ConfigInstaller::installDefaultConfig()
 */
final class RecipeMultipleModulesConfigStorage implements StorageInterface {

  /**
   * Constructs a RecipeMultipleModulesConfigStorage.
   *
   * @param array<string, \Drupal\Core\Config\FileStorage> $fileStorages
   *   The file storages for each module, keyed by the module name.
   * @param string $collection
   *   (optional) The collection to read configuration from. Defaults to the
   *   default collection.
   */
  private function __construct(
    private readonly array $fileStorages,
    private readonly string $collection = StorageInterface::DEFAULT_COLLECTION,
  ) {
  }

  /**
   * Creates a RecipeMultipleModulesConfigStorage from a list of modules.
   *
   * @param string[] $modules
   *   The list of modules.
   * @param \Drupal\Core\Extension\ModuleExtensionList $extensionList
   *   The extension listing service.
   * @param string $collection
   *   (optional) The collection to read configuration from. Defaults to the
   *   default collection.
   *
   * @return self
   *   The RecipeMultipleModulesConfigStorage object.
   */
  public static function createFromModuleList(
    array $modules,
    ModuleExtensionList $extensionList,
    string $collection = StorageInterface::DEFAULT_COLLECTION,
  ): self {
    if (empty($modules)) {
      throw new \InvalidArgumentException('At least one module must be provided.');
    }
    // Convert the list of modules to a list of file storages keyed by the
    // module name.
    $file_storages = array_map(
      fn ($module) => new FileStorage($extensionList->get($module)->getPath() . '/' . InstallStorage::CONFIG_INSTALL_DIRECTORY, $collection),
      array_combine($modules, $modules)
    );

    return new self($file_storages, $collection);
  }

  /**
   * Gets the correct module configuration storage to use.
   *
   * @param string $name
   *   The name of a configuration object to get the storage for.
   *
   * @return \Drupal\Core\Config\FileStorage|null
   *   The storage to use.
   */
  private function getStorage(string $name): ?FileStorage {
    [$module] = explode('.', $name, 2);
    return $this->fileStorages[$module] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function exists($name): bool {
    return $this->getStorage($name)?->exists($name) ?? FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function read($name): array|false {
    return $this->getStorage($name)?->read($name) ?? FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function readMultiple(array $names): array {
    $names_by_module = [];
    foreach ($names as $name) {
      [$module] = explode('.', $name, 2);
      if (isset($this->fileStorages[$module])) {
        $names_by_module[$module][] = $name;
      }
    }

    $data = [];
    foreach ($names_by_module as $module => $name_list) {
      $data = array_merge($this->fileStorages[$module]->readMultiple($name_list), $data);
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function write($name, array $data): never {
    throw new \BadMethodCallException();
  }

  /**
   * {@inheritdoc}
   */
  public function delete($name): never {
    throw new \BadMethodCallException();
  }

  /**
   * {@inheritdoc}
   */
  public function rename($name, $new_name): never {
    throw new \BadMethodCallException();
  }

  /**
   * {@inheritdoc}
   */
  public function encode($data): string {
    return array_first($this->fileStorages)->encode($data);
  }

  /**
   * {@inheritdoc}
   */
  public function decode($raw): array {
    return array_first($this->fileStorages)->decode($raw);
  }

  /**
   * {@inheritdoc}
   */
  public function listAll($prefix = ''): array {
    // Optimization: if the prefix contains a dot only look in a single storage.
    if (str_contains($prefix, '.')) {
      [$module] = explode('.', $prefix, 2);
      return $this->getStorage($module)?->listAll($prefix) ?? [];
    }

    // If the prefix is empty or doesn't contain a dot, list all the
    // configuration in the module storages that begin with the module's name.
    $names = [];
    foreach ($this->fileStorages as $module => $fileStorage) {
      // Optimization: if the prefix does not match the module name, skip it.
      if ($prefix === '' || str_starts_with($module, $prefix)) {
        $names = array_merge($fileStorage->listAll($module . '.'), $names);
      }
    }

    if ($prefix !== '') {
      // Filter out the names that don't start with the prefix.
      $names = array_filter($names, fn (string $name) => str_starts_with($name, $prefix));
    }
    sort($names);

    return $names;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll($prefix = ''): never {
    throw new \BadMethodCallException();
  }

  /**
   * {@inheritdoc}
   */
  public function createCollection($collection): self {
    $file_storages = array_map(
      fn (FileStorage $fileStorage) => $fileStorage->createCollection($collection),
      $this->fileStorages,
    );
    return new self(
      $file_storages,
      $collection,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getAllCollectionNames(): array {
    $names = [];
    foreach ($this->fileStorages as $fileStorage) {
      $names = array_merge($names, $fileStorage->getAllCollectionNames());
    }
    return array_values(array_unique($names));
  }

  /**
   * {@inheritdoc}
   */
  public function getCollectionName(): string {
    return $this->collection;
  }

}
