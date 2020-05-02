<?php

namespace Drupal\Tests\migrate\Kernel;

use Drupal\migrate\MigrateExecutable;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Tests rolling back of imports.
 *
 * @group migrate
 */
class MigrateRollbackEntityConfigTest extends MigrateTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'field',
    'taxonomy',
    'text',
    'language',
    'config_translation',
    'user',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('taxonomy_vocabulary');
    $this->installEntitySchema('taxonomy_term');
    $this->installConfig(['taxonomy']);
  }

  /**
   * Tests rolling back configuration entity translations.
   */
  public function testConfigEntityRollback() {
    // We use vocabularies to demonstrate importing and rolling back
    // configuration entities with translations. First, import vocabularies.
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

    /** @var \Drupal\migrate\Plugin\Migration $vocabulary_migration */
    $vocabulary_migration = \Drupal::service('plugin.manager.migration')
      ->createStubMigration($definition);
    $vocabulary_id_map = $vocabulary_migration->getIdMap();

    $this->assertTrue($vocabulary_migration->getDestinationPlugin()
      ->supportsRollback());

    // Import and validate vocabulary config entities were created.
    $vocabulary_executable = new MigrateExecutable($vocabulary_migration, $this);
    $vocabulary_executable->import();
    foreach ($vocabulary_data_rows as $row) {
      /** @var \Drupal\taxonomy\Entity\Vocabulary $vocabulary */
      $vocabulary = Vocabulary::load($row['id']);
      $this->assertNotEmpty($vocabulary);
      $map_row = $vocabulary_id_map->getRowBySource(['id' => $row['id']]);
      $this->assertNotNull($map_row['destid1']);
    }

    // Second, import translations of the vocabulary name property.
    $vocabulary_i18n_data_rows = [
      [
        'id' => '1',
        'name' => '1',
        'language' => 'fr',
        'property' => 'name',
        'translation' => 'fr - categories',
      ],
      [
        'id' => '2',
        'name' => '2',
        'language' => 'fr',
        'property' => 'name',
        'translation' => 'fr - tags',
      ],
    ];
    $ids = [
      'id' => ['type' => 'integer'],
      'language' => ['type' => 'string'],
    ];
    $definition = [
      'id' => 'i18n_vocabularies',
      'migration_tags' => ['Import and rollback test'],
      'source' => [
        'plugin' => 'embedded_data',
        'data_rows' => $vocabulary_i18n_data_rows,
        'ids' => $ids,
        'constants' => [
          'name' => 'name',
        ],
      ],
      'process' => [
        'vid' => 'id',
        'langcode' => 'language',
        'property' => 'constants/name',
        'translation' => 'translation',
      ],
      'destination' => [
        'plugin' => 'entity:taxonomy_vocabulary',
        'translations' => 'true',
      ],
    ];

    $vocabulary_i18n__migration = \Drupal::service('plugin.manager.migration')
      ->createStubMigration($definition);
    $vocabulary_i18n_id_map = $vocabulary_i18n__migration->getIdMap();

    $this->assertTrue($vocabulary_i18n__migration->getDestinationPlugin()
      ->supportsRollback());

    // Import and validate vocabulary config entities were created.
    $vocabulary_i18n_executable = new MigrateExecutable($vocabulary_i18n__migration, $this);
    $vocabulary_i18n_executable->import();

    $language_manager = \Drupal::service('language_manager');
    foreach ($vocabulary_i18n_data_rows as $row) {
      $langcode = $row['language'];
      $id = 'taxonomy.vocabulary.' . $row['id'];
      /** @var \Drupal\language\Config\LanguageConfigOverride $config_translation */
      $config_translation = $language_manager->getLanguageConfigOverride($langcode, $id);
      $this->assertSame($row['translation'], $config_translation->get('name'));
      $map_row = $vocabulary_i18n_id_map->getRowBySource(['id' => $row['id'], 'language' => $row['language']]);
      $this->assertNotNull($map_row['destid1']);
    }

    // Perform the rollback and confirm the translation was deleted and the map
    // table row removed.
    $vocabulary_i18n_executable->rollback();
    foreach ($vocabulary_i18n_data_rows as $row) {
      $langcode = $row['language'];
      $id = 'taxonomy.vocabulary.' . $row['id'];
      /** @var \Drupal\language\Config\LanguageConfigOverride $config_translation */
      $config_translation = $language_manager->getLanguageConfigOverride($langcode, $id);
      $this->assertNull($config_translation->get('name'));
      $map_row = $vocabulary_i18n_id_map->getRowBySource(['id' => $row['id'], 'language' => $row['language']]);
      $this->assertFalse($map_row);
    }

    // Confirm the original vocabulary still exists.
    foreach ($vocabulary_data_rows as $row) {
      /** @var \Drupal\taxonomy\Entity\Vocabulary $vocabulary */
      $vocabulary = Vocabulary::load($row['id']);
      $this->assertNotEmpty($vocabulary);
      $map_row = $vocabulary_id_map->getRowBySource(['id' => $row['id']]);
      $this->assertNotNull($map_row['destid1']);
    }

  }

}
