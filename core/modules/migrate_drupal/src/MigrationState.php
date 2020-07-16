<?php

namespace Drupal\migrate_drupal;

use Drupal\Core\Discovery\YamlDiscovery;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\migrate_drupal\Plugin\MigrateFieldPluginManagerInterface;

/**
 * Determines the migrate state for all modules enabled on the source.
 *
 * Retrieves migrate info from *.migrate_drupal.yml files.
 *
 * Knowing which modules will be upgraded and those that will not is needed by
 * anyone upgrading a legacy Drupal version. This service provides that
 * information by analyzing the existing migrations and data in
 * migrate_drupal.yml files. Modules that are enabled or disabled in the source
 * are included in the analysis modules that are uninstalled are ignored.
 *
 * Deciding the upgrade state of a source module is a complicated task. A
 * destination module is not limited in any way to the source modules or the
 * current major version destination modules it is providing migrations for. We
 * see this in core where the Drupal 6 Menu module is upgraded by having
 * migrations in three Drupal 8 modules; menu_link_content, menu_ui and system.
 * If migrations for any of those three modules are not complete or if any of
 * them are not installed on the destination site then the Drupal 6 Menu module
 * cannot be listed as upgraded. If any one of the conditions are not met then
 * it should be listed as will not be upgraded.
 *
 * Another challenge is to ensure that legacy source modules that do not need an
 * upgrade path are handled correctly. These will not have migrations but should
 * be listed as will be upgraded, which even though there are not migrations
 * under the hood, it lets a site admin know that upgrading with this module
 * enabled is safe.
 *
 * There is not enough information in the existing system to determine the
 * correct state of the upgrade path for these, and other scenarios.
 *
 * The solution is for every destination module that is the successor to a
 * module built for a legacy Drupal version to declare the state of the upgrade
 * path(s) for the module. A module's upgrade path from a previous version may
 * consist of one or more migrations sets. Each migration set definition
 * consists of a source module supporting a legacy Drupal version, and one or
 * more current destination modules. This allows a module to indicate that a
 * provided migration set requires additional modules to be enabled in the
 * destination.
 *
 * A migration set can be marked 'finished', which indicates that all
 * migrations that are going to be provided by this destination module for this
 * migration set have been written and are complete. A migration set may also
 * be marked 'not_finished' which indicates that the module either has not
 * provided any migrations for the set, or needs to provide additional
 * migrations to complete the set. Note that other modules may still provide
 * additional finished or not_finished migrations for the same migration set.
 *
 * Modules inform the upgrade process of the migration sets by adding them to
 * their <module_name>.migrate_drupal.yml file.
 *
 * The <module_name>.migrate_drupal.yml file uses the following structure:
 *
 * # (optional) List of the source_module/destination_module(s) for the
 * #  migration sets that this module provides and are complete.
 * finished:
 *   # One or more Drupal legacy version number mappings (i.e. 6 and/or 7).
 *   6:
 *     # A mapping of legacy module machine names to either an array of modules
 *     # or a single destination module machine name to define this migration
 *     # set.
 *     <source_module_1>: <destination_module_1>
 *     <source_module_2>:
 *       - <destination_module_1>
 *       - <destination_module_2>
 *   7:
 *     <source_module_1>: <destination_module_1>
 *     <source_module_2>:
 *       - <destination_module_1>
 *       - <destination_module_2>
 * # (optional) List of the migration sets that this module provides, or will be
 * #  providing, that are incomplete or do not yet exist.
 * not_finished:
 *   6:
 *     <source_module_1>: <destination_module_1>
 *     <source_module_2>:
 *       - <destination_module_1>
 *       - <destination_module_2>
 *
 * Examples:
 *
 * @code
 * finished:
 *   6:
 *     node: node
 *   7:
 *     node: node
 *     entity_translation: node
 * not_finished:
 *   7:
 *     commerce_product: commerce_product
 *     other_module:
 *       - other_module
 *       - further_module
 * @endcode
 *
 * In this example the module has completed the upgrade path for data from the
 * Drupal 6 and Drupal 7 Node modules to the Drupal 8 Node module and for data
 * from the Drupal 7 Entity Translation module to the Drupal 8 Node module.
 *
 * @code
 * finished:
 *   6:
 *     pirate: pirate
 *   7:
 *     pirate: pirate
 * @endcode
 *
 * The Pirate module does not require an upgrade path. By declaring the upgrade
 * finished the Pirate module will be included in the finished list. That is,
 * as long as no other module has an entry "pirate: <any module name>' in its
 * not_finished section.
 */
class MigrationState {

  use MessengerTrait;
  use StringTranslationTrait;

  /**
   * Source module upgrade state when all its migrations are complete.
   *
   * @var string
   */
  const FINISHED = 'finished';

  /**
   * Source module upgrade state when all its migrations are not complete.
   *
   * @var string
   */
  const NOT_FINISHED = 'not_finished';

