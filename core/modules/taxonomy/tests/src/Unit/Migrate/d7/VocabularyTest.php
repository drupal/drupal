<?php

namespace Drupal\Tests\taxonomy\Unit\Migrate\d7;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests D7 vocabulary source plugin.
 *
 * @group taxonomy
 */
class VocabularyTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = 'Drupal\taxonomy\Plugin\migrate\source\d7\Vocabulary';

  protected $migrationConfiguration = [
    'id' => 'test',
    'source' => [
      'plugin' => 'd7_vocabulary',
    ],
  ];

  protected $expectedResults = [
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

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->databaseContents['taxonomy_vocabulary'] = $this->expectedResults;
    parent::setUp();
  }

}
