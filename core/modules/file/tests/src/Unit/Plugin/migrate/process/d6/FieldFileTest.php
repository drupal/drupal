<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Unit\Plugin\migrate\process\d6;

use Drupal\file\Plugin\migrate\process\d6\FieldFile;
use Drupal\migrate\MigrateLookupInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the file field process plugin.
 *
 * @group file
 */
class FieldFileTest extends UnitTestCase {

  /**
   * Tests that alt and title attributes are included in transformed values.
   */
  public function testTransformAltTitle(): void {
    $executable = $this->prophesize(MigrateExecutableInterface::class)->reveal();
    $row = $this->prophesize(Row::class)->reveal();
    $migration = $this->prophesize(MigrationInterface::class)->reveal();

    $migrate_lookup = $this->prophesize(MigrateLookupInterface::class);
    $migrate_lookup->lookup('d6_file', [1])->willReturn([['fid' => 1]]);

    $plugin = new FieldFile([], 'd6_file', [], $migration, $migrate_lookup->reveal());

    $options = [
      'alt' => 'Foo',
      'title' => 'Bar',
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
      'alt' => 'Foo',
      'title' => 'Bar',
    ];
    $this->assertSame($expected, $transformed);
  }

}
