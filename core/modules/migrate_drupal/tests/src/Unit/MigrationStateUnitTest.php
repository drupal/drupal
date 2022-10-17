<?php

namespace Drupal\Tests\migrate_drupal\Unit;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\migrate\Plugin\MigrateDestinationInterface;
use Drupal\migrate\Plugin\MigrateSourceInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_drupal\MigrationState;
use Drupal\migrate_drupal\Plugin\MigrateFieldPluginManagerInterface;
use Drupal\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamWrapper;

/**
 * Defines a class for testing \Drupal\migrate_drupal\MigrationState.
 *
 * @group migrate_drupal
 *
 * @coversDefaultClass \Drupal\migrate_drupal\MigrationState
 */
class MigrationStateUnitTest extends UnitTestCase {

  /**
   * Tests ::getUpgradeStates.
   *
   * @dataProvider providerGetUpgradeStates
   *
   * @covers ::getUpgradeStates
   * @covers ::buildDiscoveredDestinationsBySource
   * @covers ::buildDeclaredStateBySource
   * @covers ::buildUpgradeState
   * @covers ::getMigrationStates
   * @covers ::getSourceState
   * @covers ::getDestinationsForSource
   */
  public function testGetUpgradeStates($modules_to_enable, $files, $field_plugins, $migrations, $source_system_data, $expected_7, $expected_6) {
    $fieldPluginManager = $this->prophesize(MigrateFieldPluginManagerInterface::class);
    $fieldPluginManager->getDefinitions()->willReturn($field_plugins);
    $moduleHandler = $this->prophesize(ModuleHandlerInterface::class);
    $moduleHandler->getModuleList()->willReturn($modules_to_enable);
    vfsStreamWrapper::register();
    $root = new vfsStreamDirectory('modules');
    vfsStreamWrapper::setRoot($root);
    $url = vfsStream::url('modules');

    foreach ($files as $module => $contents) {
      $path = $url . '/' . $module . '/migrations/state';
      mkdir($path, '0755', TRUE);
      file_put_contents($path . '/' . $module . '.migrate_drupal.yml', $contents);
    }
    $moduleHandler->getModuleDirectories()
      ->willReturn(array_combine(array_keys($files), array_map(function ($module) use ($url) {
        return $url . '/' . $module;
      }, array_keys($files))));
    $migrationState = new MigrationState($fieldPluginManager->reveal(), $moduleHandler->reveal(), $this->createMock(MessengerInterface::class), $this->getStringTranslationStub());

    $all_migrations = [];
    foreach ($migrations as $name => $values) {
      $migration = $this->prophesize(MigrationInterface::class);
      $source = $this->prophesize(MigrateSourceInterface::class);
      $destination = $this->prophesize(MigrateDestinationInterface::class);
      $source->getSourceModule()->willReturn($values['source_module']);
      $destination->getDestinationModule()
        ->willReturn($values['destination_module']);
      $migration->getSourcePlugin()->willReturn($source->reveal());
      $migration->getDestinationPlugin()->willReturn($destination->reveal());
      $migration->getPluginId()->willReturn($name);
      $migration->label()->willReturn($name);
      $all_migrations[] = $migration->reveal();
    }

    // Tests Drupal 7 states.
    $states = $migrationState->getUpgradeStates(7, $source_system_data, $all_migrations);
    $this->assertEquals($expected_7, $states);
    $source_system_data['module']['content'] = [
      'name' => 'content',
      'status' => TRUE,
    ];

    // Tests Drupal 6 states.
    unset($source_system_data['module']['rdf'], $source_system_data['module']['filter']);
    $states = $migrationState->getUpgradeStates(6, $source_system_data, []);
    $this->assertEquals($expected_6, $states);
  }

