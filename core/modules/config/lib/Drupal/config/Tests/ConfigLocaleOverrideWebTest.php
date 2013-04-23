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

  public static $modules = array('locale', 'language', 'system');

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

    // Add a custom lanugage.
    $langcode = 'xx';
    $name = $this->randomName(16);
    $edit = array(
      'predefined_langcode' => 'custom',
      'langcode' => $langcode,
      'name' => $name,
      'direction' => '0',
    );
    $this->drupalPost('admin/config/regional/language/add', $edit, t('Add custom language'));

    // Save an override for the XX language.
    config('locale.config.xx.system.site')->set('name', 'XX site name')->save();

    $this->drupalLogout();

    // The home page in English should not have the override.
    $this->drupalGet('');
    $this->assertNoText('XX site name');

    // During path resolution the system.site configuration object is used to
    // determine the front page. This occurs before language negotiation causing
    // the configuration factory to cache an object without the correct
    // overrides. We are testing that the configuration factory is
    // re-initialised after language negotiation. Ensure that it applies when
    // we access the XX front page.
    // @see \Drupal\Core\PathProcessor::processInbound()
    $this->drupalGet('xx');
    $this->assertText('XX site name');
  }

}
