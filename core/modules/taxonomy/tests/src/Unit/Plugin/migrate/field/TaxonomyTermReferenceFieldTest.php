<?php

declare(strict_types=1);

namespace Drupal\Tests\taxonomy\Unit\Plugin\migrate\field;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\taxonomy\Plugin\migrate\field\TaxonomyTermReference;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\taxonomy\Plugin\migrate\field\TaxonomyTermReference
 * @group taxonomy
 */
class TaxonomyTermReferenceFieldTest extends UnitTestCase {

  /**
   * The migrate field plugin.
   *
   * @var \Drupal\migrate_drupal\Plugin\MigrateFieldInterface
   */
  protected $plugin;

  /**
   * The migration.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->plugin = new TaxonomyTermReference([], 'taxonomy', []);

    $migration = $this->prophesize(MigrationInterface::class);

    // The plugin's defineValueProcessPipeline() method will call
    // setProcessOfProperty() and return nothing. So, in order to examine the
    // process pipeline created by the plugin, we need to ensure that
    // getProcess() always returns the last input to setProcessOfProperty().
    $migration->setProcessOfProperty(Argument::type('string'), Argument::type('array'))
      ->will(function ($arguments) use ($migration) {
        $migration->getProcess()->willReturn($arguments[1]);
      });

    $this->migration = $migration->reveal();
  }

  /**
   * @covers ::defineValueProcessPipeline
   */
  public function testDefineValueProcessPipeline($method = 'defineValueProcessPipeline'): void {
    $this->plugin->$method($this->migration, 'field_name', []);

    $expected = [
      'plugin' => 'sub_process',
      'source' => 'field_name',
      'process' => [
        'target_id' => 'tid',
      ],
    ];
    $this->assertSame($expected, $this->migration->getProcess());
  }

}