  /**
   * Data provider for testGetUpgradeStates.
   */
  public function providerGetUpgradeStates() {

    // Tests multiple scenarios:
    // Not enabled and not declared.
    // Destination module is not enabled.
    // Destination module not enabled.
    // Declared not finished.
    // Not finished.
    // No discovered or declared state.
    // Declared finished by one module but not finished by another.
    // Not declared and non compatible field plugin.
    // Update path not needed.
    $tests[0] = [
      'modules_to_enable' => [
        'entity_test' => [],
        'node' => [],
        'link' => [],
        'rdf' => [],
      ],
      'files' => [
        'node' => <<<NODE
finished:
  6:
    content: node
    node: node
  7:
    node: node
NODE
        ,
        'entity_test' => <<<ENTITY_TEST
not_finished:
  6:
    entity_test: entity_test
  7:
    entity_test:
      - entity_test
      - entity_test_rev
ENTITY_TEST
        ,
        'comment' => <<<COMMENT
finished:
  6:
    comment:
      - comment
      - node
  7:
    comment:
      - comment
      - node
COMMENT
        ,
        'user' => <<<USER
finished:
  6:
    user: user
  7:
    user: user
USER
        ,
        'profile' => <<<PROFILE
not_finished:
  6:
    profile: user
  7:
    profile: user
PROFILE
        ,
      ],
      'field_plugins' => [
        'datetime' => [
          'id' => 'datetime',
          'core' => [7],
          'source_module' => 'date',
          'destination_module' => 'datetime',
        ],
        'link' => [
          'id' => 'link',
          'core' => [6, 7],
          'source_module' => 'link',
          'destination_module' => 'link',
        ],
      ],
      'migrations' => [
        'rdf' => [
          'source_module' => 'rdf',
          'destination_module' => 'rdf',
        ],
        'filter' => [
          'source_module' => 'filter',
          'destination_module' => 'filter',
        ],
      ],
      'source_system_data' => [
        'module' => [
          'entity_test' => [
            'name' => 'entity_test',
            'status' => TRUE,
          ],
          'rdf' => [
            'name' => 'rdf',
            'status' => TRUE,
          ],
          'node' => [
            'name' => 'node',
            'status' => TRUE,
          ],
          'date' => [
            'name' => 'date',
            'status' => TRUE,
          ],
          'link' => [
            'name' => 'link',
            'status' => TRUE,
          ],
          'search' => [
            'name' => 'search',
            'status' => TRUE,
          ],
          'filter' => [
            'name' => 'filter',
            'status' => TRUE,
          ],
          'comment' => [
            'name' => 'comment',
            'status' => TRUE,
          ],
          'standard' => [
            'name' => 'standard',
            'status' => TRUE,
          ],
          'color' => [
            'name' => 'color',
            'status' => TRUE,
          ],
          'user' => [
            'name' => 'user',
            'status' => TRUE,
          ],
          'profile' => [
            'name' => 'profile',
            'status' => TRUE,
          ],
          // Disabled, hence ignored.
          'dblog' => [
            'name' => 'dblog',
            'status' => FALSE,
          ],
        ],
      ],

      'expected_7' => [
        MigrationState::NOT_FINISHED => [
          // Not enabled and not declared.
          'color' => '',
          // Destination module comment is not enabled.
          'comment' => 'comment, node',
          // Destination module not enabled.
          'date' => 'datetime',
          // Declared not finished.
          'entity_test' => 'entity_test, entity_test_rev',
          // Destination module not enabled.
          'filter' => 'filter',
          // Not finished.
          'profile' => 'user',
          // No discovered or declared state.
          'search' => '',
          // Declared finished by one module but not finished by another.
          'user' => 'user',
          // Enabled and not declared.
          'rdf' => 'rdf',
          'link' => 'link',
        ],
        MigrationState::FINISHED => [
          'node' => 'node',
        ],
      ],
      'expected_6' => [
        MigrationState::NOT_FINISHED => [
          // Declared not finished.
          'entity_test' => 'entity_test',
          // Destination module comment is not enabled.
          'comment' => 'comment, node',
          'user' => 'user',
          // Not finished.
          'profile' => 'user',
          // Not declared and non compatible field plugin.
          'date' => '',
          // No discovered or declared state.
          'search' => '',
          'color' => '',
          'link' => 'link',
        ],
        MigrationState::FINISHED => [
          'node' => 'node',
          'content' => 'node',
        ],
      ],
    ];

    // Test menu migration with all three required destination modules enabled.
    $tests[1] = [
      'modules_to_enable' => [
        'menu_link_content' => [],
        'menu_ui' => [],
        'system' => [],
      ],
      'files' => [
        'system' => <<<SYSTEM
finished:
  6:
    menu:
      - system
      - menu_link_content
      - menu_ui
  7:
    menu:
      - system
      - menu_link_content
      - menu_ui
SYSTEM
        ,
        'menu_link_content' => <<<MENU_LINK_CONTENT
finished:
  6:
    menu: menu_link_content
  7:
    menu: menu_link_content
MENU_LINK_CONTENT
        ,
        'menu' => <<<MENU_UI
finished:
  6:
    menu: menu_ui
  7:
    menu: menu_ui
MENU_UI
        ,
      ],
      'field_plugins' => [],
      'migrations' => [
        'system' => [
          'source_module' => 'menu',
          'destination_module' => 'system',
        ],
        'menu_ui' => [
          'source_module' => 'menu',
          'destination_module' => 'menu_ui',
        ],
        'menu_link_content' => [
          'source_module' => 'menu',
          'destination_module' => 'menu_link_content',
        ],
      ],
      'source_system_data' => [
        'module' => [
          'menu' => [
            'name' => 'menu',
            'status' => TRUE,
          ],
          'system' => [
            'name' => 'system',
            'status' => TRUE,
          ],
        ],
      ],

      'expected_7' => [
        MigrationState::NOT_FINISHED => [
          'system' => '',
        ],
        MigrationState::FINISHED => [
          'menu' => 'menu_link_content, menu_ui, system',
        ],
      ],
      'expected_6' => [
        MigrationState::NOT_FINISHED => [
          'system' => '',
          'content' => '',
        ],
        MigrationState::FINISHED => [
          'menu' => 'menu_link_content, menu_ui, system',
        ],
      ],
    ];

    // Test menu migration with menu_link_content uninstalled.
    $tests[2] = $tests[1];
    unset($tests[2]['modules_to_enable']['menu_link_content']);
    unset($tests[2]['files']['menu_link_content']);
    unset($tests[2]['migrations']['menu_link_content']);
    $tests[2]['expected_7'] = [
      MigrationState::NOT_FINISHED => [
        'menu' => 'menu_link_content, menu_ui, system',
        'system' => '',
      ],
    ];
    $tests[2]['expected_6'] = [
      MigrationState::NOT_FINISHED => [
        'menu' => 'menu_link_content, menu_ui, system',
        'system' => '',
        'content' => '',
      ],
    ];

    // Test menu migration with menu_ui uninstalled.
    $tests[3] = $tests[1];
    unset($tests[3]['modules_to_enable']['menu_ui']);
    unset($tests[3]['migrations']['menu_ui']);
    $tests[3]['expected_7'] = [
      MigrationState::NOT_FINISHED => [
        'menu' => 'menu_link_content, menu_ui, system',
        'system' => '',
      ],
    ];
    $tests[3]['expected_6'] = [
      MigrationState::NOT_FINISHED => [
        'menu' => 'menu_link_content, menu_ui, system',
        'system' => '',
        'content' => '',
      ],
    ];

    // Test an i18n migration with all three required destination modules
    // enabled.
    $tests[4] = [
      'modules_to_enable' => [
        'block' => [],
        'block_content' => [],
        'content_translation' => [],
        'system' => [],
      ],
      'files' => [
        'system' => <<<SYSTEM
finished:
  6:
    i18nblocks:
      - block
      - block_content
      - content_translation
  7:
    i18nblocks:
      - block
      - block_content
      - content_translation
SYSTEM
        ,
        'block' => <<<BLOCK
finished:
  6:
    block: block
  7:
    block: block
BLOCK
        ,
        'block_content' => <<<BLOCK_CONTENT
finished:
  6:
    block: block_content
  7:
    block: block_content
BLOCK_CONTENT
        ,
      ],
      'field_plugins' => [],
      'migrations' => [
        'block' => [
          'source_module' => 'block',
          'destination_module' => 'block',
        ],
        'block_content' => [
          'source_module' => 'block',
          'destination_module' => 'block_content',
        ],
        'i18nblocks' => [
          'source_module' => 'i18nblocks',
          'destination_module' => 'content_translation',
        ],
      ],
      'source_system_data' => [
        'module' => [
          'block' => [
            'name' => 'block',
            'status' => TRUE,
          ],
          'i18nblocks' => [
            'name' => 'i18nblocks',
            'status' => TRUE,
          ],
          'system' => [
            'name' => 'system',
            'status' => TRUE,
          ],
        ],
      ],

      'expected_7' => [
        MigrationState::NOT_FINISHED => [
          'system' => '',
        ],
        MigrationState::FINISHED => [
          'block' => 'block, block_content',
          'i18nblocks' => 'block, block_content, content_translation',
        ],
      ],
      'expected_6' => [
        MigrationState::NOT_FINISHED => [
          'system' => '',
          'content' => '',
        ],
        MigrationState::FINISHED => [
          'block' => 'block, block_content',
          'i18nblocks' => 'block, block_content, content_translation',
        ],
      ],
    ];

    // Test i18n_block  migration with block uninstalled.
    $tests[5] = $tests[4];
    unset($tests[5]['modules_to_enable']['block']);
    unset($tests[5]['files']['block']);
    unset($tests[5]['migrations']['block']);
    $tests[5]['expected_7'] = [
      MigrationState::NOT_FINISHED => [
        'system' => '',
        'i18nblocks' => 'block, block_content, content_translation',
      ],
      MigrationState::FINISHED => [
        'block' => 'block_content',
      ],
    ];
    $tests[5]['expected_6'] = [
      MigrationState::NOT_FINISHED => [
        'system' => '',
        'content' => '',
        'i18nblocks' => 'block, block_content, content_translation',
      ],
      MigrationState::FINISHED => [
        'block' => 'block_content',
      ],
    ];

    // Tests modules that don't require an upgrade path.
    $tests[6] = [
      'modules_to_enable' => [
        'system' => [],
        'content_translation' => [],
      ],
      'files' => [
        'system' => <<<SYSTEM
finished:
  6:
    help: core
    i18ncontent: content_translation
  7:
    help: core
    i18ncontent: content_translation
SYSTEM
        ,
      ],
      'field_plugins' => [],
      'migrations' => [],
      'source_system_data' => [
        'module' => [
          'system' => [
            'name' => 'system',
            'status' => TRUE,
          ],
          'help' => [
            'name' => 'help',
            'status' => TRUE,
          ],
          'i18ncontent' => [
            'name' => 'i18ncontent',
            'status' => TRUE,
          ],
        ],
      ],

      'expected_7' => [
        MigrationState::NOT_FINISHED => [
          'system' => '',
        ],
        MigrationState::FINISHED => [
          'help' => 'core',
          'i18ncontent' => 'content_translation',
        ],
      ],
      'expected_6' => [
        MigrationState::NOT_FINISHED => [
          'system' => '',
          'content' => '',
        ],
        MigrationState::FINISHED => [
          'help' => 'core',
          'i18ncontent' => 'content_translation',
        ],
      ],
    ];

    $tests[7] = $tests[6];
    unset($tests[7]['modules_to_enable']['content_translation']);
    $tests[7]['expected_7'] = [
      MigrationState::NOT_FINISHED => [
        'system' => '',
        'i18ncontent' => 'content_translation',
      ],
      MigrationState::FINISHED => [
        'help' => 'core',
      ],
    ];
    $tests[7]['expected_6'] = [
      MigrationState::NOT_FINISHED => [
        'system' => '',
        'content' => '',
        'i18ncontent' => 'content_translation',
      ],
      MigrationState::FINISHED => [
        'help' => 'core',
      ],
    ];

    return $tests;
  }

}
