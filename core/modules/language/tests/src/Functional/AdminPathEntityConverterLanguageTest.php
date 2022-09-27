<?php

namespace Drupal\Tests\language\Functional;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;

/**
 * Test administration path based conversion of entities.
 *
 * @group language
 */
class AdminPathEntityConverterLanguageTest extends BrowserTestBase {

  protected static $modules = ['language', 'language_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $permissions = [
      'access administration pages',
      'administer site configuration',
    ];
    $this->drupalLogin($this->drupalCreateUser($permissions));
    ConfigurableLanguage::createFromLangcode('es')->save();
  }

  /**
   * Tests the translated and untranslated config entities are loaded properly.
   */
  public function testConfigUsingCurrentLanguage() {
    \Drupal::languageManager()
      ->getLanguageConfigOverride('es', 'language.entity.es')
      ->set('label', 'Español')
      ->save();

    $this->drupalGet('es/admin/language_test/entity_using_current_language/es');
    $this->assertSession()->pageTextNotContains('Loaded Spanish.');
    $this->assertSession()->pageTextContains('Loaded Español.');

    $this->drupalGet('es/admin/language_test/entity_using_original_language/es');
    $this->assertSession()->pageTextContains('Loaded Spanish.');
    $this->assertSession()->pageTextNotContains('Loaded Español.');
  }

}
