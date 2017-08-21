<?php

namespace Drupal\Tests\file\Unit\Plugin\migrate\cckfield\d7;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\Tests\UnitTestCase;
use Drupal\file\Plugin\migrate\cckfield\d7\FileField;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\file\Plugin\migrate\cckfield\d7\FileField
 * @group file
 * @group legacy
 */
class FileCckTest extends UnitTestCase {

  /**
   * @var \Drupal\migrate_drupal\Plugin\MigrateCckFieldInterface
   */
  protected $plugin;

  /**
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->plugin = new FileField([], 'file', []);

    $migration = $this->prophesize(MigrationInterface::class);

    // The plugin's processFieldValues() method will call
    // mergeProcessOfProperty() and return nothing. So, in order to examine the
    // process pipeline created by the plugin, we need to ensure that
    // getProcess() always returns the last input to mergeProcessOfProperty().
    $migration->mergeProcessOfProperty(Argument::type('string'), Argument::type('array'))
      ->will(function($arguments) use ($migration) {
        $migration->getProcess()->willReturn($arguments[1]);
      });
    $this->migration = $migration->reveal();
  }

  /**
   * @covers ::processCckFieldValues
   */
  public function testProcessCckFieldValues() {
    $this->plugin->processCckFieldValues($this->migration, 'somefieldname', []);

    $expected = [
      'plugin' => 'iterator',
      'source' => 'somefieldname',
      'process' => [
        'target_id' => 'fid',
        'display' => 'display',
        'description' => 'description',
      ],
    ];
    $this->assertSame($expected, $this->migration->getProcess());
  }

  /**
   * Data provider for testGetFieldType().
   */
  public function getFieldTypeProvider() {
    return [
      ['image', 'imagefield_widget'],
      ['file', 'filefield_widget'],
      ['file', 'x_widget']
    ];
  }

  /**
   * @covers ::getFieldType
   * @dataProvider getFieldTypeProvider
   */
  public function testGetFieldType($expected_type, $widget_type, array $settings = []) {
    $row = new Row();
    $row->setSourceProperty('widget_type', $widget_type);
    $row->setSourceProperty('global_settings', $settings);
    $this->assertSame($expected_type, $this->plugin->getFieldType($row));
  }

}
