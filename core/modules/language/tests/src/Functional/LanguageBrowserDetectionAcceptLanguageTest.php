<?php

declare(strict_types=1);

namespace Drupal\Tests\language\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests browser language detection with different accept-language headers.
 *
 * @group language
 */
class LanguageBrowserDetectionAcceptLanguageTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'language',
    'locale',
    'content_translation',
    'system_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // User to manage languages.
    $admin = $this->drupalCreateUser([], NULL, TRUE);
    $this->drupalLogin($admin);

    // Create FR.
    ConfigurableLanguage::createFromLangcode('fr')->save();
    // Set language detection to URL and browser detection.
    $this->drupalGet('/admin/config/regional/language/detection');
    $this->submitForm([
      'language_interface[enabled][language-url]' => TRUE,
      'language_interface[enabled][language-browser]' => TRUE,
      'language_interface[enabled][language-selected]' => TRUE,
    ], 'Save settings');

    // Set prefixes to en and fr.
    $this->drupalGet('/admin/config/regional/language/detection/url');
    $this->submitForm([
      'prefix[en]' => 'en',
      'prefix[fr]' => 'fr',
    ], 'Save configuration');
    // Add language codes to browser detection.
    $this->drupalGet('/admin/config/regional/language/detection/browser');
    $this->submitForm([
      'new_mapping[browser_langcode]' => 'fr',
      'new_mapping[drupal_langcode]' => 'fr',
    ], 'Save configuration');
    $this->drupalGet('/admin/config/regional/language/detection/browser');
    $this->submitForm([
      'new_mapping[browser_langcode]' => 'en',
      'new_mapping[drupal_langcode]' => 'en',
    ], 'Save configuration');
    $this->drupalGet('/admin/config/regional/language/detection/selected');
    $this->submitForm(['edit-selected-langcode' => 'en'], 'Save configuration');

    $this->drupalLogout();
  }

  /**
   * Tests with browsers with and without Accept-Language header.
   */
  public function testAcceptLanguageEmptyDefault(): void {

    // Check correct headers.
    $this->drupalGet('/en/system-test/echo/language test', [], ['Accept-Language' => 'en']);
    $this->assertSession()->responseHeaderEquals('Content-Language', 'en');
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'MISS');

    $this->drupalGet('/fr/system-test/echo/language test', [], ['Accept-Language' => 'en']);
    $this->assertSession()->responseHeaderEquals('Content-Language', 'fr');
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'MISS');

    $this->drupalGet('/system-test/echo/language test', [], ['Accept-Language' => 'en']);
    $this->assertSession()->responseHeaderEquals('Content-Language', 'en');
    $this->assertSession()->responseHeaderDoesNotExist('X-Drupal-Cache');

    // Check with UK browser.
    $this->drupalGet('/system-test/echo/language test', [], ['Accept-Language' => 'en-UK,en']);
    $this->assertSession()->responseHeaderEquals('Content-Language', 'en');
    $this->assertSession()->responseHeaderDoesNotExist('X-Drupal-Cache');

    // Check with french browser.
    $this->drupalGet('/system-test/echo/language test', [], ['Accept-Language' => 'fr-FR,fr']);
    $this->assertSession()->responseHeaderEquals('Content-Language', 'fr');
    $this->assertSession()->responseHeaderDoesNotExist('X-Drupal-Cache');

    // Check with browser without language settings - should return fallback language.
    $this->drupalGet('/system-test/echo/language test', [], ['Accept-Language' => '']);
    $this->assertSession()->responseHeaderEquals('Content-Language', 'en');
    $this->assertSession()->responseHeaderDoesNotExist('X-Drupal-Cache');

    // Check with french browser again.
    $this->drupalGet('/system-test/echo/language test', [], ['Accept-Language' => 'fr-FR,fr']);
    $this->assertSession()->responseHeaderEquals('Content-Language', 'fr');
    $this->assertSession()->responseHeaderDoesNotExist('X-Drupal-Cache');

    // Check with UK browser.
    $this->drupalGet('/system-test/echo/language test', [], ['Accept-Language' => 'en-UK,en']);
    $this->assertSession()->responseHeaderEquals('Content-Language', 'en');
    $this->assertSession()->responseHeaderDoesNotExist('X-Drupal-Cache');

    // Check if prefixed URLs are still cached.
    $this->drupalGet('/en/system-test/echo/language test', [], ['Accept-Language' => 'en']);
    $this->assertSession()->responseHeaderEquals('Content-Language', 'en');
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'HIT');

    $this->drupalGet('/fr/system-test/echo/language test', [], ['Accept-Language' => 'en']);
    $this->assertSession()->responseHeaderEquals('Content-Language', 'fr');
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'HIT');
  }

}
