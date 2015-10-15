<?php

/**
 * @file
 * Contains \Drupal\system\Tests\System\ThemeTest.
 */

namespace Drupal\system\Tests\System;

use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the theme interface functionality by enabling and switching themes, and
 * using an administration theme.
 *
 * @group system
 */
class ThemeTest extends WebTestBase {

  /**
   * A user with administrative permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'block', 'file'];

  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));

    $this->adminUser = $this->drupalCreateUser(array('access administration pages', 'view the administration theme', 'administer themes', 'bypass node access', 'administer blocks'));
    $this->drupalLogin($this->adminUser);
    $this->node = $this->drupalCreateNode();
    $this->drupalPlaceBlock('local_tasks_block');
  }

  /**
   * Test the theme settings form.
   */
  function testThemeSettings() {
    // Ensure invalid theme settings form URLs return a proper 404.
    $this->drupalGet('admin/appearance/settings/bartik');
    $this->assertResponse(404, 'The theme settings form URL for a uninstalled theme could not be found.');
    $this->drupalGet('admin/appearance/settings/' . $this->randomMachineName());
    $this->assertResponse(404, 'The theme settings form URL for a non-existent theme could not be found.');

    // Specify a filesystem path to be used for the logo.
    $file = current($this->drupalGetTestFiles('image'));
    $file_relative = strtr($file->uri, array('public:/' => PublicStream::basePath()));
    $default_theme_path = 'core/themes/classy';

    $supported_paths = array(
      // Raw stream wrapper URI.
      $file->uri => array(
        'form' => file_uri_target($file->uri),
        'src' => file_create_url($file->uri),
      ),
      // Relative path within the public filesystem.
      file_uri_target($file->uri) => array(
        'form' => file_uri_target($file->uri),
        'src' => file_create_url($file->uri),
      ),
      // Relative path to a public file.
      $file_relative => array(
        'form' => $file_relative,
        'src' => file_create_url($file->uri),
      ),
      // Relative path to an arbitrary file.
      'core/misc/druplicon.png' => array(
        'form' => 'core/misc/druplicon.png',
        'src' => $GLOBALS['base_url'] . '/' . 'core/misc/druplicon.png',
      ),
      // Relative path to a file in a theme.
      $default_theme_path . '/logo.svg' => array(
        'form' => $default_theme_path . '/logo.svg',
        'src' => $GLOBALS['base_url'] . '/' . $default_theme_path . '/logo.svg',
      ),
    );
    foreach ($supported_paths as $input => $expected) {
      $edit = array(
        'default_logo' => FALSE,
        'logo_path' => $input,
      );
      $this->drupalPostForm('admin/appearance/settings', $edit, t('Save configuration'));
      $this->assertNoText('The custom logo path is invalid.');
      $this->assertFieldByName('logo_path', $expected['form']);

      // Verify logo path examples.
      $elements = $this->xpath('//div[contains(@class, :item)]/div[@class=:description]/code', array(
        ':item' => 'js-form-item-logo-path',
        ':description' => 'description',
      ));
      // Expected default values (if all else fails).
      $implicit_public_file = 'logo.svg';
      $explicit_file = 'public://logo.svg';
      $local_file = $default_theme_path . '/logo.svg';
      // Adjust for fully qualified stream wrapper URI in public filesystem.
      if (file_uri_scheme($input) == 'public') {
        $implicit_public_file = file_uri_target($input);
        $explicit_file = $input;
        $local_file = strtr($input, array('public:/' => PublicStream::basePath()));
      }
      // Adjust for fully qualified stream wrapper URI elsewhere.
      elseif (file_uri_scheme($input) !== FALSE) {
        $explicit_file = $input;
      }
      // Adjust for relative path within public filesystem.
      elseif ($input == file_uri_target($file->uri)) {
        $implicit_public_file = $input;
        $explicit_file = 'public://' . $input;
        $local_file = PublicStream::basePath() . '/' . $input;
      }
      $this->assertEqual((string) $elements[0], $implicit_public_file);
      $this->assertEqual((string) $elements[1], $explicit_file);
      $this->assertEqual((string) $elements[2], $local_file);

      // Verify the actual 'src' attribute of the logo being output in a site
      // branding block.
      $this->drupalPlaceBlock('system_branding_block', ['region' => 'header']);
      $this->drupalGet('');
      $elements = $this->xpath('//header//a[@rel=:rel]/img', array(
          ':rel' => 'home',
        )
      );
      $this->assertEqual((string) $elements[0]['src'], $expected['src']);
    }
    $unsupported_paths = array(
      // Stream wrapper URI to non-existing file.
      'public://whatever.png',
      'private://whatever.png',
      'temporary://whatever.png',
      // Bogus stream wrapper URIs.
      'public:/whatever.png',
      '://whatever.png',
      ':whatever.png',
      'public://',
      // Relative path within the public filesystem to non-existing file.
      'whatever.png',
      // Relative path to non-existing file in public filesystem.
      PublicStream::basePath() . '/whatever.png',
      // Semi-absolute path to non-existing file in public filesystem.
      '/' . PublicStream::basePath() . '/whatever.png',
      // Relative path to arbitrary non-existing file.
      'core/misc/whatever.png',
      // Semi-absolute path to arbitrary non-existing file.
      '/core/misc/whatever.png',
      // Absolute paths to any local file (even if it exists).
      drupal_realpath($file->uri),
    );
    $this->drupalGet('admin/appearance/settings');
    foreach ($unsupported_paths as $path) {
      $edit = array(
        'default_logo' => FALSE,
        'logo_path' => $path,
      );
      $this->drupalPostForm(NULL, $edit, t('Save configuration'));
      $this->assertText('The custom logo path is invalid.');
    }

    // Upload a file to use for the logo.
    $edit = array(
      'default_logo' => FALSE,
      'logo_path' => '',
      'files[logo_upload]' => drupal_realpath($file->uri),
    );
    $this->drupalPostForm('admin/appearance/settings', $edit, t('Save configuration'));

    $fields = $this->xpath($this->constructFieldXpath('name', 'logo_path'));
    $uploaded_filename = 'public://' . $fields[0]['value'];

    $this->drupalPlaceBlock('system_branding_block', ['region' => 'header']);
    $this->drupalGet('');
    $elements = $this->xpath('//header//a[@rel=:rel]/img', array(
        ':rel' => 'home',
      )
    );
    $this->assertEqual($elements[0]['src'], file_create_url($uploaded_filename));

    $this->container->get('theme_handler')->install(array('bartik'));
    $this->drupalGet('admin/appearance/settings/bartik');
    // The logo field should only be present on the global theme settings form.
    $this->assertNoFieldByName('logo_path');
    $this->drupalPostForm(NULL, [], t('Save configuration'));
  }

