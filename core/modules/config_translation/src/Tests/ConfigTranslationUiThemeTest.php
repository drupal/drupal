<?php

/**
 * @file
 * Contains \Drupal\config_translation\Tests\ConfigTranslationUiThemeTest.
 */

namespace Drupal\config_translation\Tests;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\simpletest\WebTestBase;

/**
 * Verifies theme configuration translation settings.
 *
 * @group config_translation
 */
class ConfigTranslationUiThemeTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('config_translation', 'config_translation_test');

  /**
   * Languages to enable.
   *
   * @var array
   */
  protected $langcodes = array('fr', 'ta');

  /**
   * Administrator user for tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $admin_user;

  protected function setUp() {
    parent::setUp();

    $admin_permissions = array(
      'administer themes',
      'administer languages',
      'administer site configuration',
      'translate configuration',
    );
    // Create and login user.
    $this->admin_user = $this->drupalCreateUser($admin_permissions);

    // Add languages.
    foreach ($this->langcodes as $langcode) {
      ConfigurableLanguage::createFromLangcode($langcode)->save();
    }
  }

  /**
   * Tests that theme provided *.config_translation.yml files are found.
   */
  public function testThemeDiscovery() {
    // Enable the test theme and rebuild routes.
    $theme = 'config_translation_test_theme';

    $this->drupalLogin($this->admin_user);

    $this->drupalGet('admin/appearance');
    $elements = $this->xpath('//a[normalize-space()=:label and contains(@href, :theme)]', array(
      ':label' => 'Enable and set as default',
      ':theme' => $theme,
    ));
    $this->drupalGet($GLOBALS['base_root'] . $elements[0]['href'], array('external' => TRUE));

    $translation_base_url = 'admin/config/development/performance/translate';
    $this->drupalGet($translation_base_url);
    $this->assertResponse(200);
    $this->assertLinkByHref("$translation_base_url/fr/add");
  }

}
