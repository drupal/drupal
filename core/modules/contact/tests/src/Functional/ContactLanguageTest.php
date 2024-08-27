<?php

declare(strict_types=1);

namespace Drupal\Tests\contact\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\language\Traits\LanguageTestTrait;

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

  use LanguageTestTrait;

  /**
   * {@inheritdoc}
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
  public function testContactLanguage(): void {
    // Ensure that contact form by default does not show the language select.
    $this->drupalGet('contact');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldNotExists('edit-langcode-0-value');

    // Enable translations for feedback contact messages.
    static::enableBundleTranslation('contact_message', 'feedback');

    // Ensure that contact form now shows the language select.
    $this->drupalGet('contact');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldExists('edit-langcode-0-value');
  }

}
