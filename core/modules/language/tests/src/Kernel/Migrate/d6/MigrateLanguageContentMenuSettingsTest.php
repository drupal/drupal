<?php

namespace Drupal\Tests\language\Kernel\Migrate\d6;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\language\Entity\ContentLanguageSettings;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Tests migration of the ability to translate menu content.
 *
 * @group migrate_drupal_6
 */
class MigrateLanguageContentMenuSettingsTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'language',
    'content_translation',
    'menu_link_content',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Create some languages.
    ConfigurableLanguage::createFromLangcode('en')->save();
    ConfigurableLanguage::createFromLangcode('fr')->save();
    $this->executeMigrations(['d6_language_content_menu_settings']);
  }

  /**
   * Tests migration of menu translation ability.
   */
  public function testLanguageMenuContent() {
    $config = ContentLanguageSettings::load('menu_link_content.menu_link_content');
    $this->assertInstanceOf(ContentLanguageSettings::class, $config);
    $this->assertSame('menu_link_content', $config->getTargetEntityTypeId());
    $this->assertSame('menu_link_content', $config->getTargetBundle());
    $this->assertSame(LanguageInterface::LANGCODE_SITE_DEFAULT, $config->getDefaultLangcode());
    $this->assertTrue($config->isLanguageAlterable());

    // Test that menus are not alterable when the i18nmenu is not enabled.
    $this->sourceDatabase->update('system')
      ->fields(['status' => 0])
      ->condition('name', 'i18nmenu')
      ->execute();

    /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
    $migration = $this->getMigration('d6_language_content_menu_settings');
    // Indicate we're rerunning a migration that's already run.
    $migration->getIdMap()->prepareUpdate();
    $this->executeMigration($migration);

    $config = ContentLanguageSettings::load('menu_link_content.menu_link_content');
    $this->assertInstanceOf(ContentLanguageSettings::class, $config);
    $this->assertSame('menu_link_content', $config->getTargetEntityTypeId());
    $this->assertSame('menu_link_content', $config->getTargetBundle());
    $this->assertSame(LanguageInterface::LANGCODE_SITE_DEFAULT, $config->getDefaultLangcode());
    $this->assertFalse($config->isLanguageAlterable());
  }

}
