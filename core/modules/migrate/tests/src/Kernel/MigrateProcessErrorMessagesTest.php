<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Kernel;

use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\Plugin\migrate\process\Get;
use Drupal\migrate\Plugin\migrate\process\SubProcess;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigratePluginManagerInterface;
use Drupal\migrate\Plugin\MigrateProcessInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * Tests the format of messages from process plugin exceptions.
 *
 * @group migrate
 */
class MigrateProcessErrorMessagesTest extends MigrateTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'migrate_events_test',
    'migrate',
  ];

  /**
   * A prophesized Process Plugin Manager.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $processPluginManager;

  /**
   * A prophesized ID Map Plugin Manager.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $idMapPluginManager;

  /**
   * A prophesized ID Map.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $idMap;

  /**
   * The default stub migration definition.
   *
   * @var array
   */
  protected array $definition = [
    'id' => 'process_errors_migration',
    'idMap' => [
      'plugin' => 'idmap_prophecy',
    ],
    'source' => [
      'plugin' => 'embedded_data',
      'data_rows' => [
        [
          'id' => 1,
          'my_property' => [
            'subfield' => [
              42,
            ],
          ],
        ],
      ],
      'ids' => ['id' => ['type' => 'integer']],
    ],
    'process' => [
      'id' => 'id',
    ],
    'destination' => [
      'plugin' => 'dummy',
    ],
    'migration_dependencies' => [],
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->processPluginManager = $this->prophesize(MigratePluginManagerInterface::class);
    $this->idMapPluginManager = $this->prophesize(MigratePluginManagerInterface::class);
    $this->idMap = $this->prophesize(MigrateIdMapInterface::class);
  }

  /**
   * Tests format of map messages saved from plugin exceptions.
   */
  public function testProcessErrorMessage(): void {
    $this->definition['process']['error']['plugin'] = 'test_error';

    $this->idMap->saveMessage(['id' => 1], "process_errors_migration:error:test_error: Process exception.", MigrationInterface::MESSAGE_ERROR)->shouldBeCalled();
    $this->setPluginManagers();

    /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
    $migration = \Drupal::service('plugin.manager.migration')->createStubMigration($this->definition);

    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  /**
   * Tests format of map messages saved from sub_process exceptions.
   *
   * This checks the format of messages that are thrown from normal process
   * plugins while being executed inside a sub_process pipeline as they
   * bubble up to the main migration.
   */
  public function testSubProcessErrorMessage(): void {
    $this->definition['process']['subprocess_error'] = [
      'plugin' => 'sub_process',
      'source' => 'my_property',
      'process' => [
        'subfield' => [
          [
            'plugin' => 'test_error',
            'value' => 'subfield',
          ],
        ],
      ],
    ];

    $this->processPluginManager->createInstance('sub_process', Argument::cetera())
      ->will(fn($x) => new SubProcess($x[1], 'sub_process', ['handle_multiples' => TRUE]));
    $this->idMap->saveMessage(['id' => 1], "process_errors_migration:subprocess_error:sub_process: test_error: Process exception.", MigrationInterface::MESSAGE_ERROR)->shouldBeCalled();
    $this->setPluginManagers();

    /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
    $migration = \Drupal::service('plugin.manager.migration')->createStubMigration($this->definition);

    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  /**
   * Prepares and sets the prophesized plugin managers.
   */
  protected function setPluginManagers() {
    $error_plugin_prophecy = $this->prophesize(MigrateProcessInterface::class);
    $error_plugin_prophecy->getPluginDefinition()->willReturn(['plugin_id' => 'test_error']);
    $error_plugin_prophecy->getPluginId()->willReturn('test_error');
    $error_plugin_prophecy->reset()->shouldBeCalled();
    $error_plugin_prophecy->transform(Argument::cetera())->willThrow(new MigrateException('Process exception.'));

    $this->processPluginManager->createInstance('get', Argument::cetera())
      ->will(fn($x) => new Get($x[1], 'get', ['handle_multiples' => TRUE]));
    $this->processPluginManager->createInstance('test_error', Argument::cetera())->willReturn($error_plugin_prophecy->reveal());

    $this->idMap->setMessage(Argument::any())->willReturn();
    $this->idMap->getRowBySource(Argument::any())->willReturn([]);
    $this->idMap->delete(Argument::cetera())->willReturn();
    $this->idMap->saveIdMapping(Argument::cetera())->willReturn();

    $this->idMapPluginManager->createInstance('idmap_prophecy', Argument::cetera())->willReturn($this->idMap->reveal());

    $this->container->set('plugin.manager.migrate.process', $this->processPluginManager->reveal());
    $this->container->set('plugin.manager.migrate.id_map', $this->idMapPluginManager->reveal());
  }

}
