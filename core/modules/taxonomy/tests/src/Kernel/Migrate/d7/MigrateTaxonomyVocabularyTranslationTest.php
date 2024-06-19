<?php

declare(strict_types=1);

namespace Drupal\Tests\taxonomy\Kernel\Migrate\d7;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Migrate taxonomy vocabularies to taxonomy.vocabulary.*.yml.
 *
 * @group migrate_drupal_7
 */
class MigrateTaxonomyVocabularyTranslationTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'config_translation',
    'language',
    'taxonomy',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->executeMigrations([
      'language',
      'd7_taxonomy_vocabulary',
      'd7_taxonomy_vocabulary_translation',
    ]);
  }

  /**
   * Tests the Drupal 7 i18n taxonomy vocabularies to Drupal 8 migration.
   */
  public function testTaxonomyVocabularyTranslation(): void {
    /** @var \Drupal\language\ConfigurableLanguageManagerInterface $language_manager */
    $language_manager = \Drupal::service('language_manager');

    $config_translation = $language_manager->getLanguageConfigOverride('fr', 'taxonomy.vocabulary.sujet_de_discussion');
    $this->assertNull($config_translation->get('name'));
    $this->assertNull($config_translation->get('description'));

    $config_translation = $language_manager->getLanguageConfigOverride('fr', 'taxonomy.vocabulary.tags');
    $this->assertNull($config_translation->get('name'));
    $this->assertNull($config_translation->get('description'));

    $config_translation = $language_manager->getLanguageConfigOverride('fr', 'taxonomy.vocabulary.test_vocabulary');
    $this->assertNull($config_translation->get('name'));
    $this->assertNull($config_translation->get('description'));
    $config_translation = $language_manager->getLanguageConfigOverride('is', 'taxonomy.vocabulary.test_vocabulary');
    $this->assertNull($config_translation->get('name'));
    $this->assertNull($config_translation->get('description'));

    $config_translation = $language_manager->getLanguageConfigOverride('is', 'taxonomy.vocabulary.vocabulary_name_clearly_diffe');
    $this->assertNull($config_translation->get('name'));
    $this->assertNull($config_translation->get('description'));
    $config_translation = $language_manager->getLanguageConfigOverride('fr', 'taxonomy.vocabulary.vocabulary_name_clearly_diffe');
    $this->assertNull($config_translation->get('name'));
    $this->assertNull($config_translation->get('description'));

    $config_translation = $language_manager->getLanguageConfigOverride('fr', 'taxonomy.vocabulary.vocabfixed');
    $this->assertSame('fr - VocabFixed', $config_translation->get('name'));
    $this->assertNull($config_translation->get('description'));

    $config_translation = $language_manager->getLanguageConfigOverride('fr', 'taxonomy.vocabulary.vocablocalized');
    $this->assertSame('fr - VocabLocalized', $config_translation->get('name'));
    $this->assertSame('fr - Vocabulary localize option', $config_translation->get('description'));
    $config_translation = $language_manager->getLanguageConfigOverride('is', 'taxonomy.vocabulary.vocablocalized');
    $this->assertSame('is - VocabLocalized', $config_translation->get('name'));
    $this->assertSame('is - Vocabulary localize option', $config_translation->get('description'));

    $config_translation = $language_manager->getLanguageConfigOverride('fr', 'taxonomy.vocabulary.vocabtranslate');
    $this->assertNull($config_translation->get('name'));
    $this->assertNull($config_translation->get('description'));
    $config_translation = $language_manager->getLanguageConfigOverride('is', 'taxonomy.vocabulary.vocabtranslate');
    $this->assertSame('is - VocabTranslate', $config_translation->get('name'));
    $this->assertSame('is - Vocabulary translate option', $config_translation->get('description'));
  }

}