  /**
   * The field plugin manager service.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * The field plugin manager service.
   *
   * @var \Drupal\migrate_drupal\Plugin\MigrateFieldPluginManagerInterface
   */
  protected $fieldPluginManager;

  /**
   * An array of migration states declared for each source migration.
   *
   * States are keyed by version. Each value is an array keyed by name of the
   * source module and the value is an array of all the states declared for this
   * source module.
   *
   * @var array
   */
  protected $stateBySource;

  /**
   * An array of destinations declared for each source migration.
   *
   * Destinations are keyed by version. Each value is an array keyed by the name
   * of the source module and the value is an array of the destination modules.
   *
   * @var array
   */
  protected $declaredBySource;

  /**
   * An array of migration source and destinations derived from migrations.
   *
   * The key is the source version and the value is an array where the key is
   * the source module and the value is an array of destinations derived from
   * migration plugins.
   *
   * @var array
   */
  protected $discoveredBySource;

  /**
   * An array of migration source and destinations.
   *
   * Values are derived from migration plugins and declared states. The key is
   * the source version and the value is an array where the key is the source
   * module and the value is an array of declared or derived destinations.
   *
   * @var array
   */
  protected $destinations = [];

  /**
   * Array of enabled modules.
   *
   * @var array
   */
  protected $enabledModules = [];

  /**
   * Construct a new MigrationState object.
   *
   * @param \Drupal\migrate_drupal\Plugin\MigrateFieldPluginManagerInterface $fieldPluginManager
   *   Field plugin manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Module handler.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   String translation service.
   */
  public function __construct(MigrateFieldPluginManagerInterface $fieldPluginManager, ModuleHandlerInterface $moduleHandler, MessengerInterface $messenger, TranslationInterface $stringTranslation) {
    $this->fieldPluginManager = $fieldPluginManager;
    $this->moduleHandler = $moduleHandler;
    $this->enabledModules = array_keys($this->moduleHandler->getModuleList());
    $this->enabledModules[] = 'core';
    $this->messenger = $messenger;
    $this->stringTranslation = $stringTranslation;
  }

  /**
   * Gets the upgrade states for all enabled source modules.
   *
   * @param string $version
   *   The legacy drupal version.
   * @param array $source_system_data
   *   The data from the source site system table.
   * @param array $migrations
   *   An array of migrations.
   *
   * @return array
   *   An associative array of data with keys of state, source modules and a
   *   value which is a comma separated list of destination modules.
   */
  public function getUpgradeStates($version, array $source_system_data, array $migrations) {
    return $this->buildUpgradeState($version, $source_system_data, $migrations);
  }

  /**
   * Gets migration state information from *.migrate_drupal.yml.
   *
   * @return array
   *   An association array keyed by module of the finished and not_finished
   *   migrations for each module.
   * */
  protected function getMigrationStates() {
    // Always instantiate a new YamlDiscovery object so that we always search on
    // the up-to-date list of modules.
    $discovery = new YamlDiscovery('migrate_drupal', array_map(function (&$value) {
      return $value . '/migrations/state';
    }, $this->moduleHandler->getModuleDirectories()));
    return $discovery->findAll();
  }

  /**
   * Determines migration state for each source module enabled on the source.
   *
   * If there are no migrations for a module and no declared state the state is
   * set to NOT_FINISHED. When a module does not need any migrations, such as
   * Overlay, a state of finished is declared in system.migrate_drupal.yml.
   *
   * If there are migrations for a module the following happens. If the
   * destination module is 'core' the state is set to FINISHED. If there are
   * any occurrences of 'not_finished' in the *.migrate_drupal.yml information
   * for this source module then the state is set to NOT_FINISHED. And finally,
   * if there is an occurrence of 'finished' the state is set to FINISHED.
   *
   * @param string $version
   *   The legacy drupal version.
   * @param array $source_system_data
   *   The data from the source site system table.
   * @param array $migrations
   *   An array of migrations.
   *
   * @return array
   *   An associative array of data with keys of state, source modules and a
   *   value which is a comma separated list of destination modules.
   *   Example.
   *
   * @code
   * [
   *   'finished' => [
   *     'menu' => [
   *       'menu_link_content','menu_ui','system'
   *     ]
   *   ],
   * ]
   * @endcode
   */
  protected function buildUpgradeState($version, array $source_system_data, array $migrations) {
    // Remove core profiles from the system data.
    unset($source_system_data['module']['standard'], $source_system_data['module']['minimal']);
    $this->buildDiscoveredDestinationsBySource($version, $migrations, $source_system_data);
    $this->buildDeclaredStateBySource($version);

    $upgrade_state = [];
    // Loop through every source module that is enabled on the source site.
    foreach ($source_system_data['module'] as $module) {
      // The source plugins check requirements requires that all
      // source_modules are enabled so do the same here.
      if ($module['status']) {
        $source_module = $module['name'];
        $upgrade_state[$this->getSourceState($version, $source_module)][$source_module] = implode(', ', $this->getDestinationsForSource($version, $source_module));
      }

    }
    foreach ($upgrade_state as $key => $value) {
      ksort($upgrade_state[$key]);
    }
    return $upgrade_state;
  }

