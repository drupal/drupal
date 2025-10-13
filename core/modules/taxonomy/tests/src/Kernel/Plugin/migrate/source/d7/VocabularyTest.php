<?php

declare(strict_types=1);

namespace Drupal\Tests\taxonomy\Kernel\Plugin\migrate\source\d7;

use Drupal\taxonomy\Plugin\migrate\source\d7\Vocabulary;
use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests D7 vocabulary source plugin.
 */
#[CoversClass(Vocabulary::class)]
#[Group('taxonomy')]
#[RunTestsInSeparateProcesses]
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
