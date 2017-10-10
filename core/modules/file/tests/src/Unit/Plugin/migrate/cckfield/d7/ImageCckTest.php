<?php

namespace Drupal\Tests\file\Unit\Plugin\migrate\cckfield\d7;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\file\Plugin\migrate\cckfield\d7\ImageField;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\file\Plugin\migrate\cckfield\d7\ImageField
 * @group file
 * @group legacy
 */
class ImageCckTest extends UnitTestCase {

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
    $this->plugin = new ImageField([], 'image', []);

    $migration = $this->prophesize(MigrationInterface::class);

    // The plugin's processFieldValues() method will call
    // mergeProcessOfProperty() and return nothing. So, in order to examine the
    // process pipeline created by the plugin, we need to ensure that
    // getProcess() always returns the last input to mergeProcessOfProperty().
    $migration->mergeProcessOfProperty(Argument::type('string'), Argument::type('array'))
      ->will(function ($arguments) use ($migration) {
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
        'alt' => 'alt',
        'title' => 'title',
        'width' => 'width',
        'height' => 'height',
      ],
    ];
    $this->assertSame($expected, $this->migration->getProcess());
  }

}
