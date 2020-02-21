<?php

namespace Drupal\Tests\language\Kernel\Migrate\d7;

use Drupal\language\Entity\ContentLanguageSettings;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests migration of i18n_menu settings.
 *
 * @group migrate_drupal_7
 */
class MigrateLanguageContentMenuSettingsTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'content_translation',
    'language',
    'menu_link_content',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->executeMigrations([
      'language',
      'd7_menu',
      'd7_language_content_menu_settings',
    ]);
  }

  /**
   * Tests migration of menu translation ability.
   */
  public function testLanguageContentMenu() {
    $target_entity = 'menu_link_content';
    // No multilingual options, i18n_mode = 0.
    $this->assertLanguageContentSettings($target_entity, 'main', LanguageInterface::LANGCODE_NOT_SPECIFIED, FALSE, ['enabled' => FALSE]);
    $this->assertLanguageContentSettings($target_entity, 'admin', LanguageInterface::LANGCODE_NOT_SPECIFIED, FALSE, ['enabled' => FALSE]);
    $this->assertLanguageContentSettings($target_entity, 'tools', LanguageInterface::LANGCODE_NOT_SPECIFIED, FALSE, ['enabled' => FALSE]);
    $this->assertLanguageContentSettings($target_entity, 'account', LanguageInterface::LANGCODE_NOT_SPECIFIED, FALSE, ['enabled' => FALSE]);
    // Translate and localize, i18n_mode = 5.
    $this->assertLanguageContentSettings($target_entity, 'menu-test-menu', LanguageInterface::LANGCODE_NOT_SPECIFIED, TRUE, ['enabled' => TRUE]);
    // Fixed language, i18n_mode = 2.
    $this->assertLanguageContentSettings($target_entity, 'menu-fixedlang', 'is', FALSE, ['enabled' => FALSE]);
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
