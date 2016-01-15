<?php

/**
 * @file
 * Contains \Drupal\color\Tests\ColorTest.
 */

namespace Drupal\color\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Modify the Bartik theme colors and make sure the changes are reflected on the
 * frontend.
 *
 * @group color
 */
class ColorTest extends WebTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('color', 'color_test', 'block', 'file');

  /**
   * A user with administrative permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $bigUser;

  /**
   * An associative array of settings for themes.
   *
   * @var array
   */
  protected $themes;

  /**
   * Associative array of hex color strings to test.
   *
   * Keys are the color string and values are a Boolean set to TRUE for valid
   * colors.
   *
   * @var array
   */
  protected $colorTests;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create user.
    $this->bigUser = $this->drupalCreateUser(array('administer themes'));

    // This tests the color module in Bartik.
    $this->themes = array(
      'bartik' => array(
        'palette_input' => 'palette[bg]',
        'scheme' => 'slate',
        'scheme_color' => '#3b3b3b',
      ),
      'color_test_theme' => array(
        'palette_input' => 'palette[bg]',
        'scheme' => 'custom',
        'scheme_color' => '#3b3b3b',
      ),
    );
    \Drupal::service('theme_handler')->install(array_keys($this->themes));

    // Array filled with valid and not valid color values.
    $this->colorTests = array(
      '#000' => TRUE,
      '#123456' => TRUE,
      '#abcdef' => TRUE,
      '#0' => FALSE,
      '#00' => FALSE,
      '#0000' => FALSE,
      '#00000' => FALSE,
      '123456' => FALSE,
      '#00000g' => FALSE,
    );
  }

  /**
   * Tests the Color module functionality.
   */
  function testColor() {
    foreach ($this->themes as $theme => $test_values) {
      $this->_testColor($theme, $test_values);
    }
  }

  /**
   * Tests the Color module functionality using the given theme.
   *
   * @param string $theme
   *   The machine name of the theme being tested.
   * @param array $test_values
   *   An associative array of test settings (i.e. 'Main background', 'Text
   *   color', 'Color set', etc) for the theme which being tested.
   */
  function _testColor($theme, $test_values) {
    $this->config('system.theme')
      ->set('default', $theme)
      ->save();
    $settings_path = 'admin/appearance/settings/' . $theme;

    $this->drupalLogin($this->bigUser);
    $this->drupalGet($settings_path);
    $this->assertResponse(200);
    $this->assertUniqueText('Color set');
    $edit['scheme'] = '';
    $edit[$test_values['palette_input']] = '#123456';
    $this->drupalPostForm($settings_path, $edit, t('Save configuration'));

    $this->drupalGet('<front>');
    $stylesheets = $this->config('color.theme.' . $theme)->get('stylesheets');
    foreach ($stylesheets as $stylesheet) {
      $this->assertPattern('|' . file_url_transform_relative(file_create_url($stylesheet)) . '|', 'Make sure the color stylesheet is included in the content. (' . $theme . ')');
      $stylesheet_content = join("\n", file($stylesheet));
      $this->assertTrue(strpos($stylesheet_content, 'color: #123456') !== FALSE, 'Make sure the color we changed is in the color stylesheet. (' . $theme . ')');
    }

    $this->drupalGet($settings_path);
    $this->assertResponse(200);
    $edit['scheme'] = $test_values['scheme'];
    $this->drupalPostForm($settings_path, $edit, t('Save configuration'));

    $this->drupalGet('<front>');
    $stylesheets = $this->config('color.theme.' . $theme)->get('stylesheets');
    foreach ($stylesheets as $stylesheet) {
      $stylesheet_content = join("\n", file($stylesheet));
      $this->assertTrue(strpos($stylesheet_content, 'color: ' . $test_values['scheme_color']) !== FALSE, 'Make sure the color we changed is in the color stylesheet. (' . $theme . ')');
    }

    // Test with aggregated CSS turned on.
    $config = $this->config('system.performance');
    $config->set('css.preprocess', 1);
    $config->save();
    $this->drupalGet('<front>');
    $stylesheets = \Drupal::state()->get('drupal_css_cache_files') ?: array();
    $stylesheet_content = '';
    foreach ($stylesheets as $uri) {
      $stylesheet_content .= join("\n", file(drupal_realpath($uri)));
    }
    $this->assertTrue(strpos($stylesheet_content, 'public://') === FALSE, 'Make sure the color paths have been translated to local paths. (' . $theme . ')');
    $config->set('css.preprocess', 0);
    $config->save();
  }

  /**
   * Tests whether the provided color is valid.
   */
  function testValidColor() {
    $this->config('system.theme')
      ->set('default', 'bartik')
      ->save();
    $settings_path = 'admin/appearance/settings/bartik';

    $this->drupalLogin($this->bigUser);
    $edit['scheme'] = '';

    foreach ($this->colorTests as $color => $is_valid) {
      $edit['palette[bg]'] = $color;
      $this->drupalPostForm($settings_path, $edit, t('Save configuration'));

      if($is_valid) {
        $this->assertText('The configuration options have been saved.');
      }
      else {
        $this->assertText('You must enter a valid hexadecimal color value for Main background.');
      }
    }
  }

  /**
   * Test whether the custom logo is used in the color preview.
   */
  function testLogoSettingOverride() {
    $this->drupalLogin($this->bigUser);
    $edit = array(
      'default_logo' => FALSE,
      'logo_path' => 'core/misc/druplicon.png',
    );
    $this->drupalPostForm('admin/appearance/settings', $edit, t('Save configuration'));

    // Ensure that the overridden logo is present in Bartik, which is colorable.
    $this->drupalGet('admin/appearance/settings/bartik');
    $this->assertIdentical($GLOBALS['base_path'] . 'core/misc/druplicon.png', $this->getDrupalSettings()['color']['logo']);
  }

  /**
   * Test whether the scheme can be set, viewed anonymously and reset.
   */
  function testOverrideAndResetScheme() {
    $settings_path = 'admin/appearance/settings/bartik';
    $this->config('system.theme')
      ->set('default', 'bartik')
      ->save();

    // Place branding block with site name and slogan into header region.
    $this->drupalPlaceBlock('system_branding_block', ['region' => 'header']);

    $this->drupalGet('');
    $this->assertNoRaw('files/color/bartik-', 'Make sure the color logo is not being used.');
    $this->assertRaw('bartik/logo.svg', 'Make sure the original bartik logo exists.');

    // Log in and set the color scheme to 'slate'.
    $this->drupalLogin($this->bigUser);
    $edit['scheme'] = 'slate';
    $this->drupalPostForm($settings_path, $edit, t('Save configuration'));

    // Visit the homepage and ensure color changes.
    $this->drupalLogout();
    $this->drupalGet('');
    $this->assertRaw('files/color/bartik-', 'Make sure the color logo is being used.');
    $this->assertNoRaw('bartik/logo.svg', 'Make sure the original bartik logo does not exist.');

    // Log in and set the color scheme back to default (delete config).
    $this->drupalLogin($this->bigUser);
    $edit['scheme'] = 'default';
    $this->drupalPostForm($settings_path, $edit, t('Save configuration'));

    // Log out and ensure there is no color and we have the original logo.
    $this->drupalLogout();
    $this->drupalGet('');
    $this->assertNoRaw('files/color/bartik-', 'Make sure the color logo is not being used.');
    $this->assertRaw('bartik/logo.svg', 'Make sure the original bartik logo exists.');
  }

}
