<?php

namespace Drupal\Tests\link\Unit\Plugin\migrate\field\d6;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\link\Plugin\migrate\field\d6\LinkField;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\link\Plugin\migrate\field\d6\LinkField
 * @group link
 */
class LinkFieldTest extends UnitTestCase {

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
  protected function setUp(): void {
    $this->plugin = new LinkField([], 'link', []);

    $migration = $this->prophesize(MigrationInterface::class);

    // The plugin's defineValueProcessPipeline() method will call
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
   * @covers ::defineValueProcessPipeline
   */
  public function testDefineValueProcessPipeline($method = 'defineValueProcessPipeline') {
    $this->plugin->$method($this->migration, 'field_name', []);

    $expected = [
      'plugin' => 'field_link',
      'source' => 'field_name',
    ];
    $this->assertSame($expected, $this->migration->getProcess());
  }

}
