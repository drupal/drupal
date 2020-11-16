<?php

namespace Drupal\FunctionalTests\Routing;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\Traits\Core\PathAliasTestTrait;

/**
 * Tests the route cache when the language is not in the path.
 *
 * @group language
 */
class RouteCachingNonPathLanguageNegotiationTest extends BrowserTestBase {

  use PathAliasTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['language', 'block'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  protected function setUp(): void {
    parent::setUp();

    // Create and log in user.
    $this->adminUser = $this->drupalCreateUser([
      'administer blocks',
      'administer languages',
      'access administration pages',
    ]);
    $this->drupalLogin($this->adminUser);

    // Add language.
    ConfigurableLanguage::createFromLangcode('fr')->save();

    // Enable session language detection and selection.
    $edit = [
      'language_interface[enabled][language-url]' => FALSE,
      'language_interface[enabled][language-session]' => TRUE,
    ];
    $this->drupalPostForm('admin/config/regional/language/detection', $edit, 'Save settings');

    // A more common scenario is domain-based negotiation but that can not be
    // tested. Session negotiation by default is not considered by the URL
    // language type that is used to resolve the alias. Explicitly enable
    // that to be able to test this scenario.
    // @todo Improve in https://www.drupal.org/project/drupal/issues/1125428.
    $this->config('language.types')
      ->set('negotiation.language_url.enabled', ['language-session' => 0])
      ->save();

    // Enable the language switching block.
    $this->drupalPlaceBlock('language_block:' . LanguageInterface::TYPE_INTERFACE, [
      'id' => 'test_language_block',
    ]);

  }

  /**
   * Tests aliases when the negotiated language is not in the path.
   */
  public function testAliases() {
    // Switch to French and try to access the now inaccessible block.
    $this->drupalGet('');

    // Create an alias for user/UID just for en, make sure that this is a 404
    // on the french page exist in english, no matter which language is
    // checked first. Create the alias after visiting frontpage to make sure
    // there is no existing cache entry for this that affects the tests.
    $this->createPathAlias('/user/' . $this->adminUser->id(), '/user-page', 'en');

    $this->clickLink('French');
    $this->drupalGet('user-page');
    $this->assertSession()->statusCodeEquals(404);

    // Switch to english, make sure it works now.
    $this->clickLink('English');
    $this->drupalGet('user-page');
    $this->assertSession()->statusCodeEquals(200);

    // Clear cache and repeat the check, this time with english first.
    $this->resetAll();
    $this->drupalGet('user-page');
    $this->assertSession()->statusCodeEquals(200);

    $this->clickLink('French');
    $this->drupalGet('user-page');
    $this->assertSession()->statusCodeEquals(404);
  }

}