  /**
   * Test the administration theme functionality.
   */
  function testAdministrationTheme() {
    $this->container->get('theme_handler')->install(array('seven'));

    // Install an administration theme and show it on the node admin pages.
    $edit = array(
      'admin_theme' => 'seven',
      'use_admin_theme' => TRUE,
    );
    $this->drupalPostForm('admin/appearance', $edit, t('Save configuration'));

    $this->drupalGet('admin/config');
    $this->assertRaw('core/themes/seven', 'Administration theme used on an administration page.');

    $this->drupalGet('node/' . $this->node->id());
    $this->assertRaw('core/themes/classy', 'Site default theme used on node page.');

    $this->drupalGet('node/add');
    $this->assertRaw('core/themes/seven', 'Administration theme used on the add content page.');

    $this->drupalGet('node/' . $this->node->id() . '/edit');
    $this->assertRaw('core/themes/seven', 'Administration theme used on the edit content page.');

    // Disable the admin theme on the node admin pages.
    $edit = array(
      'use_admin_theme' => FALSE,
    );
    $this->drupalPostForm('admin/appearance', $edit, t('Save configuration'));

    $this->drupalGet('admin/config');
    $this->assertRaw('core/themes/seven', 'Administration theme used on an administration page.');

    // Ensure that the admin theme is also visible on the 403 page.
    $normal_user = $this->drupalCreateUser(['view the administration theme']);
    $this->drupalLogin($normal_user);
    $this->drupalGet('admin/config');
    $this->assertResponse(403);
    $this->assertRaw('core/themes/seven', 'Administration theme used on an administration page.');
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('node/add');
    $this->assertRaw('core/themes/classy', 'Site default theme used on the add content page.');

    // Reset to the default theme settings.
    $edit = array(
      'admin_theme' => '0',
      'use_admin_theme' => FALSE,
    );
    $this->drupalPostForm('admin/appearance', $edit, t('Save configuration'));

    $this->drupalGet('admin');
    $this->assertRaw('core/themes/classy', 'Site default theme used on administration page.');

    $this->drupalGet('node/add');
    $this->assertRaw('core/themes/classy', 'Site default theme used on the add content page.');
  }

  /**
   * Test switching the default theme.
   */
  function testSwitchDefaultTheme() {
    // Install Bartik and set it as the default theme.
    \Drupal::service('theme_handler')->install(array('bartik'));
    $this->drupalGet('admin/appearance');
    $this->clickLink(t('Set as default'));
    $this->assertEqual($this->config('system.theme')->get('default'), 'bartik');

    // Test the default theme on the secondary links (blocks admin page).
    $this->drupalGet('admin/structure/block');
    $this->assertText('Bartik(' . t('active tab') . ')', 'Default local task on blocks admin page is the default theme.');
    // Switch back to Stark and test again to test that the menu cache is cleared.
    $this->drupalGet('admin/appearance');
    // Classy is the first 'Set as default' link.
    $this->clickLink(t('Set as default'), 0);
    $this->drupalGet('admin/structure/block');
    $this->assertText('Classy(' . t('active tab') . ')', 'Default local task on blocks admin page has changed.');
  }

