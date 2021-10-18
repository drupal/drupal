<?php

namespace Drupal\Tests\migrate_drupal\Unit\Plugin\migrate\field\d6;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\migrate_drupal\Plugin\migrate\field\NodeReference;
use Prophecy\Argument;

/**
 * Tests legacy NodeReference migrate field plugin.
 *
 * @coversDefaultClass \Drupal\migrate_drupal\Plugin\migrate\field\NodeReference
 * @group migrate_drupal
 * @group legacy
 */
class NodeReferenceFieldTest extends UnitTestCase {

  /**
   * The plugin being tested.
   *
   * @var \Drupal\migrate_drupal\Plugin\MigrateFieldInterface
   */
  protected $plugin;

  /**
   * The prophesize migration.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $migration;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->plugin = new NodeReference([], 'nodereference', []);

    $migration = $this->prophesize(MigrationInterface::class);

    $migration->setProcessOfProperty(Argument::type('string'), Argument::type('array'))
      ->will(function ($arguments) use ($migration) {
        $migration->getProcess()->willReturn($arguments[1]);
      });
    $this->migration = $migration->reveal();
  }

  /**
   * @covers ::defineValueProcessPipeline
   * @runInSeparateProcess
   */
  public function testDefineValueProcessPipeline() {
    $this->expectDeprecation('The Drupal\migrate_drupal\Plugin\migrate\field\NodeReference is deprecated in drupal:9.1.0 and will be removed from drupal:10.0.0. Instead use \Drupal\migrate_drupal\Plugin\migrate\field\d6\NodeReference. See https://www.drupal.org/node/3159537.');
    $this->plugin->defineValueProcessPipeline($this->migration, 'field_name', []);

    $expected = [
      'plugin' => 'sub_process',
      'source' => 'field_name',
      'process' => ['target_id' => 'nid'],
    ];
    $this->assertSame($expected, $this->migration->getProcess());
  }

}
