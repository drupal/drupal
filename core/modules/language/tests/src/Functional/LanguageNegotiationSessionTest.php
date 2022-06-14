<?php

namespace Drupal\Tests\language\Functional;

use Drupal\Core\Language\LanguageInterface;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationBrowser;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationSelected;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationSession;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl;
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
  public function testSessionLanguageNegotiationMethod() {
    $this->drupalGet('admin/config/regional/language/detection');

    // Enable Session and Selected language for interface language detection.
    $config = $this->config('language.types');
    $config->set('configurable', [LanguageInterface::TYPE_INTERFACE]);
    $config->set('negotiation.language_interface.enabled', [
      LanguageNegotiationSession::METHOD_ID => -6,
      LanguageNegotiationSelected::METHOD_ID => 12,
    ]);
    $config->set('negotiation.language_interface.method_weights', [
      'language-user-admin' => -10,
      LanguageNegotiationUrl::METHOD_ID => -8,
      LanguageNegotiationSession::METHOD_ID => -6,
      'language-user' => -4,
      LanguageNegotiationBrowser::METHOD_ID => -2,
      LanguageNegotiationSelected::METHOD_ID => 12,
    ]);
    $config->save();

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