  /**
   * Test themes can't be installed when the base theme or engine is missing.
   */
  function testInvalidTheme() {
    // theme_page_test_system_info_alter() un-hides all hidden themes.
    $this->container->get('module_installer')->install(array('theme_page_test'));
    // Clear the system_list() and theme listing cache to pick up the change.
    $this->container->get('theme_handler')->reset();
    $this->drupalGet('admin/appearance');
    $this->assertText(t('This theme requires the base theme @base_theme to operate correctly.', array('@base_theme' => 'not_real_test_basetheme')));
    $this->assertText(t('This theme requires the theme engine @theme_engine to operate correctly.', array('@theme_engine' => 'not_real_engine')));
    // Check for the error text of a theme with the wrong core version.
    $this->assertText("This theme is not compatible with Drupal 8.x. Check that the .info.yml file contains the correct 'core' value.");
    // Check for the error text of a theme without a content region.
    $this->assertText("This theme is missing a 'content' region.");
  }

  /**
   * Test uninstalling of themes works.
   */
  function testUninstallingThemes() {
    // Install Bartik and set it as the default theme.
    \Drupal::service('theme_handler')->install(array('bartik'));
    // Set up seven as the admin theme.
    \Drupal::service('theme_handler')->install(array('seven'));
    $edit = array(
      'admin_theme' => 'seven',
      'use_admin_theme' => TRUE,
    );
    $this->drupalPostForm('admin/appearance', $edit, t('Save configuration'));
    $this->drupalGet('admin/appearance');
    $this->clickLink(t('Set as default'));

    // Check that seven cannot be uninstalled as it is the admin theme.
    $this->assertNoRaw('Uninstall Seven theme', 'A link to uninstall the Seven theme does not appear on the theme settings page.');
    // Check that bartik cannot be uninstalled as it is the default theme.
    $this->assertNoRaw('Uninstall Bartik theme', 'A link to uninstall the Bartik theme does not appear on the theme settings page.');
    // Check that the classy theme cannot be uninstalled as it is a base theme
    // of seven and bartik.
    $this->assertNoRaw('Uninstall Classy theme', 'A link to uninstall the Classy theme does not appear on the theme settings page.');

    // Install Stark and set it as the default theme.
    \Drupal::service('theme_handler')->install(array('stark'));

    $edit = array(
      'admin_theme' => 'stark',
      'use_admin_theme' => TRUE,
    );
    $this->drupalPostForm('admin/appearance', $edit, t('Save configuration'));

    // Check that seven can be uninstalled now.
    $this->assertRaw('Uninstall Seven theme', 'A link to uninstall the Seven theme does appear on the theme settings page.');
    // Check that the classy theme still cannot be uninstalled as it is a
    // base theme of bartik.
    $this->assertNoRaw('Uninstall Classy theme', 'A link to uninstall the Classy theme does not appear on the theme settings page.');

    // Change the default theme to stark, stark is third in the list.
    $this->clickLink(t('Set as default'), 2);

    // Check that bartik can be uninstalled now.
    $this->assertRaw('Uninstall Bartik theme', 'A link to uninstall the Bartik theme does appear on the theme settings page.');

    // Check that the classy theme still can't be uninstalled as neither of it's
    // base themes have been.
    $this->assertNoRaw('Uninstall Classy theme', 'A link to uninstall the Classy theme does not appear on the theme settings page.');

    // Uninstall each of the three themes starting with Bartik.
    $this->clickLink(t('Uninstall'));
    $this->assertRaw('The <em class="placeholder">Bartik</em> theme has been uninstalled');
    // Seven is the second in the list.
    $this->clickLink(t('Uninstall'));
    $this->assertRaw('The <em class="placeholder">Seven</em> theme has been uninstalled');
    // Now uninstall classy.
    $this->clickLink(t('Uninstall'));
    $this->assertRaw('The <em class="placeholder">Classy</em> theme has been uninstalled');
  }

  /**
   * Tests installing a theme and setting it as default.
   */
  function testInstallAndSetAsDefault() {
    $this->drupalGet('admin/appearance');
    // Bartik is uninstalled in the test profile and has the third "Install and
    // set as default" link.
    $this->clickLink(t('Install and set as default'), 2);
    // Test the confirmation message.
    $this->assertText('Bartik is now the default theme.');
    // Make sure Bartik is now set as the default theme in config.
    $this->assertEqual($this->config('system.theme')->get('default'), 'bartik');

    // This checks for a regression. See https://www.drupal.org/node/2498691.
    $this->assertNoText('The bartik theme was not found.');

    $themes = \Drupal::service('theme_handler')->rebuildThemeData();
    $version = $themes['bartik']->info['version'];

    // Confirm Bartik is indicated as the default theme.
    $this->assertTextPattern('/Bartik ' . preg_quote($version) . '\s{2,}\(default theme\)/');
  }

}
