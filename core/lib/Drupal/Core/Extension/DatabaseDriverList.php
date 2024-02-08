<?php

namespace Drupal\Core\Extension;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\Exception\UnknownExtensionException;

/**
 * Provides a list of available database drivers.
 *
 * @internal
 *   This class is not yet stable and therefore there are no guarantees that the
 *   internal implementations including constructor signature and protected
 *   properties / methods will not change over time. This will be reviewed after
 *   https://www.drupal.org/project/drupal/issues/2940481
 */
class DatabaseDriverList extends ExtensionList {

  /**
   * The namespace of core's MySql database driver.
   */
  protected const CORE_MYSQL_DRIVER_NAMESPACE = 'Drupal\\mysql\\Driver\\Database\\mysql';

  /**
   * Determines whether test drivers shall be included in the discovery.
   *
   * If FALSE, all 'tests' directories are excluded from the search. If NULL,
   * it will be determined by the 'extension_discovery_scan_tests' setting.
   */
  private ?bool $includeTestDrivers = NULL;

  /**
   * Constructs a new instance.
   *
   * @param string $root
   *   The app root.
   * @param string $type
   *   The extension type.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache.
   */
  public function __construct($root, $type, CacheBackendInterface $cache) {
    $this->root = $root;
    $this->type = $type;
    $this->cache = $cache;
  }

  /**
   * Determines whether test drivers shall be included in the discovery.
   *
   * @param bool|null $includeTestDrivers
   *   Whether to include test extensions. If FALSE, all 'tests' directories
   *   are excluded in the search. If NULL, it will be determined by the
   *   'extension_discovery_scan_tests' setting.
   *
   * @return $this
   */
  public function includeTestDrivers(?bool $includeTestDrivers): static {
    $this->includeTestDrivers = $includeTestDrivers;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExtensionDiscovery() {
    return new ExtensionDiscovery($this->root, FALSE);
  }

  /**
   * {@inheritdoc}
   */
  protected function doScanExtensions() {
    return $this->getExtensionDiscovery()->scan('module', $this->includeTestDrivers);
  }

  /**
   * {@inheritdoc}
   */
  protected function doList(): array {
    // Determine the modules that contain at least one installable database
    // driver.
    $discoveredModules = $this->doScanExtensions();
    $drivers = [];
    foreach ($discoveredModules as $module) {
      $moduleDriverDirectory = $this->root . DIRECTORY_SEPARATOR . $module->getPath() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Driver' . DIRECTORY_SEPARATOR . 'Database';
      if (is_dir($moduleDriverDirectory)) {
        // Use directory iterator to avoid services.
        $directoryIterator = new \DirectoryIterator($moduleDriverDirectory);
        foreach ($directoryIterator as $fileInfo) {
          if ($fileInfo->isDir() && !$fileInfo->isDot() && file_exists($moduleDriverDirectory . DIRECTORY_SEPARATOR . $fileInfo->getFilename() . DIRECTORY_SEPARATOR . 'Install' . DIRECTORY_SEPARATOR . 'Tasks.php')) {
            $databaseDriver = new DatabaseDriver($this->root, $module, $fileInfo->getFilename(), $discoveredModules);
            $drivers[$databaseDriver->getName()] = $databaseDriver;
          }
        }
      }
    }
    return $drivers;
  }

  /**
   * Returns the list of installable database drivers.
   *
   * @return \Drupal\Core\Extension\DatabaseDriver[]
   *   An array of installable database driver extension objects.
   */
  public function getInstallableList(): array {
    $installableDrivers = [];
    foreach ($this->getList() as $name => $driver) {
      if ($driver->getInstallTasks()->installable()) {
        $installableDrivers[$name] = $driver;
      }
    }
    // Usability: unconditionally put core MySQL driver on top.
    if (isset($installableDrivers[static::CORE_MYSQL_DRIVER_NAMESPACE])) {
      $mysqlDriver = $installableDrivers[static::CORE_MYSQL_DRIVER_NAMESPACE];
      unset($installableDrivers[static::CORE_MYSQL_DRIVER_NAMESPACE]);
      $installableDrivers = [static::CORE_MYSQL_DRIVER_NAMESPACE => $mysqlDriver] + $installableDrivers;
    }
    return $installableDrivers;
  }

  /**
   * {@inheritdoc}
   */
  public function getName($extension_name) {
    throw new \LogicException(__METHOD__ . '() is not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function get($extension_name) {
    if (!str_contains($extension_name, "\\")) {
      @trigger_error("Passing a database driver name '{$extension_name}' to " . __METHOD__ . '() is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Pass a database driver namespace instead. See https://www.drupal.org/node/3258175', E_USER_DEPRECATED);
      return $this->getFromDriverName($extension_name);
    }
    return parent::get($extension_name);
  }

  /**
   * Returns the first available driver extension by the driver name.
   *
   * @param string $driverName
   *   The database driver name.
   *
   * @return \Drupal\Core\Extension\DatabaseDriver
   *   The driver extension.
   *
   * @throws \Drupal\Core\Extension\Exception\UnknownExtensionException
   *   When no matching driver extension can be found.
   *
   * @deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Use
   *   DatabaseDriverList::get() instead, passing a database driver namespace.
   *
   * @see https://www.drupal.org/node/3258175
   */
  public function getFromDriverName(string $driverName): DatabaseDriver {
    @trigger_error(__METHOD__ . '() is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Use DatabaseDriverList::get() instead, passing a database driver namespace. See https://www.drupal.org/node/3258175', E_USER_DEPRECATED);
    foreach ($this->getList() as $extensionName => $driver) {
      $namespaceParts = explode('\\', $extensionName);
      if (end($namespaceParts) === $driverName) {
        return parent::get($extensionName);
      }
    }
    throw new UnknownExtensionException("Could not find a database driver named '{$driverName}' in any module");
  }

  /**
   * {@inheritdoc}
   */
  public function getExtensionInfo($extension_name) {
    throw new \LogicException(__METHOD__ . '() is not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function getAllAvailableInfo() {
    throw new \LogicException(__METHOD__ . '() is not implemented');
  }

  /**
   * {@inheritdoc}
   */
  protected function getInstalledExtensionNames() {
    throw new \LogicException(__METHOD__ . '() is not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function getAllInstalledInfo() {
    throw new \LogicException(__METHOD__ . '() is not implemented');
  }

  /**
   * {@inheritdoc}
   */
  protected function recalculateInfo() {
    throw new \LogicException(__METHOD__ . '() is not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function getPathNames() {
    throw new \LogicException(__METHOD__ . '() is not implemented');
  }

  /**
   * {@inheritdoc}
   */
  protected function recalculatePathNames() {
    throw new \LogicException(__METHOD__ . '() is not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function setPathname($extension_name, $pathname) {
    throw new \LogicException(__METHOD__ . '() is not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function getPathname($extension_name) {
    throw new \LogicException(__METHOD__ . '() is not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function getPath($extension_name) {
    throw new \LogicException(__METHOD__ . '() is not implemented');
  }

  /**
   * {@inheritdoc}
   */
  protected function createExtensionInfo(Extension $extension) {
    throw new \LogicException(__METHOD__ . '() is not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function checkIncompatibility($name) {
    throw new \LogicException(__METHOD__ . '() is not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public static function sortByName(Extension $a, Extension $b): int {
    throw new \LogicException(__METHOD__ . '() is not implemented');
  }

}
