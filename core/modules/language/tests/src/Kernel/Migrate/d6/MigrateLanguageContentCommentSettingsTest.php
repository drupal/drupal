<?php

namespace Drupal\Tests\language\Kernel\Migrate\d6;

use Drupal\language\Entity\ContentLanguageSettings;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Tests migration of language content comment settings.
 *
 * @group migrate_drupal_6
 */
class MigrateLanguageContentCommentSettingsTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'comment',
    'language',
    'content_translation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['comment']);
    $this->executeMigrations([
      'language',
      'd6_comment_type',
      'd6_language_content_comment_settings',
    ]);
  }

  /**
   * Tests migration of comment content language settings.
   */
  public function testLanguageCommentSettings() {
    // Article and Employee content type have multilingual settings of 'Enabled,
    // with Translation'. Assert that comments are not translatable and the
    // default language is 'current_interface'.
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

    $config = ContentLanguageSettings::loadByEntityTypeBundle('comment', 'comment_node_employee');
    $this->assertSame('comment', $config->getTargetEntityTypeId());
    $this->assertSame('comment_node_employee', $config->getTargetBundle());
    $this->assertSame('current_interface', $config->getDefaultLangcode());
    $this->assertTrue($config->isLanguageAlterable());
    $this->assertSame($third_party_settings, $config->get('third_party_settings'));

    // Sponsor content type has multilingual settings of 'Enabled'. Assert that
    // comments are not translatable and the default language is
    // 'current_interface'.
    $config = ContentLanguageSettings::loadByEntityTypeBundle('comment', 'comment_node_sponsor');
    $this->assertSame('comment', $config->getTargetEntityTypeId());
    $this->assertSame('comment_node_sponsor', $config->getTargetBundle());
    $this->assertSame('current_interface', $config->getDefaultLangcode());
    $this->assertTrue($config->isLanguageAlterable());
    $this->assertSame($third_party_settings, $config->get('third_party_settings'));

    // Assert that non-translatable content is not translatable and the default
    // language is 'site_default.
    $not_translatable = [
      'comment_node_company',
      'comment_node_event',
      'comment_node_page',
      'comment_node_story',
      'comment_node_test_event',
      'comment_node_test_page',
      'comment_node_test_planet',
      'comment_node_test_story',
      'comment_forum',
      'comment_node_event',
    ];
    foreach ($not_translatable as $bundle) {
      $config = ContentLanguageSettings::loadByEntityTypeBundle('comment', $bundle);
      $this->assertTrue($config->isDefaultConfiguration());
      $this->assertFalse($config->isLanguageAlterable());
      $this->assertSame('site_default', $config->getDefaultLangcode(), "Default language is not 'site_default' for comment bundle $bundle");
    }
  }

}
