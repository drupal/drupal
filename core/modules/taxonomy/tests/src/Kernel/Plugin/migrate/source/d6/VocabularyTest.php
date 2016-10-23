<?php

namespace Drupal\Tests\taxonomy\Kernel\Plugin\migrate\source\d6;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests D6 vocabulary source plugin.
 *
 * @covers \Drupal\taxonomy\Plugin\migrate\source\d6\Vocabulary
 * @group taxonomy
 */
class VocabularyTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['taxonomy', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];

    // The source data.
    $tests[0]['source_data']['vocabulary'] = [
      [
        'vid' => 1,
        'name' => 'Tags',
        'description' => 'Tags description.',
        'help' => 1,
        'relations' => 0,
        'hierarchy' => 0,
        'multiple' => 0,
        'required' => 0,
        'tags' => 1,
        'module' => 'taxonomy',
        'weight' => 0,
      ],
      [
        'vid' => 2,
        'name' => 'Categories',
        'description' => 'Categories description.',
        'help' => 1,
        'relations' => 1,
        'hierarchy' => 1,
        'multiple' => 0,
        'required' => 1,
        'tags' => 0,
        'module' => 'taxonomy',
        'weight' => 0,
      ],
    ];
    $tests[0]['source_data']['vocabulary_node_types'] = [
      [
        'vid' => 1,
        'type' => 'page',
      ],
      [
        'vid' => 1,
        'type' => 'article',
      ],
      [
        'vid' => 2,
        'type' => 'article',
      ],
    ];

    // The expected results.
    $tests[0]['expected_data'] = [
      [
        'vid' => 1,
        'name' => 'Tags',
        'description' => 'Tags description.',
        'help' => 1,
        'relations' => 0,
        'hierarchy' => 0,
        'multiple' => 0,
        'required' => 0,
        'tags' => 1,
        'module' => 'taxonomy',
        'weight' => 0,
        'node_types' => ['page', 'article'],
        'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      ],
      [
        'vid' => 2,
        'name' => 'Categories',
        'description' => 'Categories description.',
        'help' => 1,
        'relations' => 1,
        'hierarchy' => 1,
        'multiple' => 0,
        'required' => 1,
        'tags' => 0,
        'module' => 'taxonomy',
        'weight' => 0,
        'node_types' => ['article'],
        'cardinality' => 1,
      ],
    ];

    return $tests;
  }

}
