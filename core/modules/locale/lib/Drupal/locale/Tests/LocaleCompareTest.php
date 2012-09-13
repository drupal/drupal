<?php

/**
 * @file
 * Definition of Drupal\locale\Tests\LocaleCompareTest.
 */

namespace Drupal\locale\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests for comparing status of existing project translations with available translations.
 */
class LocaleCompareTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('update', 'locale', 'locale_test');

  public static function getInfo() {
    return array(
      'name' => 'Compare project states',
      'description' => 'Tests for comparing status of existing project translations with available translations.',
      'group' => 'Locale',
    );
  }

  /**
   * Test for translation status storage and translation status comparison.
   */
  function testLocaleCompare() {
    // Create and login user.
    $admin_user = $this->drupalCreateUser(array('administer site configuration', 'administer languages', 'access administration pages'));
    $this->drupalLogin($admin_user);

    module_load_include('compare.inc', 'locale');

    // Check if hidden modules are not included.
    $projects = locale_translation_project_list();
    $this->assertFalse(isset($projects['locale_test']), 'Hidden module not found');

    // Make the test modules look like a normal custom module. i.e. make the
    // modules not hidden. locale_test_system_info_alter() modifies the project
    // info of the locale_test and locale_test_disabled modules.
    variable_set('locale_translation_test_system_info_alter', TRUE);

    // Check if interface translation data is collected from hook_info.
    drupal_static_reset('locale_translation_project_list');
    $projects = locale_translation_project_list();
    $this->assertEqual($projects['locale_test']['info']['interface translation server pattern'], 'core/modules/locale/test/modules/locale_test/%project-%version.%language.po', 'Interface translation parameter found in project info.');
    $this->assertEqual($projects['locale_test']['name'] , 'locale_test', format_string('%key found in project info.', array('%key' => 'interface translation project')));

    // Get the locale settings.
    $config = config('locale.settings');

    // Check if disabled modules are detected.
    $config->set('translation.check_disabled_modules', TRUE)->save();
    drupal_static_reset('locale_translation_project_list');
    $projects = locale_translation_project_list();
    $this->assertTrue(isset($projects['locale_test_disabled']), 'Disabled module found');

    // Check the fully processed list of project data of both enabled and
    // disabled modules.
    $config->set('translation.check_disabled_modules', TRUE)->save();
    drupal_static_reset('locale_translation_project_list');
    $projects = locale_translation_get_projects();
    $this->assertEqual($projects['drupal']->name, 'drupal', 'Core project found');
    $this->assertEqual($projects['locale_test']->server_pattern, 'core/modules/locale/test/modules/locale_test/%project-%version.%language.po', 'Interface translation parameter found in project info.');
    $this->assertEqual($projects['locale_test_disabled']->status, '0', 'Disabled module found');
    $config->delete('translation.check_disabled_modules');

    // Return the locale test modules back to their hidden state.
    variable_del('locale_translation_test_system_info_alter');
  }

}
