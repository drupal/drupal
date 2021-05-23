<?php

namespace Drupal\Tests\language\Kernel\Migrate\d7;

use Drupal\language\Entity\ContentLanguageSettings;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests migration of language comment settings.
 *
 * @group migrate_drupal_7
 */
class MigrateLanguageContentCommentSettingsTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'comment',
    'content_translation',
    'language',
    'node',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->migrateCommentTypes();
    $this->executeMigrations([
      'language',
      'd7_language_content_comment_settings',
    ]);
  }

  /**
   * Tests migration of content language settings.
   */
  public function testLanguageCommentSettings() {
    // Article and Blog content type have multilingual settings of 'Enabled,
    // with Translation'. Assert that comments are translatable and the default
    // language is 'current_interface'.
    $config = ContentLanguageSettings::loadByEntityTypeBundle('comment', 'comment_node_article');
    $this->assertSame('comment', $config->getTargetEntityTypeId());
    $this->assertSame('comment_node_article', $config->getTargetBundle());
    $this->assertSame('current_interface', $config->getDefaultLangcode());
    $this->assertTrue($config->isLanguageAlterable());
    $third_party_settings = [
      'content_translation' => [
        'enabled' => FALSE,
      ],
    ];
    $this->assertSame($third_party_settings, $config->get('third_party_settings'));

    $config = ContentLanguageSettings::loadByEntityTypeBundle('comment', 'comment_node_blog');
    $this->assertSame('comment', $config->getTargetEntityTypeId());
    $this->assertSame('comment_node_blog', $config->getTargetBundle());
    $this->assertSame('current_interface', $config->getDefaultLangcode());
    $this->assertTrue($config->isLanguageAlterable());
    $this->assertSame($third_party_settings, $config->get('third_party_settings'));

    // Page content type has multilingual settings of 'Enabled'. Assert that
    // comments are translatable and default language is 'current_interface'.
    $config = ContentLanguageSettings::loadByEntityTypeBundle('comment', 'comment_node_page');
    $this->assertSame('comment', $config->getTargetEntityTypeId());
    $this->assertSame('comment_node_page', $config->getTargetBundle());
    $this->assertSame('current_interface', $config->getDefaultLangcode());
    $this->assertTrue($config->isLanguageAlterable());
    $this->assertSame($third_party_settings, $config->get('third_party_settings'));

    // Test content type has multilingual settings of 'Enabled, with field
    // translation'.
    $config = ContentLanguageSettings::loadByEntityTypeBundle('comment', 'comment_node_test_content_type');
    $this->assertSame('comment', $config->getTargetEntityTypeId());
    $this->assertSame('comment_node_test_content_type', $config->getTargetBundle());
    $this->assertSame('current_interface', $config->getDefaultLangcode());
    $this->assertTrue($config->isLanguageAlterable());
    $third_party_settings = [
      'content_translation' => [
        'enabled' => TRUE,
      ],
    ];
    $this->assertSame($third_party_settings, $config->get('third_party_settings'));

    // Assert that non-translatable content is not translatable and the default
    // language is 'site_default.
    $not_translatable = [
      'comment_node_book',
      'comment_forum',
    ];
    foreach ($not_translatable as $bundle) {
      $config = ContentLanguageSettings::loadByEntityTypeBundle('comment', $bundle);
      $this->assertTrue($config->isDefaultConfiguration());
      $this->assertFalse($config->isLanguageAlterable());
      $this->assertSame('site_default', $config->getDefaultLangcode(), "Default language is not 'site_default' for comment bundle $bundle");
    }
  }

}
