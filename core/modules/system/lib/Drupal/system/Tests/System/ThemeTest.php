<?php

/**
 * @file
 * Definition of Drupal\system\Tests\System\ThemeTest.
 */

namespace Drupal\system\Tests\System;

use Drupal\simpletest\WebTestBase;

/**
 * Tests for the theme interface functionality.
 */
class ThemeTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Theme interface functionality',
      'description' => 'Tests the theme interface functionality by enabling and switching themes, and using an administration theme.',
      'group' => 'System',
    );
  }

  function setUp() {
    parent::setUp(array('node', 'block'));

    $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));

    $this->admin_user = $this->drupalCreateUser(array('access administration pages', 'view the administration theme', 'administer themes', 'bypass node access', 'administer blocks'));
    $this->drupalLogin($this->admin_user);
    $this->node = $this->drupalCreateNode();
  }

  /**
   * Test the theme settings form.
   */
  function testThemeSettings() {
    // Specify a filesystem path to be used for the logo.
    $file = current($this->drupalGetTestFiles('image'));
    $file_relative = strtr($file->uri, array('public:/' => variable_get('file_public_path', conf_path() . '/files')));
    $default_theme_path = 'core/themes/stark';

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
      $default_theme_path . '/logo.png' => array(
        'form' => $default_theme_path . '/logo.png',
        'src' => $GLOBALS['base_url'] . '/' . $default_theme_path . '/logo.png',
      ),
    );
    foreach ($supported_paths as $input => $expected) {
      $edit = array(
        'default_logo' => FALSE,
        'logo_path' => $input,
      );
      $this->drupalPost('admin/appearance/settings', $edit, t('Save configuration'));
      $this->assertNoText('The custom logo path is invalid.');
      $this->assertFieldByName('logo_path', $expected['form']);

      // Verify logo path examples.
      $elements = $this->xpath('//div[contains(@class, :item)]/div[@class=:description]/code', array(
        ':item' => 'form-item-logo-path',
        ':description' => 'description',
      ));
      // Expected default values (if all else fails).
      $implicit_public_file = 'logo.png';
      $explicit_file = 'public://logo.png';
      $local_file = $default_theme_path . '/logo.png';
      // Adjust for fully qualified stream wrapper URI in public filesystem.
      if (file_uri_scheme($input) == 'public') {
        $implicit_public_file = file_uri_target($input);
        $explicit_file = $input;
        $local_file = strtr($input, array('public:/' => variable_get('file_public_path', conf_path() . '/files')));
      }
      // Adjust for fully qualified stream wrapper URI elsewhere.
      elseif (file_uri_scheme($input) !== FALSE) {
        $explicit_file = $input;
      }
      // Adjust for relative path within public filesystem.
      elseif ($input == file_uri_target($file->uri)) {
        $implicit_public_file = $input;
        $explicit_file = 'public://' . $input;
        $local_file = variable_get('file_public_path', conf_path() . '/files') . '/' . $input;
      }
      $this->assertEqual((string) $elements[0], $implicit_public_file);
      $this->assertEqual((string) $elements[1], $explicit_file);
      $this->assertEqual((string) $elements[2], $local_file);

      // Verify the actual 'src' attribute of the logo being output.
      $this->drupalGet('');
      $elements = $this->xpath('//*[@id=:id]/img', array(':id' => 'logo'));
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
      variable_get('file_public_path', conf_path() . '/files') . '/whatever.png',
      // Semi-absolute path to non-existing file in public filesystem.
      '/' . variable_get('file_public_path', conf_path() . '/files') . '/whatever.png',
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
      $this->drupalPost(NULL, $edit, t('Save configuration'));
      $this->assertText('The custom logo path is invalid.');
    }

    // Upload a file to use for the logo.
    $edit = array(
      'default_logo' => FALSE,
      'logo_path' => '',
      'files[logo_upload]' => drupal_realpath($file->uri),
    );
    $this->drupalPost('admin/appearance/settings', $edit, t('Save configuration'));

    $fields = $this->xpath($this->constructFieldXpath('name', 'logo_path'));
    $uploaded_filename = 'public://' . $fields[0]['value'];

    $this->drupalGet('');
    $elements = $this->xpath('//*[@id=:id]/img', array(':id' => 'logo'));
    $this->assertEqual($elements[0]['src'], file_create_url($uploaded_filename));
  }

  /**
   * Test the administration theme functionality.
   */
  function testAdministrationTheme() {
    theme_enable(array('stark'));
    variable_set('theme_default', 'stark');
    // Enable an administration theme and show it on the node admin pages.
    $edit = array(
      'admin_theme' => 'seven',
      'node_admin_theme' => TRUE,
    );
    $this->drupalPost('admin/appearance', $edit, t('Save configuration'));

    $this->drupalGet('admin/config');
    $this->assertRaw('core/themes/seven', t('Administration theme used on an administration page.'));

    $this->drupalGet('node/' . $this->node->nid);
    $this->assertRaw('core/themes/stark', t('Site default theme used on node page.'));

    $this->drupalGet('node/add');
    $this->assertRaw('core/themes/seven', t('Administration theme used on the add content page.'));

    $this->drupalGet('node/' . $this->node->nid . '/edit');
    $this->assertRaw('core/themes/seven', t('Administration theme used on the edit content page.'));

    // Disable the admin theme on the node admin pages.
    $edit = array(
      'node_admin_theme' => FALSE,
    );
    $this->drupalPost('admin/appearance', $edit, t('Save configuration'));

    $this->drupalGet('admin/config');
    $this->assertRaw('core/themes/seven', t('Administration theme used on an administration page.'));

    $this->drupalGet('node/add');
    $this->assertRaw('core/themes/stark', t('Site default theme used on the add content page.'));

    // Reset to the default theme settings.
    variable_set('theme_default', 'bartik');
    $edit = array(
      'admin_theme' => '0',
      'node_admin_theme' => FALSE,
    );
    $this->drupalPost('admin/appearance', $edit, t('Save configuration'));

    $this->drupalGet('admin');
    $this->assertRaw('core/themes/bartik', t('Site default theme used on administration page.'));

    $this->drupalGet('node/add');
    $this->assertRaw('core/themes/bartik', t('Site default theme used on the add content page.'));
  }

  /**
   * Test switching the default theme.
   */
  function testSwitchDefaultTheme() {
    // Enable Bartik and set it as the default theme.
    theme_enable(array('bartik'));
    $this->drupalGet('admin/appearance');
    $this->clickLink(t('Set default'));
    $this->assertEqual(variable_get('theme_default', ''), 'bartik');

    // Test the default theme on the secondary links (blocks admin page).
    $this->drupalGet('admin/structure/block');
    $this->assertText('Bartik(' . t('active tab') . ')', t('Default local task on blocks admin page is the default theme.'));
    // Switch back to Stark and test again to test that the menu cache is cleared.
    $this->drupalGet('admin/appearance');
    $this->clickLink(t('Set default'), 0);
    $this->drupalGet('admin/structure/block');
    $this->assertText('Stark(' . t('active tab') . ')', t('Default local task on blocks admin page has changed.'));
  }
}
