<?php

namespace Drupal\Tests\language\Kernel\Migrate\d7;

use Drupal\language\Entity\ContentLanguageSettings;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests migration of i18ntaxonomy vocabulary settings.
 *
 * @group migrate_drupal_7
 */
class MigrateLanguageContentTaxonomyVocabularySettingsTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'language',
    'content_translation',
    'taxonomy',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('taxonomy_term');
    $this->executeMigrations([
      'language',
      'd7_taxonomy_vocabulary',
      'd7_language_content_taxonomy_vocabulary_settings',
    ]);
  }

  /**
   * Tests migration of 18ntaxonomy vocabulary settings.
   */
  public function testLanguageContentTaxonomy() {
    $target_entity = 'taxonomy_term';
    // No multilingual options for terms, i18n_mode = 0.
    $this->assertLanguageContentSettings($target_entity, 'tags', LanguageInterface::LANGCODE_NOT_SPECIFIED, FALSE, ['enabled' => FALSE]);
    $this->assertLanguageContentSettings($target_entity, 'forums', LanguageInterface::LANGCODE_NOT_SPECIFIED, FALSE, ['enabled' => FALSE]);
    $this->assertLanguageContentSettings($target_entity, 'vocabulary_name_much_longer_th', LanguageInterface::LANGCODE_NOT_SPECIFIED, FALSE, ['enabled' => FALSE]);
    $this->assertLanguageContentSettings($target_entity, 'test_vocabulary', LanguageInterface::LANGCODE_NOT_SPECIFIED, FALSE, ['enabled' => FALSE]);
    // Localize, i18n_mode = 1.
    $this->assertLanguageContentSettings($target_entity, 'vocablocalized', LanguageInterface::LANGCODE_NOT_SPECIFIED, TRUE, ['enabled' => TRUE]);
    // Translate, i18n_mode = 4.
    $this->assertLanguageContentSettings($target_entity, 'vocabtranslate', LanguageInterface::LANGCODE_NOT_SPECIFIED, TRUE, ['enabled' => FALSE]);
    // Fixed language, i18n_mode = 2.
    $this->assertLanguageContentSettings($target_entity, 'vocabfixed', 'fr', FALSE, ['enabled' => FALSE]);
  }

  /**
   * Asserts a content language settings configuration.
   *
   * @param string $target_entity
   *   The expected target entity type.
   * @param string $bundle
   *   The expected bundle.
   * @param string $default_langcode
   *   The default language code.
   * @param bool $language_alterable
   *   The expected state of language alterable.
   * @param array $third_party_settings
   *   The content translation setting.
   */
  public function assertLanguageContentSettings($target_entity, $bundle, $default_langcode, $language_alterable, array $third_party_settings) {
    $config = ContentLanguageSettings::load($target_entity . '.' . $bundle);
    $this->assertInstanceOf(ContentLanguageSettings::class, $config);
    $this->assertSame($target_entity, $config->getTargetEntityTypeId());
    $this->assertSame($bundle, $config->getTargetBundle());
    $this->assertSame($default_langcode, $config->getDefaultLangcode());
    $this->assertSame($language_alterable, $config->isLanguageAlterable());
    $this->assertSame($third_party_settings, $config->getThirdPartySettings('content_translation'));
  }

}
