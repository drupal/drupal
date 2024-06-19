<?php

declare(strict_types=1);

namespace Drupal\Tests\language\Functional;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the session language negotiation method.
 *
 * @group language
 */
class LanguageNegotiationSessionTest extends BrowserTestBase {

  /**
   * An administrative user to configure the test environment.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['language'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a new user with permission to manage the languages.
    $this->adminUser = $this->drupalCreateUser(['administer languages']);
    $this->drupalLogin($this->adminUser);

    ConfigurableLanguage::createFromLangcode('fr')->save();
  }

  /**
   * Tests language negotiation via query/session parameters.
   */
  public function testSessionLanguageNegotiationMethod(): void {
    // Enable Session and Selected language for interface language detection.
    $this->drupalGet('admin/config/regional/language/detection');
    $edit = [
      'language_interface[enabled][language-session]' => 1,
      'language_interface[enabled][language-selected]' => 1,
      'language_interface[weight][language-session]' => -6,
      'language_interface[weight][language-selected]' => 12,
    ];
    $this->submitForm($edit, 'Save settings');

    // Set language via query parameter.
    $this->drupalGet('user/' . $this->adminUser->id(), ['query' => ['language' => 'fr']]);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseHeaderEquals('Content-language', 'fr');

    // Verify that the language is persisted in the session.
    $this->drupalGet('user/' . $this->adminUser->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseHeaderEquals('Content-language', 'fr');

    // Set language via query parameter.
    $this->drupalGet('user/' . $this->adminUser->id(), ['query' => ['language' => 'en']]);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseHeaderEquals('Content-language', 'en');

    // Verify that the language is persisted in the session.
    $this->drupalGet('admin/config/regional/language');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseHeaderEquals('Content-language', 'en');
  }

}
