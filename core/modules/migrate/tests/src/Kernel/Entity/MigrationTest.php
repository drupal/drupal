<?php

/**
 * @file
 * Contains \Drupal\Tests\migrate\Kernel\Entity\MigrationTest.
 */

namespace Drupal\Tests\migrate\Kernel\Entity;

use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate\Plugin\Migration;

/**
 * Tests the Migration entity.
 *
 * @coversDefaultClass \Drupal\migrate\Plugin\Migration
 * @group migrate
 */
class MigrationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['migrate'];

  /**
   * @todo: this should be covers, fix when dependencies are fixed.
   * @-covers ::calculateDependencies
   */
  public function testCalculateDependencies() {
    // @TODO https://www.drupal.org/node/2666640
    return;
    $fixture_migrations = [
      'd6_node__article' => 'd6_node',
      'd6_node__page' => 'd6_node',
      'd6_variables' => 'd6_variables',
    ];

    foreach ($fixture_migrations as $id => $template) {
      $definition = [
        'id' => $id,
        'template' => $template,
        'source' => [
          'plugin' => 'empty',
        ],
        'destination' => [
          'plugin' => 'null',
        ],
        'migration_tags' => []
      ];
      new Migration([], uniqid(), $definition);
    }

    $definition = [
      'migration_dependencies' => [
        'required' => [
          'd6_node:*',
          'd6_variables'
        ]
      ],
      'source' => [
        'plugin' => 'empty',
      ],
      'destination' => [
        'plugin' => 'null',
      ],
    ];

    $migration = new Migration([], uniqid(), $definition);
    $expected = [
      'migrate.migration.d6_node__article',
      'migrate.migration.d6_node__page',
      'migrate.migration.d6_variables'
    ];
    $migration->calculateDependencies();
    $this->assertEquals($expected, $migration->getDependencies()['config']);
  }

}
