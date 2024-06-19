<?php

declare(strict_types=1);

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
  protected static $modules = [
    'content_translation',
    'language',
    'link',
    'menu_link_content',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('menu_link_content');
    $this->executeMigrations([
      'language',
      'd7_menu',
      'd7_language_content_menu_settings',
    ]);
  }

  /**
   * Tests migration of menu translation ability.
   */
  public function testLanguageContentMenu(): void {
    $config = ContentLanguageSettings::load('menu_link_content.menu_link_content');
    $this->assertInstanceOf(ContentLanguageSettings::class, $config);
    $this->assertSame('menu_link_content', $config->getTargetEntityTypeId());
    $this->assertSame('menu_link_content', $config->getTargetBundle());
    $this->assertSame(LanguageInterface::LANGCODE_SITE_DEFAULT, $config->getDefaultLangcode());
    $this->assertTrue($config->isLanguageAlterable());
    $settings = [
      'enabled' => TRUE,
      'bundle_settings' => [
        'untranslatable_fields_hide' => '0',
      ],
    ];
    $this->assertSame($settings, $config->getThirdPartySettings('content_translation'));
  }

}
