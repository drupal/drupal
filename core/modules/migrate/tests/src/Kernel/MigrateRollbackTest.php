<?php

namespace Drupal\Tests\migrate\Kernel;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Row;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Tests rolling back of imports.
 *
 * @group migrate
 */
class MigrateRollbackTest extends MigrateTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['field', 'taxonomy', 'text'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('taxonomy_vocabulary');
    $this->installEntitySchema('taxonomy_term');
    $this->installConfig(['taxonomy']);
  }

  /**
   * Tests rolling back configuration and content entities.
   */
  public function testRollback() {
    // We use vocabularies to demonstrate importing and rolling back
    // configuration entities.
    $vocabulary_data_rows = [
      ['id' => '1', 'name' => 'categories', 'weight' => '2'],
      ['id' => '2', 'name' => 'tags', 'weight' => '1'],
    ];
    $ids = ['id' => ['type' => 'integer']];
    $definition = [
      'id' => 'vocabularies',
      'migration_tags' => ['Import and rollback test'],
      'source' => [
        'plugin' => 'embedded_data',
        'data_rows' => $vocabulary_data_rows,
        'ids' => $ids,
      ],
      'process' => [
        'vid' => 'id',
        'name' => 'name',
        'weight' => 'weight',
      ],
      'destination' => ['plugin' => 'entity:taxonomy_vocabulary'],
    ];

    $vocabulary_migration = \Drupal::service('plugin.manager.migration')->createStubMigration($definition);
    $vocabulary_id_map = $vocabulary_migration->getIdMap();

    $this->assertTrue($vocabulary_migration->getDestinationPlugin()->supportsRollback());

    // Import and validate vocabulary config entities were created.
    $vocabulary_executable = new MigrateExecutable($vocabulary_migration, $this);
    $vocabulary_executable->import();
    foreach ($vocabulary_data_rows as $row) {
      /** @var Vocabulary $vocabulary */
      $vocabulary = Vocabulary::load($row['id']);
      $this->assertTrue($vocabulary);
      $map_row = $vocabulary_id_map->getRowBySource(['id' => $row['id']]);
      $this->assertNotNull($map_row['destid1']);
    }

    // We use taxonomy terms to demonstrate importing and rolling back content
    // entities.
    $term_data_rows = [
      ['id' => '1', 'vocab' => '1', 'name' => 'music'],
      ['id' => '2', 'vocab' => '2', 'name' => 'Bach'],
      ['id' => '3', 'vocab' => '2', 'name' => 'Beethoven'],
    ];
    $ids = ['id' => ['type' => 'integer']];
    $definition = [
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
      ],
      'destination' => ['plugin' => 'entity:taxonomy_term'],
      'migration_dependencies' => ['required' => ['vocabularies']],
    ];

    $term_migration = \Drupal::service('plugin.manager.migration')->createStubMigration($definition);
    $term_id_map = $term_migration->getIdMap();

    $this->assertTrue($term_migration->getDestinationPlugin()->supportsRollback());

    // Pre-create a term, to make sure it isn't deleted on rollback.
    $preserved_term_ids[] = 1;
    $new_term = Term::create(['tid' => 1, 'vid' => 1, 'name' => 'music']);
    $new_term->save();

    // Import and validate term entities were created.
    $term_executable = new MigrateExecutable($term_migration, $this);
    $term_executable->import();
    // Also explicitly mark one row to be preserved on rollback.
    $preserved_term_ids[] = 2;
    $map_row = $term_id_map->getRowBySource(['id' => 2]);
    $dummy_row = new Row(['id' => 2], $ids);
    $term_id_map->saveIdMapping($dummy_row, [$map_row['destid1']],
      $map_row['source_row_status'], MigrateIdMapInterface::ROLLBACK_PRESERVE);

    foreach ($term_data_rows as $row) {
      /** @var Term $term */
      $term = Term::load($row['id']);
      $this->assertTrue($term);
      $map_row = $term_id_map->getRowBySource(['id' => $row['id']]);
      $this->assertNotNull($map_row['destid1']);
    }

    // Rollback and verify the entities are gone.
    $term_executable->rollback();
    foreach ($term_data_rows as $row) {
      $term = Term::load($row['id']);
      if (in_array($row['id'], $preserved_term_ids)) {
        $this->assertNotNull($term);
      }
      else {
        $this->assertNull($term);
      }
      $map_row = $term_id_map->getRowBySource(['id' => $row['id']]);
      $this->assertFalse($map_row);
    }
    $vocabulary_executable->rollback();
    foreach ($vocabulary_data_rows as $row) {
      $term = Vocabulary::load($row['id']);
      $this->assertNull($term);
      $map_row = $vocabulary_id_map->getRowBySource(['id' => $row['id']]);
      $this->assertFalse($map_row);
    }

    // Test that simple configuration is not rollbackable.
    $term_setting_rows = [
      ['id' => 1, 'override_selector' => '0', 'terms_per_page_admin' => '10'],
    ];
    $ids = ['id' => ['type' => 'integer']];
    $definition = [
      'id' => 'taxonomy_settings',
      'migration_tags' => ['Import and rollback test'],
      'source' => [
        'plugin' => 'embedded_data',
        'data_rows' => $term_setting_rows,
        'ids' => $ids,
      ],
      'process' => [
        'override_selector' => 'override_selector',
        'terms_per_page_admin' => 'terms_per_page_admin',
      ],
      'destination' => [
        'plugin' => 'config',
        'config_name' => 'taxonomy.settings',
      ],
      'migration_dependencies' => ['required' => ['vocabularies']],
    ];

    $settings_migration = \Drupal::service('plugin.manager.migration')->createStubMigration($definition);
    $this->assertFalse($settings_migration->getDestinationPlugin()->supportsRollback());
  }

}