  /**
   * Builds migration source and destination module information.
   *
   * @param string $version
   *   The legacy Drupal version.
   * @param array $migrations
   *   The discovered migrations.
   * @param array $source_system_data
   *   The data from the source site system table.
   */
  protected function buildDiscoveredDestinationsBySource($version, array $migrations, array $source_system_data) {
    $discovered_upgrade_paths = [];
    $table_data = [];
    foreach ($migrations as $migration) {
      $migration_id = $migration->getPluginId();
      $source_module = $migration->getSourcePlugin()->getSourceModule();
      if (!$source_module) {
        $this->messenger()
          ->addError($this->t('Source module not found for @migration_id.', ['@migration_id' => $migration_id]));
      }
      $destination_module = $migration->getDestinationPlugin()
        ->getDestinationModule();
      if (!$destination_module) {
        $this->messenger()
          ->addError($this->t('Destination module not found for @migration_id.', ['@migration_id' => $migration_id]));
      }

      if ($source_module && $destination_module) {
        $discovered_upgrade_paths[$source_module][] = $destination_module;
        $table_data[$source_module][$destination_module][$migration_id] = $migration->label();
      }
    }

    // Add entries for the field plugins to discovered_upgrade_paths.
    $definitions = $this->fieldPluginManager->getDefinitions();
    foreach ($definitions as $definition) {
      // This is not strict so that we find field plugins with an annotation
      // where the Drupal core version is an integer and when it is a string.
      if (in_array($version, $definition['core'])) {
        $source_module = $definition['source_module'];
        $destination_module = $definition['destination_module'];
        $discovered_upgrade_paths[$source_module][] = $destination_module;
        $table_data[$source_module][$destination_module][$definition['id']] = $definition['id'];
      }
    }
    ksort($table_data);
    foreach ($table_data as $source_module => $destination_module_info) {
      ksort($table_data[$source_module]);
    }
    $this->discoveredBySource[$version] = array_map('array_unique', $discovered_upgrade_paths);
  }

  /**
   * Gets migration data from *.migrate_drupal.yml sorted by source module.
   *
   * @param string $version
   *   The legacy Drupal version.
   */
  protected function buildDeclaredStateBySource($version) {
    $migration_states = $this->getMigrationStates();

    $state_by_source = [];
    $dest_by_source = [];
    $states = [static::FINISHED, static::NOT_FINISHED];
    foreach ($migration_states as $module => $info) {
      foreach ($states as $state) {
        if (isset($info[$state][$version])) {
          foreach ($info[$state][$version] as $source => $destination) {
            // Add the state.
            $state_by_source[$source][] = $state;
            // Add the destination modules.
            $dest_by_source += [$source => []];
            $dest_by_source[$source] = array_merge($dest_by_source[$source], (array) $destination);
          }
        }
      }
    }
    $this->stateBySource[$version] = array_map('array_unique', $state_by_source);
    $this->declaredBySource[$version] = array_map('array_unique', $dest_by_source);
  }

  /**
   * Tests if a destination exists for the given source module.
   *
   * @param string $version
   *   Source version of Drupal.
   * @param string $source_module
   *   Source module.
   *
   * @return string
   *   Migration state, either 'finished' or 'not_finished'.
   */
  protected function getSourceState($version, $source_module) {
    // The state is finished only when no declarations of 'not_finished'
    // were found and each destination module is enabled.
    if (!$destinations = $this->getDestinationsForSource($version, $source_module)) {
      // No discovered or declared state.
      return MigrationState::NOT_FINISHED;
    }
    if (!isset($this->stateBySource[$version][$source_module])) {
      // No declared state.
      return MigrationState::NOT_FINISHED;
    }
    if (in_array(MigrationState::NOT_FINISHED, $this->stateBySource[$version][$source_module], TRUE) || !in_array(MigrationState::FINISHED, $this->stateBySource[$version][$source_module], TRUE)) {
      return MigrationState::NOT_FINISHED;
    }
    if (array_diff($destinations, $this->enabledModules)) {
      return MigrationState::NOT_FINISHED;
    }
    return MigrationState::FINISHED;
  }

  /**
   * Get net destinations for source module.
   *
   * @param string $version
   *   Source version.
   * @param string $source_module
   *   Source module.
   *
   * @return array
   *   Destination modules either declared by {modulename}.migrate_drupal.yml
   *   files or discovered from migration plugins.
   */
  protected function getDestinationsForSource($version, $source_module) {
    if (!isset($this->destinations[$version][$source_module])) {
      $this->discoveredBySource[$version] += [$source_module => []];
      $this->declaredBySource[$version] += [$source_module => []];
      $destination = array_unique(array_merge($this->discoveredBySource[$version][$source_module], $this->declaredBySource[$version][$source_module]));
      sort($destination);
      $this->destinations[$version][$source_module] = $destination;
    }
    return $this->destinations[$version][$source_module];

  }

}
