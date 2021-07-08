<?php

namespace Drupal\Tests\migrate\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate\Plugin\migrate\id_map\Sql;

/**
 * Tests migrate module deprecations.
 *
 * @group migrate
 * @group legacy
 */
class MigrateDeprecationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'migrate',
    'migrate_stub_test',
  ];

  /**
   * @covers \Drupal\migrate\Plugin\migrate\id_map\NullIdMap::prepareUpdate
   */
  public function testNullIdMapPrepareUpdateDeprecation(): void {
    /** @var \Drupal\migrate\Plugin\MigratePluginManagerInterface $manager */
    $manager = $this->container->get('plugin.manager.migrate.id_map');
    $map = $manager->createInstance('null');
    $this->expectDeprecation('NullIdMap::prepareUpdate() is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. Use \Drupal\migrate\Plugin\MigrateIdMapInterface::setUpdate() with no parameter instead. See https://www.drupal.org/node/3188673');
    $map->prepareUpdate();
  }

  /**
   * @covers \Drupal\migrate\Plugin\migrate\id_map\Sql::prepareUpdate
   */
  public function testSqlPrepareUpdateDeprecation(): void {
    /** @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface $manager */
    $manager = $this->container->get('plugin.manager.migration');
    /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
    $migration = $manager->createInstance('sample_stubbing_migration');
    $map = Sql::create($this->container, [], 'sql', [], $migration);
    $this->expectDeprecation('Sql::prepareUpdate() is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. Use \Drupal\migrate\Plugin\MigrateIdMapInterface::setUpdate() with no parameter instead. See https://www.drupal.org/node/3188673');
    $map->prepareUpdate();
  }

}
