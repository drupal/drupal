<?php

/**
 * @file
 * Contains \Drupal\Tests\migrate\Kernel\Entity\MigrationTest.
 */

namespace Drupal\Tests\migrate\Kernel\Entity;

use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate\Entity\Migration;

/**
 * Tests the Migration entity.
 *
 * @coversDefaultClass \Drupal\migrate\Entity\Migration
 * @group migrate
 */
class MigrationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['migrate'];

  /**
   * @covers ::calculateDependencies
   */
  public function testCalculateDependencies() {
    $fixture_migrations = [
      'd6_node__article' => 'd6_node',
      'd6_node__page' => 'd6_node',
      'd6_variables' => 'd6_variables',
    ];

    foreach ($fixture_migrations as $id => $template) {
      $values = [
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
      Migration::create($values)->save();
    }

    $values = [
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

    $migration = new Migration($values, 'migration');
    $expected = [
      'migrate.migration.d6_node__article',
      'migrate.migration.d6_node__page',
      'migrate.migration.d6_variables'
    ];
    $migration->calculateDependencies();
    $this->assertEquals($expected, $migration->getDependencies()['config']);
  }

}
