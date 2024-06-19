<?php

declare(strict_types=1);

namespace Drupal\Tests\field\Unit\Plugin\migrate\process\d6;

use Drupal\field\Plugin\migrate\process\d6\FieldInstanceSettings;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\Tests\UnitTestCase;

// cspell:ignore imagefield

/**
 * @coversDefaultClass \Drupal\field\Plugin\migrate\process\d6\FieldInstanceSettings
 * @group field
 */
class FieldInstanceSettingsTest extends UnitTestCase {

  /**
   * @covers \Drupal\Core\Field\BaseFieldDefinition::getSettings
   *
   * @dataProvider getSettingsProvider
   */
  public function testGetSettings($field_type, $instance_settings, $expected): void {
    $instance_settings = unserialize($instance_settings);
    $migration = $this->createMock(MigrationInterface::class);
    $plugin = new FieldInstanceSettings([], 'd6_field_field_settings', []);

    $executable = $this->createMock(MigrateExecutableInterface::class);
    $row = $this->getMockBuilder(Row::class)
      ->disableOriginalConstructor()
      ->getMock();

    $result = $plugin->transform([
      $field_type,
      $instance_settings,
      NULL,
    ], $executable, $row, 'foo');
    $this->assertSame($expected, $result);
  }

  /**
   * Provides field settings for testGetSettings().
   */
  public static function getSettingsProvider() {
    return [
      'imagefield size set' => [
        'imagefield_widget',
        'a:14:{s:15:"file_extensions";s:11:"gif jpg png";s:9:"file_path";N;s:18:"progress_indicator";N;s:21:"max_filesize_per_file";s:3:"10M";s:21:"max_filesize_per_node";N;s:14:"max_resolution";N;s:14:"min_resolution";N;s:3:"alt";N;s:10:"custom_alt";i:1;s:5:"title";N;s:12:"custom_title";i:1;s:10:"title_type";N;s:13:"default_image";N;s:17:"use_default_image";N;}',
        [
          'file_extensions' => 'gif jpg png',
          'file_directory' => NULL,
          'max_filesize' => '10MB',
          'alt_field' => NULL,
          'alt_field_required' => 1,
          'title_field' => NULL,
          'title_field_required' => 1,
          'max_resolution' => '',
          'min_resolution' => '',
        ],
      ],
      'imagefield size NULL' => [
        'imagefield_widget',
        'a:14:{s:15:"file_extensions";s:11:"gif jpg png";s:9:"file_path";N;s:18:"progress_indicator";N;s:21:"max_filesize_per_file";N;s:21:"max_filesize_per_node";N;s:14:"max_resolution";N;s:14:"min_resolution";N;s:3:"alt";N;s:10:"custom_alt";i:1;s:5:"title";N;s:12:"custom_title";i:1;s:10:"title_type";N;s:13:"default_image";N;s:17:"use_default_image";N;}',
        [
          'file_extensions' => 'gif jpg png',
          'file_directory' => NULL,
          'max_filesize' => '',
          'alt_field' => NULL,
          'alt_field_required' => 1,
          'title_field' => NULL,
          'title_field_required' => 1,
          'max_resolution' => '',
          'min_resolution' => '',
        ],
      ],

    ];
  }

}
