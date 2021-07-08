<?php

namespace Drupal\Tests\migrate\Unit;

use Drupal\migrate\Plugin\Migration;
use Drupal\migrate\Plugin\MigrationPluginManager;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\migrate\Plugin\MigrationPluginManager
 * @group migrate
 */
class MigrationPluginManagerTest extends UnitTestCase {

  /**
   * A plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManager
   */
  protected $pluginManager;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Get a plugin manager for testing.
    $module_handler = $this->createMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $cache_backend = $this->createMock('Drupal\Core\Cache\CacheBackendInterface');
    $language_manager = $this->createMock('Drupal\Core\Language\LanguageManagerInterface');
    $this->pluginManager = new MigrationPluginManager($module_handler, $cache_backend, $language_manager);
  }

  /**
   * Tests building dependencies for multiple migrations.
   *
   * @dataProvider dependencyProvider
   */
  public function testDependencyBuilding($migrations_data, $result_ids) {
    $migrations = [];
    foreach ($migrations_data as $migration_id => $migration_data) {
      $migrations[$migration_id] = new TestMigrationMock($migration_id, $migration_data['dependencies']);
    }

    $ordered_migrations = $this->pluginManager->buildDependencyMigration($migrations, []);

    // Verify results.
    $this->assertEquals($result_ids, array_keys($ordered_migrations));
    foreach ($migrations_data as $migration_id => $migration_data) {
      $migration = $migrations[$migration_id];

      $requirements = $migration_data['result_requirements'];
      if (empty($requirements)) {
        $this->assertEquals([], $migration->set);
      }
      else {
        $requirements = array_combine($requirements, $requirements);

        $this->assertCount(1, $migration->set);
        [$set_prop, $set_requirements] = reset($migration->set);
        $this->assertEquals('requirements', $set_prop);
        $this->assertEquals($requirements, $set_requirements);
      }
    }
  }

  /**
   * Provide dependency data for testing.
   */
  public function dependencyProvider() {
    return [
      // Just one migration, with no dependencies.
      [
        [
          'm1' => [
            'dependencies' => [],
            'result_requirements' => [],
          ],
        ],
        ['m1'],
      ],

      // Just one migration, with required dependencies.
      [
        [
          'm1' => [
            'dependencies' => [
              'required' => ['required1', 'required2'],
            ],
            'result_requirements' => ['required1', 'required2'],
          ],
        ],
        ['m1'],
      ],

      // Just one migration, with optional dependencies.
      [
        [
          'm1' => [
            'dependencies' => [
              'optional' => ['optional1'],
            ],
            'result_requirements' => [],
          ],
        ],
        ['m1'],
      ],

      // Multiple migrations.
      [
        [
          'm1' => [
            'dependencies' => [
              'required' => ['required1', 'required2'],
            ],
            'result_requirements' => ['required1', 'required2'],
          ],
          'm2' => [
            'dependencies' => [
              'optional' => ['optional1'],
            ],
            'result_requirements' => [],
          ],
        ],
        ['m1', 'm2'],
      ],

      // Multiple migrations, reordered due to optional requirement.
      [
        [
          'm1' => [
            'dependencies' => [
              'optional' => ['m2'],
            ],
            'result_requirements' => [],
          ],
          'm2' => [
            'dependencies' => [
              'optional' => ['optional1'],
            ],
            'result_requirements' => [],
          ],
        ],
        ['m2', 'm1'],
      ],

      // Ensure that optional requirements aren't turned into required ones,
      // if the last migration has no optional deps.
      [
        [
          'm1' => [
            'dependencies' => [
              'optional' => ['m2'],
            ],
            'result_requirements' => [],
          ],
          'm2' => [
            'dependencies' => [],
            'result_requirements' => [],
          ],
        ],
        ['m2', 'm1'],
      ],
    ];
  }

}

/**
 * A mock migration plugin.
 *
 * Why are we using a custom class here?
 *
 * 1. The function buildDependencyMigration() calls $migration->set(), which
 * is not actually in MigrationInterface.
 *
 * 2. The function buildDependencyMigration() calls array_multisort on an
 * array with mocks in it. PHPUnit mocks are really complex, and if PHP tries
 * to compare them it will die with "Nesting level too deep".
 */
class TestMigrationMock extends Migration {
  /**
   * The values passed into set().
   *
   * @var array
   */
  public $set = [];

  /**
   * TestMigrationMock constructor.
   */
  public function __construct($id, $dependencies) {
    // Intentionally ignore parent constructor.
    $this->id = $id;
    $this->dependencies = $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getMigrationDependencies() {
    return $this->dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function set($prop, $value) {
    $this->set[] = func_get_args();
  }

}
