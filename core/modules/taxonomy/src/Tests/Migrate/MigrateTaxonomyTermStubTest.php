<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Tests\Migrate\MigrateTaxonomyTermStubTest.
 */

namespace Drupal\taxonomy\Tests\Migrate;

use Drupal\migrate\Entity\Migration;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;
use Drupal\migrate_drupal\Tests\StubTestTrait;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Test stub creation for taxonomy terms.
 *
 * @group taxonomy
 */
class MigrateTaxonomyTermStubTest extends MigrateDrupalTestBase {

  use StubTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['taxonomy', 'text'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('taxonomy_term');
  }

  /**
   * Tests creation of taxonomy term stubs.
   */
  public function testStub() {
    Vocabulary::create([
      'vid' => 'test_vocabulary',
      'name' => 'Test vocabulary',
    ])->save();
    $this->performStubTest('taxonomy_term');
  }

  /**
   * Tests creation of stubs when weight is mapped.
   */
  public function testStubWithWeightMapping() {
    // Create a vocabulary via migration for the terms to reference.
    $vocabulary_data_rows = [
      ['id' => '1', 'name' => 'tags'],
    ];
    $ids = ['id' => ['type' => 'integer']];
    $config = [
      'id' => 'vocabularies',
      'migration_tags' => ['Stub test'],
      'source' => [
        'plugin' => 'embedded_data',
        'data_rows' => $vocabulary_data_rows,
        'ids' => $ids,
      ],
      'process' => [
        'vid' => 'id',
        'name' => 'name',
      ],
      'destination' => ['plugin' => 'entity:taxonomy_vocabulary'],
    ];
    $vocabulary_migration = Migration::create($config);
    $vocabulary_executable = new MigrateExecutable($vocabulary_migration, $this);
    $vocabulary_executable->import();

    // We have a term referencing an unmigrated parent, forcing a stub to be
    // created.
    $term_data_rows = [
      ['id' => '1', 'vocab' => '1', 'name' => 'music', 'parent' => '2'],
    ];
    $ids = ['id' => ['type' => 'integer']];
    $config = [
      'id' => 'terms',
      'migration_tags' => ['Import and rollback test'],
      'source' => [
        'plugin' => 'embedded_data',
        'data_rows' => $term_data_rows,
        'ids' => $ids,
      ],
      'process' => [
        'tid' => 'id',
        'vid' => 'vocab',
        'name' => 'name',
        'weight' => 'weight',
        'parent' => [
          'plugin' => 'migration',
          'migration' => 'terms',
          'source' => 'parent',
        ],
      ],
      'destination' => ['plugin' => 'entity:taxonomy_term'],
      'migration_dependencies' => ['required' => ['vocabularies']],
    ];

    $term_migration = Migration::create($config);
    $term_migration->save();
    $term_executable = new MigrateExecutable($term_migration, $this);
    $term_executable->import();
    // Load the referenced term, which should exist as a stub.
    /** @var \Drupal\Core\Entity\ContentEntityBase $stub_entity */
    $stub_entity = Term::load(2);
    $this->assertTrue($stub_entity, 'Stub successfully created');
    if ($stub_entity) {
      $this->assertIdentical(count($stub_entity->validate()), 0, 'Stub is a valid entity');
    }
  }
}
