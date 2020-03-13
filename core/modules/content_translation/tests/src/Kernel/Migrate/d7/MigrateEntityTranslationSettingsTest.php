<?php

namespace Drupal\Tests\content_translation\Kernel\Migrate\d7;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests the migration of entity translation settings.
 *
 * @group migrate_drupal_7
 */
class MigrateEntityTranslationSettingsTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'comment',
    'content_translation',
    'language',
    'menu_ui',
    'node',
    'taxonomy',
    'text',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig([
      'comment',
      'content_translation',
      'node',
      'taxonomy',
      'user',
    ]);

    $this->installEntitySchema('comment');
    $this->installEntitySchema('node');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('user');

    $this->executeMigrations([
      'd7_comment_type',
      'd7_node_type',
      'd7_taxonomy_vocabulary',
      'd7_entity_translation_settings',
    ]);
  }

  /**
   * Tests entity translation settings migration.
   */
  public function testEntityTranslationSettingsMigration() {
    // Tests 'comment_node_test_content_type' entity translation settings.
    $config = $this->config('language.content_settings.comment.comment_node_test_content_type');
    $this->assertSame($config->get('target_entity_type_id'), 'comment');
    $this->assertSame($config->get('target_bundle'), 'comment_node_test_content_type');
    $this->assertSame($config->get('default_langcode'), 'current_interface');
    $this->assertFalse((bool) $config->get('language_alterable'));
    $this->assertTrue((bool) $config->get('third_party_settings.content_translation.enabled'));
    $this->assertFalse((bool) $config->get('third_party_settings.content_translation.bundle_settings.untranslatable_fields_hide'));

    // Tests 'test_content_type' entity translation settings.
    $config = $this->config('language.content_settings.node.test_content_type');
    $this->assertSame($config->get('target_entity_type_id'), 'node');
    $this->assertSame($config->get('target_bundle'), 'test_content_type');
    $this->assertSame($config->get('default_langcode'), LanguageInterface::LANGCODE_NOT_SPECIFIED);
    $this->assertTrue((bool) $config->get('language_alterable'));
    $this->assertTrue((bool) $config->get('third_party_settings.content_translation.enabled'));
    $this->assertFalse((bool) $config->get('third_party_settings.content_translation.bundle_settings.untranslatable_fields_hide'));

    // Tests 'test_vocabulary' entity translation settings.
    $config = $this->config('language.content_settings.taxonomy_term.test_vocabulary');
    $this->assertSame($config->get('target_entity_type_id'), 'taxonomy_term');
    $this->assertSame($config->get('target_bundle'), 'test_vocabulary');
    $this->assertSame($config->get('default_langcode'), LanguageInterface::LANGCODE_SITE_DEFAULT);
    $this->assertFalse((bool) $config->get('language_alterable'));
    $this->assertTrue((bool) $config->get('third_party_settings.content_translation.enabled'));
    $this->assertFalse((bool) $config->get('third_party_settings.content_translation.bundle_settings.untranslatable_fields_hide'));

    // Tests 'user' entity translation settings.
    $config = $this->config('language.content_settings.user.user');
    $this->assertSame($config->get('target_entity_type_id'), 'user');
    $this->assertSame($config->get('target_bundle'), 'user');
    $this->assertSame($config->get('default_langcode'), LanguageInterface::LANGCODE_SITE_DEFAULT);
    $this->assertFalse((bool) $config->get('language_alterable'));
    $this->assertTrue((bool) $config->get('third_party_settings.content_translation.enabled'));
    $this->assertFalse((bool) $config->get('third_party_settings.content_translation.bundle_settings.untranslatable_fields_hide'));
  }

}
