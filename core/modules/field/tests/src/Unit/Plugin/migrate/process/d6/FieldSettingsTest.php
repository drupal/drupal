<?php

/**
 * @file
 * Contains \Drupal\Tests\field\Unit\Plugin\migrate\process\d6\FieldSettingsTest.
 */

namespace Drupal\Tests\field\Unit\Plugin\migrate\process\d6;

use Drupal\field\Plugin\migrate\process\d6\FieldSettings;
use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\field\Plugin\migrate\process\d6\FieldSettings
 * @group field
 */
class FieldSettingsTest extends UnitTestCase {

  /**
   * @covers ::getSettings
   *
   * @dataProvider getSettingsProvider
   */
  public function testGetSettings($field_type, $field_settings, $allowed_values) {
    $migration = $this->getMock(MigrationInterface::class);
    $plugin = new FieldSettings([], 'd6_field_settings', [], $migration);

    $executable = $this->getMock(MigrateExecutableInterface::class);
    $row = $this->getMockBuilder(Row::class)
      ->disableOriginalConstructor()
      ->getMock();

    $result = $plugin->transform([$field_type, $field_settings], $executable, $row, 'foo');
    $this->assertSame($allowed_values, $result['allowed_values']);
  }

  /**
   * Provides field settings for testGetSettings().
   */
  public function getSettingsProvider() {
    return array(
      array(
        'list_integer',
        array('allowed_values' => "1|One\n2|Two\n3"),
        array(
          '1' => 'One',
          '2' => 'Two',
          '3' => '3',
        ),
      ),
      array(
        'list_string',
        array('allowed_values' => NULL),
        array(),
      ),
      array(
        'list_float',
        array('allowed_values' => ""),
        array(),
      ),
      array(
        'boolean',
        array(),
        array(),
      ),
    );
  }

}
