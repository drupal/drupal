<?php

declare(strict_types=1);

namespace Drupal\Tests\taxonomy\Kernel\Plugin\migrate\source\d7;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests D7 vocabulary source plugin.
 *
 * @covers \Drupal\taxonomy\Plugin\migrate\source\d7\Vocabulary
 * @group taxonomy
 */
class VocabularyTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['taxonomy', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public static function providerSource() {
    $tests = [];

    // The source data.
    $tests[0]['source_data']['taxonomy_vocabulary'] = [
      [
        'vid' => 1,
        'name' => 'Tags',
        'description' => 'Tags description.',
        'hierarchy' => 0,
        'module' => 'taxonomy',
        'weight' => 0,
        'machine_name' => 'tags',
      ],
      [
        'vid' => 2,
        'name' => 'Categories',
        'description' => 'Categories description.',
        'hierarchy' => 1,
        'module' => 'taxonomy',
        'weight' => 0,
        'machine_name' => 'categories',
      ],
    ];

    // The expected results.
    $tests[0]['expected_data'] = $tests[0]['source_data']['taxonomy_vocabulary'];

    return $tests;
  }

}
