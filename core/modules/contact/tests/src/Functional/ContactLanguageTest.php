<?php

namespace Drupal\Tests\contact\Functional;

use Drupal\Core\Language\LanguageInterface;
use Drupal\language\Entity\ContentLanguageSettings;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests contact messages with language module.
 *
 * This is to ensure that a contact form by default does not show the language
 * select, but it does so when it's enabled from the content language settings
 * page.
 *
 * @group contact
 */
class ContactLanguageTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'contact',
    'language',
    'contact_test',
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

    // Create and log in administrative user.
    $admin_user = $this->drupalCreateUser([
      'access site-wide contact form',
      'administer languages',
    ]);
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests configuration options with language enabled.
   */
  public function testContactLanguage() {
    // Ensure that contact form by default does not show the language select.
    $this->drupalGet('contact');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldNotExists('edit-langcode-0-value');

    // Enable language select.
    $config = ContentLanguageSettings::loadByEntityTypeBundle('contact_message', 'feedback');
    $config->setDefaultLangcode(LanguageInterface::LANGCODE_SITE_DEFAULT);
    $config->setLanguageAlterable(TRUE);
    $config->save();

    // Ensure that contact form now shows the language select.
    $this->drupalGet('contact');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldExists('edit-langcode-0-value');
  }

}
