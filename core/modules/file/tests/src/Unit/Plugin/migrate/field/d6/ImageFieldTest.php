<?php

namespace Drupal\Tests\file\Unit\Plugin\migrate\field\d6;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\file\Plugin\migrate\field\d6\ImageField;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\file\Plugin\migrate\field\d6\ImageField
 * @group file
 * @group legacy
 */
class ImageFieldTest extends UnitTestCase {

  /**
   * @var \Drupal\migrate_drupal\Plugin\MigrateFieldInterface
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
   * @covers ::processFieldValues
   * @expectedDeprecation ImageField is deprecated in Drupal 8.5.x and will be removed before Drupal 9.0.x. Use \Drupal\image\Plugin\migrate\field\d6\ImageField instead. See https://www.drupal.org/node/2936061.
   */
  public function testProcessFieldValues() {
    $this->plugin->processFieldValues($this->migration, 'somefieldname', []);

    $expected = [
      'plugin' => 'd6_field_file',
      'source' => 'somefieldname',
    ];
    $this->assertSame($expected, $this->migration->getProcess());
  }

}
