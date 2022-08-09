<?php

namespace Drupal\Tests\color\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Modify theme colors and make sure the changes are reflected on the frontend.
 *
 * @group color
 * @group legacy
 */
class ColorTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['color', 'color_test', 'block', 'file'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'color_test_theme';

  /**
   * A user with administrative permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $bigUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create user.
    $this->bigUser = $this->drupalCreateUser(['administer themes']);

    // Set 'color_test_theme' as the default theme.
    $this->config('system.theme')
      ->set('default', 'color_test_theme')
      ->save();
  }

  /**
   * Tests the Color module functionality.
   */
  public function testColor(): void {
    $settings_path = 'admin/appearance/settings/color_test_theme';
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->bigUser);
    $this->drupalGet($settings_path);
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContainsOnce('Color set');
    $edit['scheme'] = '';
    $edit['palette[bg]'] = '#123456';
    $this->drupalGet($settings_path);
    $this->submitForm($edit, 'Save configuration');

    $this->drupalGet('<front>');
    $stylesheets = $this->config('color.theme.color_test_theme')
      ->get('stylesheets');
    /** @var \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator */
    $file_url_generator = \Drupal::service('file_url_generator');
    // Make sure the color stylesheet is included in the content.
    foreach ($stylesheets as $stylesheet) {
      $assert_session->responseMatches('|' . $file_url_generator->generateString($stylesheet) . '|');
      $stylesheet_content = implode("\n", file($stylesheet));
      $this->assertStringContainsString('color: #123456', $stylesheet_content, 'Make sure the color we changed is in the color stylesheet.');
    }

    $edit['scheme'] = 'custom';
    $this->drupalGet($settings_path);
    $this->submitForm($edit, 'Save configuration');

    $this->drupalGet('<front>');
    $stylesheets = $this->config('color.theme.color_test_theme')
      ->get('stylesheets');
    foreach ($stylesheets as $stylesheet) {
      $stylesheet_content = implode("\n", file($stylesheet));
      $this->assertStringContainsString('color: #3b3b3b', $stylesheet_content, 'Make sure the color we changed is in the color stylesheet.');
    }

    // Test with aggregated CSS turned on.
    $config = $this->config('system.performance');
    $config->set('css.preprocess', 1);
    $config->save();
    $this->drupalGet('<front>');
    $stylesheets = \Drupal::state()->get('drupal_css_cache_files', []);
    $stylesheet_content = '';
    foreach ($stylesheets as $uri) {
      $stylesheet_content .= implode("\n", file(\Drupal::service('file_system')
        ->realpath($uri)));
    }
    $this->assertStringNotContainsString('public://', $stylesheet_content, 'Make sure the color paths have been translated to local paths.');
  }

  /**
   * Tests whether the provided color is valid.
   */
  public function testValidColor(): void {
    $settings_path = 'admin/appearance/settings/color_test_theme';
    $assert_session = $this->assertSession();

    // Array filled with valid and not valid color values.
    $colorTests = [
      '#000' => TRUE,
      '#123456' => TRUE,
      '#abcdef' => TRUE,
      '#0' => FALSE,
      '#00' => FALSE,
      '#0000' => FALSE,
      '#00000' => FALSE,
      '123456' => FALSE,
      '#00000g' => FALSE,
    ];

    $this->drupalLogin($this->bigUser);
    $edit['scheme'] = '';

    foreach ($colorTests as $color => $is_valid) {
      $edit['palette[bg]'] = $color;
      $this->drupalGet($settings_path);
      $this->submitForm($edit, 'Save configuration');

      if ($is_valid) {
        $assert_session->pageTextContains('The configuration options have been saved.');
      }
      else {
        $assert_session->pageTextContains('You must enter a valid hexadecimal color value for Main background.');
      }
    }
  }

  /**
   * Tests whether the custom logo is used in the color preview.
   */
  public function testLogoSettingOverride(): void {
    $this->drupalLogin($this->bigUser);
    $edit = [
      'default_logo' => FALSE,
      'logo_path' => 'core/misc/druplicon.png',
    ];
    $this->drupalGet('admin/appearance/settings');
    $this->submitForm($edit, 'Save configuration');

    // Ensure that the overridden logo is present in color_test_theme, which is
    // colorable.
    $this->drupalGet('admin/appearance/settings/color_test_theme');
    $this->assertSame($GLOBALS['base_path'] . 'core/misc/druplicon.png', $this->getDrupalSettings()['color']['logo']);
  }

  /**
   * Tests whether the scheme can be set, viewed anonymously and reset.
   */
  public function testOverrideAndResetScheme(): void {
    $settings_path = 'admin/appearance/settings/color_test_theme';
    $assert_session = $this->assertSession();

    // Place branding block with site name and slogan into header region.
    $this->drupalPlaceBlock('system_branding_block', ['region' => 'header']);

    $this->drupalGet('');
    // Make sure the color logo is not being used.
    $assert_session->responseNotContains('files/color/color_test_theme-');
    // Make sure the original color_test_theme logo exists.
    $assert_session->responseContains('color_test_theme/logo.svg');

    // Log in and set the color scheme to 'custom'.
    $this->drupalLogin($this->bigUser);
    $edit['scheme'] = 'custom';
    $this->drupalGet($settings_path);
    $this->submitForm($edit, 'Save configuration');

    // Visit the homepage and ensure color changes.
    $this->drupalLogout();
    $this->drupalGet('');
    // Make sure the color logo is being used.
    $assert_session->responseContains('files/color/color_test_theme-');
    // Make sure the original color_test_theme logo does not exist.
    $assert_session->responseNotContains('color_test_theme/logo.svg');

    // Log in and set the color scheme back to default (delete config).
    $this->drupalLogin($this->bigUser);
    $edit['scheme'] = 'default';
    $this->drupalGet($settings_path);
    $this->submitForm($edit, 'Save configuration');

    // Log out and ensure there is no color and we have the original logo.
    $this->drupalLogout();
    $this->drupalGet('');
    // Make sure the color logo is not being used.
    $assert_session->responseNotContains('files/color/color_test_theme-');
    // Make sure the original color_test_theme logo exists.
    $assert_session->responseContains('color_test_theme/logo.svg');
  }

}
