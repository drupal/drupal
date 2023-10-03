<?php

namespace Drupal\Core\Extension;

use Composer\Autoload\ClassLoader;
use Drupal\Core\Database\Install\Tasks;

/**
 * Defines a database driver extension object.
 */
class DatabaseDriver extends Extension {

  /**
   * The container class loader.
   */
  private ClassLoader $classLoader;

  /**
   * The install tasks object instance of the database driver.
   */
  private Tasks $installTasks;

  /**
   * Constructs a new DatabaseDriver object.
   *
   * @param string $root
   *   The app root.
   * @param \Drupal\Core\Extension\Extension $module
   *   The module containing the database driver.
   * @param string $driverName
   *   The database driver name.
   * @param \Drupal\Core\Extension\Extension[] $discoveredModules
   *   The modules discovered in the installation.
   */
  public function __construct(
    string $root,
    protected Extension $module,
    protected string $driverName,
    protected array $discoveredModules) {
    $this->root = $root;
    $this->type = 'database_driver';
  }

  /**
   * Returns the Extension object of the module containing the database driver.
   *
   * @return \Drupal\Core\Extension\Extension
   *   The Extension object of the module containing the database driver.
   */
  public function getModule(): Extension {
    return $this->module;
  }

  /**
   * Returns the name of the database driver.
   *
   * @return string
   *   The name of the database driver.
   */
  public function getDriverName(): string {
    return $this->driverName;
  }

  /**
   * Returns the PHP namespace of the database driver.
   *
   * @return string
   *   The PHP namespace of the database driver.
   */
  public function getNamespace(): string {
    return "Drupal\\" . $this->getModule()->getName() . "\\Driver\\Database\\" . $this->getDriverName();
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->getNamespace();
  }

  /**
   * {@inheritdoc}
   */
  public function getPath() {
    return $this->getModule()->getPath() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Driver' . DIRECTORY_SEPARATOR . 'Database' . DIRECTORY_SEPARATOR . $this->getDriverName();
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    if (!isset($this->classLoader)) {
      $this->classLoader = \Drupal::service('class_loader');
      $this->classLoader->addPsr4($this->getNamespace() . '\\', $this->getPath());
      foreach (($this->getAutoloadInfo()['dependencies'] ?? []) as $dependency) {
        $this->classLoader->addPsr4($dependency['namespace'] . '\\', $dependency['autoload']);
      }
    }
    return TRUE;
  }

  /**
   * Returns the install tasks object instance of this database driver.
   *
   * @return \Drupal\Core\Database\Install\Tasks
   *   The install tasks object instance.
   */
  public function getInstallTasks(): Tasks {
    if (!isset($this->installTasks)) {
      $this->load();
      $installTasksClass = $this->getNamespace() . "\\Install\\Tasks";
      $this->installTasks = new $installTasksClass();
    }
    return $this->installTasks;
  }

  // phpcs:disable
  /**
   * Returns an array with the driver's autoload information.
   *
   * The module that provides the database driver should add the driver's
   * namespace to Composer's autoloader. However, since the database connection
   * must be established before Drupal adds the module's entire namespace to the
   * autoloader, the database connection info array includes an "autoload" key
   * containing the autoload directory for the driver's namespace. For requests
   * that connect to the database via a connection info array, the value of the
   * "autoload" key is automatically added to the autoloader.
   *
   * This method can be called to find the default value of that key when the
   * database connection info array isn't available. This includes:
   * - Console commands and test runners that connect to a database specified
   *   by a database URL rather than a connection info array.
   * - During installation, prior to the connection info array being written to
   *   settings.php.
   *
   * This method returns an array with the driver's namespace and autoload
   * directory that must be added to the autoloader, as well as those of any
   * dependency specified in the driver's module.info.yml file, in the format
   * @code
   * [
   *   'autoload' => 'path_to_modules/module_a/src/Driver/Database/driver_1/',
   *   'namespace' => 'Drupal\\module_a\\Driver\\Database\\driver_1',
   *   'dependencies' => [
   *     'module_x' => [
   *       'autoload' => 'path_to_modules/module_x/src/',
   *       'namespace' => 'Drupal\\module_x',
   *     ],
   *   ],
   * ]
   * @endcode
   *
   * @return array{
   *     'autoload': string,
   *     'namespace': string,
   *     'dependencies': array<string, array{'autoload': string, 'namespace': string}>,
   *   }
   */
  // phpcs:enable
  public function getAutoloadInfo(): array {
    $this->getModuleInfo();

    $autoloadInfo = [
      'namespace' => $this->getNamespace(),
      'autoload' => $this->getPath() . DIRECTORY_SEPARATOR,
    ];

    foreach (($this->info['dependencies'] ?? []) as $dependency) {
      $dependencyData = Dependency::createFromString($dependency);
      $dependencyName = $dependencyData->getName();
      if (empty($this->discoveredModules[$dependencyName])) {
        throw new \RuntimeException(sprintf("Cannot find the module '%s' that is required by module '%s'", $dependencyName, $this->getModule()->getName()));
      }
      $autoloadInfo['dependencies'][$dependencyName] = [
        'namespace' => "Drupal\\{$dependencyName}",
        'autoload' => $this->discoveredModules[$dependencyName]->getPath() . '/src/',
      ];
    }

    return $autoloadInfo;
  }

  /**
   * {@inheritdoc}
   */
  public function isExperimental(): bool {
    $this->getModuleInfo();
    return parent::isExperimental();
  }

  /**
   * {@inheritdoc}
   */
  public function isObsolete(): bool {
    $this->getModuleInfo();
    return parent::isObsolete();
  }

  /**
   * Gets the content of the info.yml file of the driver's module, as an array.
   *
   * The info array is saved in the $info property.
   *
   * @throws \Drupal\Core\Extension\InfoParserException
   *   Exception thrown if there is a parsing error or the .info.yml file does
   *   not contain a required key.
   */
  private function getModuleInfo(): void {
    if (!isset($this->info)) {
      $infoParser = new InfoParser($this->root);
      $this->info = $infoParser->parse($this->getModule()->getPathname());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPathname() {
    throw new \LogicException(__METHOD__ . '() is not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function getFilename() {
    throw new \LogicException(__METHOD__ . '() is not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function getExtensionPathname() {
    throw new \LogicException(__METHOD__ . '() is not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function getExtensionFilename() {
    throw new \LogicException(__METHOD__ . '() is not implemented');
  }

}
