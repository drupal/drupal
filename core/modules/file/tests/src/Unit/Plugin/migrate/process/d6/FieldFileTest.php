<?php

namespace Drupal\Tests\file\Unit\Plugin\migrate\process\d6;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\file\Plugin\migrate\process\d6\FieldFile;
use Drupal\migrate\MigrateLookupInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrateProcessInterface;
use Drupal\migrate\Row;
use Drupal\Tests\UnitTestCase;

/**
 * @group file
 */
class FieldFileTest extends UnitTestCase {

  /**
   * Tests that alt and title attributes are included in transformed values.
   */
  public function testTransformAltTitle() {
    $executable = $this->prophesize(MigrateExecutableInterface::class)->reveal();
    $row = $this->prophesize(Row::class)->reveal();
    $migration = $this->prophesize(MigrationInterface::class)->reveal();

    $migrate_lookup = $this->prophesize(MigrateLookupInterface::class);
    $migrate_lookup->lookup('d6_file', [1])->willReturn([['fid' => 1]]);

    $plugin = new FieldFile([], 'd6_file', [], $migration, $migrate_lookup->reveal());

    $options = [
      'alt' => 'Foobaz',
      'title' => 'Wambooli',
    ];
    $value = [
      'fid' => 1,
      'list' => TRUE,
      'data' => serialize($options),
    ];

    $transformed = $plugin->transform($value, $executable, $row, 'foo');
    $expected = [
      'target_id' => 1,
      'display' => TRUE,
      'description' => '',
      'alt' => 'Foobaz',
      'title' => 'Wambooli',
    ];
    $this->assertSame($expected, $transformed);
  }

  /**
   * Tests that alt and title attributes are included in transformed values.
   *
   * @group legacy
   *
   * @expectedDeprecation Passing a migration process plugin as the fourth argument to Drupal\file\Plugin\migrate\process\d6\FieldFile::__construct is deprecated in drupal:8.8.0 and will throw an error in drupal:9.0.0. Pass the migrate.lookup service instead. See https://www.drupal.org/node/3047268
   */
  public function testLegacyTransformAltTitle() {
    $migrate_lookup = $this->prophesize(MigrateLookupInterface::class);
    $container = new ContainerBuilder();
    $container->set('migrate.lookup', $migrate_lookup->reveal());
    \Drupal::setContainer($container);

    $executable = $this->prophesize(MigrateExecutableInterface::class)->reveal();
    $row = $this->prophesize(Row::class)->reveal();
    $migration = $this->prophesize(MigrationInterface::class)->reveal();

    $migration_plugin = $this->prophesize(MigrateProcessInterface::class);
    $migration_plugin->transform(1, $executable, $row, 'foo')->willReturn(1);

    $plugin = new FieldFile([], 'd6_file', [], $migration, $migration_plugin->reveal());

    $options = [
      'alt' => 'Foobaz',
      'title' => 'Wambooli',
    ];
    $value = [
      'fid' => 1,
      'list' => TRUE,
      'data' => serialize($options),
    ];

    $transformed = $plugin->transform($value, $executable, $row, 'foo');
    $expected = [
      'target_id' => 1,
      'display' => TRUE,
      'description' => '',
      'alt' => 'Foobaz',
      'title' => 'Wambooli',
    ];
    $this->assertSame($expected, $transformed);
  }

}
