<?php

namespace Drupal\Tests\config_translation\Functional;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;

/**
 * Verifies theme configuration translation settings.
 *
 * @group config_translation
 */
class ConfigTranslationUiThemeTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['config_translation', 'config_translation_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Languages to enable.
   *
   * @var array
   */
  protected $langcodes = ['fr', 'ta'];

  /**
   * Administrator user for tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  protected function setUp() {
    parent::setUp();

    $admin_permissions = [
      'administer themes',
      'administer languages',
      'administer site configuration',
      'translate configuration',
    ];
    // Create and log in user.
    $this->adminUser = $this->drupalCreateUser($admin_permissions);

    // Add languages.
    foreach ($this->langcodes as $langcode) {
      ConfigurableLanguage::createFromLangcode($langcode)->save();
    }
  }

  /**
   * Tests that theme provided *.config_translation.yml files are found.
   */
  public function testThemeDiscovery() {
    // Install the test theme and rebuild routes.
    $theme = 'config_translation_test_theme';

    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/appearance');
    $elements = $this->xpath('//a[normalize-space()=:label and contains(@href, :theme)]', [
      ':label' => 'Install and set as default',
      ':theme' => $theme,
    ]);
    $this->drupalGet($GLOBALS['base_root'] . $elements[0]->getAttribute('href'), ['external' => TRUE]);

    $translation_base_url = 'admin/config/development/performance/translate';
    $this->drupalGet($translation_base_url);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertLinkByHref("$translation_base_url/fr/add");
  }

}
