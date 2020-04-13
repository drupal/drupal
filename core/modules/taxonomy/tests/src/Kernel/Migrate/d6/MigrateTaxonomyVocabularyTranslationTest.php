<?php

namespace Drupal\Tests\taxonomy\Kernel\Migrate\d6;

use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Migrate taxonomy vocabularies to taxonomy.vocabulary.*.yml.
 *
 * @group migrate_drupal_6
 */
class MigrateTaxonomyVocabularyTranslationTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'config_translation',
    'language',
    'taxonomy',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->executeMigrations([
      'd6_taxonomy_vocabulary',
      'd6_taxonomy_vocabulary_translation',
    ]);
  }

  /**
   * Tests the Drupal 6 i18n taxonomy vocabularies to Drupal 8 migration.
   */
  public function testTaxonomyVocabularyTranslation() {
    $language_manager = \Drupal::service('language_manager');
    $config = $language_manager->getLanguageConfigOverride('zu', 'taxonomy.vocabulary.vocabulary_1_i_0_');
    $this->assertSame('zu - vocabulary 1 (i=0)', $config->get('name'));
    $config = $language_manager->getLanguageConfigOverride('fr', 'taxonomy.vocabulary.vocabulary_1_i_0_');
    $this->assertSame('fr - vocabulary 1 (i=0)', $config->get('name'));
    $config = $language_manager->getLanguageConfigOverride('fr', 'taxonomy.vocabulary.vocabulary_2_i_1_');
    $this->assertSame('fr - vocabulary 2 (i=1)', $config->get('name'));
    $config = $language_manager->getLanguageConfigOverride('fr', 'taxonomy.vocabulary.vocabulary_3_i_2_');
    $this->assertSame('fr - vocabulary 3 (i=2)', $config->get('name'));
    $config = $language_manager->getLanguageConfigOverride('fr', 'taxonomy.vocabulary.vocabulary_name_much_longer_th');
    $this->assertSame('Nom de vocabulaire beaucoup plus long que trente-deux caractères', $config->get('name'));
    $config = $language_manager->getLanguageConfigOverride('fr', 'taxonomy.vocabulary.tags');
    $this->assertSame('fr - Tags', $config->get('name'));
  }

}
