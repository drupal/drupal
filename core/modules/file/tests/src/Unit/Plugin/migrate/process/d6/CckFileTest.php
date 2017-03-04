<?php

namespace Drupal\Tests\file\Unit\Plugin\migrate\process\d6;

use Drupal\file\Plugin\migrate\process\d6\CckFile;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrateProcessInterface;
use Drupal\migrate\Row;
use Drupal\Tests\UnitTestCase;

/**
 * @group file
 */
class CckFileTest extends UnitTestCase {

  /**
   * Tests that alt and title attributes are included in transformed values.
   */
  public function testTransformAltTitle() {
    $executable = $this->prophesize(MigrateExecutableInterface::class)->reveal();
    $row = $this->prophesize(Row::class)->reveal();
    $migration = $this->prophesize(MigrationInterface::class)->reveal();

    $migration_plugin = $this->prophesize(MigrateProcessInterface::class);
    $migration_plugin->transform(1, $executable, $row, 'foo')->willReturn(1);

    $plugin = new CckFile([], 'd6_cck_file', [], $migration, $migration_plugin->reveal());

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
