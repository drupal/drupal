<?php

/**
 * @file
 * Contains \Drupal\config\Tests\ConfigLocaleOverrideWebTest.
 */

namespace Drupal\config\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests language overrides in configuration through the request.
 */
class ConfigLocaleOverrideWebTest extends WebTestBase {

  public static $modules = array('locale', 'language', 'system', 'config_test');

  public static function getInfo() {
    return array(
      'name' => 'Locale overrides through the request',
      'description' => 'Tests locale overrides applied through the website.',
      'group' => 'Configuration',
    );
  }

  function setUp() {
    parent::setUp();
  }

  /**
   * Tests translating the site name.
   */
  function testSiteNameTranslation() {
    $adminUser = $this->drupalCreateUser(array('administer site configuration', 'administer languages'));
    $this->drupalLogin($adminUser);

    // Add French and make it the site default language.
    $this->drupalPost('admin/config/regional/language/add', array('predefined_langcode' => 'fr'), t('Add language'));

    $this->drupalLogout();

    // The home page in English should not have the override.
    $this->drupalGet('');
    $this->assertNoText('French site name');

    // During path resolution the system.site configuration object is used to
    // determine the front page. This occurs before language negotiation causing
    // the configuration factory to cache an object without the correct
    // overrides. The config_test module includes a
    // locale.config.fr.system.site.yml which overrides the site name to 'French
    // site name' to test that the configuration factory is re-initialised
    // language negotiation. Ensure that it applies when we access the French
    // front page.
    // @see \Drupal\Core\PathProcessor::processInbound()
    $this->drupalGet('fr');
    $this->assertText('French site name');
  }

}
