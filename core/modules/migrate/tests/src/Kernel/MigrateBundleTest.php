<?php

namespace Drupal\Tests\migrate\Kernel;

use Drupal\migrate\MigrateExecutable;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Tests setting of bundles on content entity migrations.
 *
 * @group migrate
 */
class MigrateBundleTest extends MigrateTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['taxonomy', 'text', 'user', 'system'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('taxonomy_vocabulary');
    $this->installEntitySchema('taxonomy_term');
    $this->installConfig(['taxonomy']);
    // Set up two vocabularies (taxonomy bundles).
    Vocabulary::create(['vid' => 'tags', 'name' => 'Tags']);
    Vocabulary::create(['vid' => 'categories', 'name' => 'Categories']);
  }

  /**
   * Tests setting the bundle in the destination.
   */
  public function testDestinationBundle() {
    $term_data_rows = [
      ['id' => 1, 'name' => 'Category 1'],
    ];
    $ids = ['id' => ['type' => 'integer']];
    $definition = [
      'id' => 'terms',
      'migration_tags' => ['Bundle test'],
      'source' => [
        'plugin' => 'embedded_data',
        'data_rows' => $term_data_rows,
        'ids' => $ids,
      ],
      'process' => [
        'tid' => 'id',
        'name' => 'name',
      ],
      'destination' => [
        'plugin' => 'entity:taxonomy_term',
        'default_bundle' => 'categories',
      ],
      'migration_dependencies' => [],
    ];

    $term_migration = \Drupal::service('plugin.manager.migration')->createStubMigration($definition);

    // Import and validate the term entity was created with the correct bundle.
    $term_executable = new MigrateExecutable($term_migration, $this);
    $term_executable->import();
    /** @var \Drupal\taxonomy\Entity\Term $term */
    $term = Term::load(1);
    $this->assertEquals($term->bundle(), 'categories');
  }

  /**
   * Tests setting the bundle in the process pipeline.
   */
  public function testProcessBundle() {
    $term_data_rows = [
      ['id' => 1, 'vocab' => 'categories', 'name' => 'Category 1'],
      ['id' => 2, 'vocab' => 'tags', 'name' => 'Tag 1'],
    ];
    $ids = ['id' => ['type' => 'integer']];
    $definition = [
      'id' => 'terms',
      'migration_tags' => ['Bundle test'],
      'source' => [
        'plugin' => 'embedded_data',
        'data_rows' => $term_data_rows,
        'ids' => $ids,
      ],
      'process' => [
        'tid' => 'id',
        'vid' => 'vocab',
        'name' => 'name',
      ],
      'destination' => [
        'plugin' => 'entity:taxonomy_term',
      ],
      'migration_dependencies' => [],
    ];

    $term_migration = \Drupal::service('plugin.manager.migration')->createStubMigration($definition);

    // Import and validate the term entities were created with the correct bundle.
    $term_executable = new MigrateExecutable($term_migration, $this);
    $term_executable->import();
    /** @var \Drupal\taxonomy\Entity\Term $term */
    $term = Term::load(1);
    $this->assertEquals($term->bundle(), 'categories');
    $term = Term::load(2);
    $this->assertEquals($term->bundle(), 'tags');
  }

  /**
   * Tests setting bundles both in process and destination.
   */
  public function testMixedBundles() {
    $term_data_rows = [
      ['id' => 1, 'vocab' => 'categories', 'name' => 'Category 1'],
      ['id' => 2, 'name' => 'Tag 1'],
    ];
    $ids = ['id' => ['type' => 'integer']];
    $definition = [
      'id' => 'terms',
      'migration_tags' => ['Bundle test'],
      'source' => [
        'plugin' => 'embedded_data',
        'data_rows' => $term_data_rows,
        'ids' => $ids,
      ],
      'process' => [
        'tid' => 'id',
        'vid' => 'vocab',
        'name' => 'name',
      ],
      'destination' => [
        'plugin' => 'entity:taxonomy_term',
        // When no vocab is provided, the destination bundle is applied.
        'default_bundle' => 'tags',
      ],
      'migration_dependencies' => [],
    ];

    $term_migration = \Drupal::service('plugin.manager.migration')->createStubMigration($definition);

    // Import and validate the term entities were created with the correct bundle.
    $term_executable = new MigrateExecutable($term_migration, $this);
    $term_executable->import();
    /** @var \Drupal\taxonomy\Entity\Term $term */
    $term = Term::load(1);
    $this->assertEquals($term->bundle(), 'categories');
    $term = Term::load(2);
    $this->assertEquals($term->bundle(), 'tags');
  }

}
