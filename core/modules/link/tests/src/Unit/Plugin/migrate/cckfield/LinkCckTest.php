<?php

namespace Drupal\Tests\link\Unit\Plugin\migrate\cckfield;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\link\Plugin\migrate\cckfield\LinkField;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\link\Plugin\migrate\cckfield\LinkField
 * @group link
 * @group legacy
 */
class LinkCckTest extends UnitTestCase {

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
    $this->plugin = new LinkField([], 'link', []);

    $migration = $this->prophesize(MigrationInterface::class);

    // The plugin's processCckFieldValues() method will call
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
      'plugin' => 'd6_cck_link',
      'source' => 'somefieldname',
    ];
    $this->assertSame($expected, $this->migration->getProcess());
  }

}
