<?php

namespace Drupal\Tests\migrate_drupal\Kernel;

use Drupal\Component\Discovery\YamlDiscovery;
use Drupal\KernelTests\FileSystemModuleDiscoveryDataProviderTrait;
use Drupal\migrate_drupal\MigrationConfigurationTrait;

/**
 * Tests that core modules have a migrate_drupal.yml file as needed.
 *
 * Checks that each module that requires a migrate_drupal.yml has the file.
 * Because more that one migrate_drupal.yml file may have the same entry the
 * ValidateMigrationStateTest, which validates the file contents, is not able
 * to determine that all the required files exits.
 *
 * @group migrate_drupal
 */
class StateFileExists extends MigrateDrupalTestBase {

  use FileSystemModuleDiscoveryDataProviderTrait;
  use MigrationConfigurationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    // Test migrations states.
    'migrate_state_finished_test',
    'migrate_state_not_finished_test',
  ];

  /**
   * Modules that should have a migrate_drupal.yml file.
   *
   * @var array
   */
  protected $stateFileRequired = [
    'action',
    'aggregator',
    'ban',
    'block',
    'block_content',
    'book',
    'color',
    'comment',
    'config_translation',
    'contact',
    'content_translation',
    'datetime',
    'dblog',
    'field',
    'file',
    'filter',
    'forum',
    'image',
    'language',
    'link',
    'locale',
    'menu_link_content',
    'migrate_state_finished_test',
    'migrate_state_not_finished_test',
    'menu_ui',
    'migrate_drupal',
    'node',
    'options',
    'path',
    'rdf',
    'search',
    'shortcut',
    'statistics',
    'syslog',
    'system',
    'taxonomy',
    'telephone',
    'text',
    'tracker',
    'update',
    'user',
  ];

  /**
   * Tests that the migrate_drupal.yml files exist as needed.
   */
  public function testMigrationState() {
    // Install all available modules.
    $module_handler = $this->container->get('module_handler');
    $all_modules = $this->coreModuleListDataProvider();
    $modules_enabled = $module_handler->getModuleList();
    $modules_to_enable = array_keys(array_diff_key($all_modules, $modules_enabled));
    $this->enableModules($modules_to_enable);

    // Modules with a migrate_drupal.yml file.
    $has_state_file = (new YamlDiscovery('migrate_drupal', array_map(function (&$value) {
      return $value . '/migrations/state';
    }, $module_handler->getModuleDirectories())))->findAll();

    foreach ($this->stateFileRequired as $module) {
      $this->assertArrayHasKey($module, $has_state_file, sprintf("Module '%s' should have a migrate_drupal.yml file", $module));
    }
    $this->assertEquals(count($this->stateFileRequired), count($has_state_file));
  }

}
