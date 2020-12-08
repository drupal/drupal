<?php

namespace Drupal\Tests\system\Functional\System;

use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests the theme interface functionality by enabling and switching themes, and
 * using an administration theme.
 *
 * @group system
 */
class ThemeTest extends BrowserTestBase {

  use TestFileCreationTrait {
    getTestFiles as drupalGetTestFiles;
  }

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
  protected static $modules = ['node', 'block', 'file'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  protected function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);

    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
      'view the administration theme',
      'administer themes',
      'bypass node access',
      'administer blocks',
    ]);
    $this->drupalLogin($this->adminUser);
    $this->node = $this->drupalCreateNode();
    $this->drupalPlaceBlock('local_tasks_block');
  }

  /**
   * Test the theme settings form.
   */
  public function testThemeSettings() {
    // Ensure a disabled theme settings form URL returns 404.
    $this->drupalGet('admin/appearance/settings/bartik');
    $this->assertSession()->statusCodeEquals(404);
    // Ensure a non existent theme settings form URL returns 404.
    $this->drupalGet('admin/appearance/settings/' . $this->randomMachineName());
    $this->assertSession()->statusCodeEquals(404);
    // Ensure a hidden theme settings form URL returns 404.
    $this->assertTrue(\Drupal::service('theme_installer')->install(['stable']));
    $this->drupalGet('admin/appearance/settings/stable');
    $this->assertSession()->statusCodeEquals(404);

    // Specify a filesystem path to be used for the logo.
    $file = current($this->drupalGetTestFiles('image'));
    $file_relative = strtr($file->uri, ['public:/' => PublicStream::basePath()]);
    $default_theme_path = 'core/themes/classy';

    $supported_paths = [
      // Raw stream wrapper URI.
      $file->uri => [
        'form' => StreamWrapperManager::getTarget($file->uri),
        'src' => file_url_transform_relative(file_create_url($file->uri)),
      ],
      // Relative path within the public filesystem.
      StreamWrapperManager::getTarget($file->uri) => [
        'form' => StreamWrapperManager::getTarget($file->uri),
        'src' => file_url_transform_relative(file_create_url($file->uri)),
      ],
      // Relative path to a public file.
      $file_relative => [
        'form' => $file_relative,
        'src' => file_url_transform_relative(file_create_url($file->uri)),
      ],
      // Relative path to an arbitrary file.
      'core/misc/druplicon.png' => [
        'form' => 'core/misc/druplicon.png',
        'src' => base_path() . 'core/misc/druplicon.png',
      ],
      // Relative path to a file in a theme.
      $default_theme_path . '/logo.svg' => [
        'form' => $default_theme_path . '/logo.svg',
        'src' => base_path() . $default_theme_path . '/logo.svg',
      ],
    ];
    foreach ($supported_paths as $input => $expected) {
      $edit = [
        'default_logo' => FALSE,
        'logo_path' => $input,
      ];
      $this->drupalPostForm('admin/appearance/settings', $edit, 'Save configuration');
      $this->assertNoText('The custom logo path is invalid.');
      $this->assertSession()->fieldValueEquals('logo_path', $expected['form']);

      // Verify logo path examples.
      $elements = $this->xpath('//div[contains(@class, :item)]/div[@class=:description]/code', [
        ':item' => 'js-form-item-logo-path',
        ':description' => 'description',
      ]);
      // Expected default values (if all else fails).
      $implicit_public_file = 'logo.svg';
      $explicit_file = 'public://logo.svg';
      $local_file = $default_theme_path . '/logo.svg';
      // Adjust for fully qualified stream wrapper URI in public filesystem.
      if (StreamWrapperManager::getScheme($input) == 'public') {
        $implicit_public_file = StreamWrapperManager::getTarget($input);
        $explicit_file = $input;
        $local_file = strtr($input, ['public:/' => PublicStream::basePath()]);
      }
      // Adjust for fully qualified stream wrapper URI elsewhere.
      elseif (StreamWrapperManager::getScheme($input) !== FALSE) {
        $explicit_file = $input;
      }
      // Adjust for relative path within public filesystem.
      elseif ($input == StreamWrapperManager::getTarget($file->uri)) {
        $implicit_public_file = $input;
        $explicit_file = 'public://' . $input;
        $local_file = PublicStream::basePath() . '/' . $input;
      }
      $this->assertEqual($elements[0]->getText(), $implicit_public_file);
      $this->assertEqual($elements[1]->getText(), $explicit_file);
      $this->assertEqual($elements[2]->getText(), $local_file);

      // Verify the actual 'src' attribute of the logo being output in a site
      // branding block.
      $this->drupalPlaceBlock('system_branding_block', ['region' => 'header']);
      $this->drupalGet('');
      $elements = $this->xpath('//header//a[@rel=:rel]/img', [
          ':rel' => 'home',
        ]
      );
      $this->assertEqual($elements[0]->getAttribute('src'), $expected['src']);
    }
    $unsupported_paths = [
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
      \Drupal::service('file_system')->realpath($file->uri),
    ];
    $this->drupalGet('admin/appearance/settings');
    foreach ($unsupported_paths as $path) {
      $edit = [
        'default_logo' => FALSE,
        'logo_path' => $path,
      ];
      $this->submitForm($edit, 'Save configuration');
      $this->assertText('The custom logo path is invalid.');
    }

    // Upload a file to use for the logo.
    $edit = [
      'default_logo' => FALSE,
      'logo_path' => '',
      'files[logo_upload]' => \Drupal::service('file_system')->realpath($file->uri),
    ];
    $this->drupalPostForm('admin/appearance/settings', $edit, 'Save configuration');

    $uploaded_filename = 'public://' . $this->getSession()->getPage()->findField('logo_path')->getValue();

    $this->drupalPlaceBlock('system_branding_block', ['region' => 'header']);
    $this->drupalGet('');
    $elements = $this->xpath('//header//a[@rel=:rel]/img', [
        ':rel' => 'home',
      ]
    );
    $this->assertEqual($elements[0]->getAttribute('src'), file_url_transform_relative(file_create_url($uploaded_filename)));

    $this->container->get('theme_installer')->install(['bartik']);

    // Ensure only valid themes are listed in the local tasks.
    $this->drupalPlaceBlock('local_tasks_block', ['region' => 'header']);
    $this->drupalGet('admin/appearance/settings');
    $theme_handler = \Drupal::service('theme_handler');
    $this->assertSession()->linkExists($theme_handler->getName('classy'));
    $this->assertSession()->linkExists($theme_handler->getName('bartik'));
    $this->assertSession()->linkNotExists($theme_handler->getName('stable'));

    // If a hidden theme is an admin theme it should be viewable.
    \Drupal::configFactory()->getEditable('system.theme')->set('admin', 'stable')->save();
    \Drupal::service('router.builder')->rebuildIfNeeded();
    $this->drupalPlaceBlock('local_tasks_block', ['region' => 'header', 'theme' => 'stable']);
    $this->drupalGet('admin/appearance/settings');
    $this->assertSession()->linkExists($theme_handler->getName('stable'));
    $this->drupalGet('admin/appearance/settings/stable');
    $this->assertSession()->statusCodeEquals(200);

    // Ensure default logo and favicons are not triggering custom path
    // validation errors if their custom paths are set on the form.
    $edit = [
      'default_logo' => TRUE,
      'logo_path' => 'public://whatever.png',
      'default_favicon' => TRUE,
      'favicon_path' => 'public://whatever.ico',
    ];
    $this->drupalPostForm('admin/appearance/settings', $edit, 'Save configuration');
    $this->assertNoText('The custom logo path is invalid.');
    $this->assertNoText('The custom favicon path is invalid.');
  }

  /**
   * Test the theme settings logo form.
   */
  public function testThemeSettingsLogo() {
    // Visit Bartik's theme settings page to replace the logo.
    $this->container->get('theme_installer')->install(['bartik']);
    $this->drupalGet('admin/appearance/settings/bartik');
    $edit = [
      'default_logo' => FALSE,
      'logo_path' => 'core/misc/druplicon.png',
    ];
    $this->drupalPostForm('admin/appearance/settings/bartik', $edit, 'Save configuration');
    $this->assertSession()->fieldValueEquals('default_logo', FALSE);
    $this->assertSession()->fieldValueEquals('logo_path', 'core/misc/druplicon.png');

    // Make sure the logo and favicon settings are not available when the file
    // module is not enabled.
    \Drupal::service('module_installer')->uninstall(['file']);
    $this->drupalGet('admin/appearance/settings');
    $this->assertNoText('Logo image settings');
    $this->assertNoText('Shortcut icon settings');
  }

  /**
   * Tests the 'rendered' cache tag is cleared when saving theme settings.
   */
  public function testThemeSettingsRenderCacheClear() {
    $this->container->get('theme_installer')->install(['bartik']);
    // Ensure the frontpage is cached for anonymous users. The render cache will
    // cleared by installing a theme.
    $this->drupalLogout();
    $this->drupalGet('');
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'MISS');
    $this->drupalGet('');
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'HIT');

    $this->drupalLogin($this->adminUser);
    // Save Bartik's theme settings which should invalidate the 'rendered' cache
    // tag in \Drupal\system\EventSubscriber\ConfigCacheTag.
    $this->drupalPostForm('admin/appearance/settings/bartik', [], 'Save configuration');
    $this->drupalLogout();
    $this->drupalGet('');
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'MISS');
  }

  /**
   * Test the administration theme functionality.
   */
  public function testAdministrationTheme() {
    $this->container->get('theme_installer')->install(['seven']);

    // Install an administration theme and show it on the node admin pages.
    $edit = [
      'admin_theme' => 'seven',
      'use_admin_theme' => TRUE,
    ];
    $this->drupalPostForm('admin/appearance', $edit, 'Save configuration');

    // Check that the administration theme is used on an administration page.
    $this->drupalGet('admin/config');
    $this->assertRaw('core/themes/seven');

    // Check that the site default theme used on node page.
    $this->drupalGet('node/' . $this->node->id());
    $this->assertRaw('core/themes/classy');

    // Check that the administration theme is used on the add content page.
    $this->drupalGet('node/add');
    $this->assertRaw('core/themes/seven');

    // Check that the administration theme is used on the edit content page.
    $this->drupalGet('node/' . $this->node->id() . '/edit');
    $this->assertRaw('core/themes/seven');

    // Disable the admin theme on the node admin pages.
    $edit = [
      'use_admin_theme' => FALSE,
    ];
    $this->drupalPostForm('admin/appearance', $edit, 'Save configuration');

    // Check that the administration theme is used on an administration page.
    $this->drupalGet('admin/config');
    $this->assertRaw('core/themes/seven');

    // Ensure that the admin theme is also visible on the 403 page.
    $normal_user = $this->drupalCreateUser(['view the administration theme']);
    $this->drupalLogin($normal_user);
    // Check that the administration theme is used on an administration page.
    $this->drupalGet('admin/config');
    $this->assertSession()->statusCodeEquals(403);
    $this->assertRaw('core/themes/seven');
    $this->drupalLogin($this->adminUser);

    // Check that the site default theme used on the add content page.
    $this->drupalGet('node/add');
    $this->assertRaw('core/themes/classy');

    // Reset to the default theme settings.
    $edit = [
      'admin_theme' => '',
      'use_admin_theme' => FALSE,
    ];
    $this->drupalPostForm('admin/appearance', $edit, 'Save configuration');

    // Check that the site default theme used on administration page.
    $this->drupalGet('admin');
    $this->assertRaw('core/themes/classy');

    // Check that the site default theme used on the add content page.
    $this->drupalGet('node/add');
    $this->assertRaw('core/themes/classy');
  }

  /**
   * Test switching the default theme.
   */
  public function testSwitchDefaultTheme() {
    /** @var \Drupal\Core\Extension\ThemeInstallerInterface $theme_installer */
    $theme_installer = \Drupal::service('theme_installer');
    // First, install Stark and set it as the default theme programmatically.
    $theme_installer->install(['stark']);
    $this->config('system.theme')->set('default', 'stark')->save();
    $this->drupalPlaceBlock('local_tasks_block');

    // Install Bartik and set it as the default theme.
    $theme_installer->install(['bartik']);
    $this->drupalGet('admin/appearance');
    $this->clickLink(t('Set as default'));
    $this->assertEqual($this->config('system.theme')->get('default'), 'bartik');

    // Test the default theme on the secondary links (blocks admin page).
    $this->drupalGet('admin/structure/block');
    $this->assertSession()->pageTextContains('Bartik(active tab)');
    // Switch back to Stark and test again to test that the menu cache is cleared.
    $this->drupalGet('admin/appearance');
    // Stark is the first 'Set as default' link.
    $this->clickLink(t('Set as default'));
    $this->drupalGet('admin/structure/block');
    $this->assertSession()->pageTextContains('Stark(active tab)');
  }

  /**
   * Test themes can't be installed when the base theme or engine is missing.
   *
   * Include test for themes that have a missing base theme somewhere further up
   * the chain than the immediate base theme.
   */
  public function testInvalidTheme() {
    // theme_page_test_system_info_alter() un-hides all hidden themes.
    $this->container->get('module_installer')->install(['theme_page_test']);
    // Clear the system_list() and theme listing cache to pick up the change.
    $this->container->get('theme_handler')->reset();
    $this->drupalGet('admin/appearance');
    $this->assertText('This theme requires the base theme not_real_test_basetheme to operate correctly.');
    $this->assertText('This theme requires the base theme test_invalid_basetheme to operate correctly.');
    $this->assertText('This theme requires the theme engine not_real_engine to operate correctly.');
    // Check for the error text of a theme with the wrong core version
    // using 7.x and ^7.
    $incompatible_core_message = 'This theme is not compatible with Drupal ' . \Drupal::VERSION . ". Check that the .info.yml file contains a compatible 'core' or 'core_version_requirement' value.";
    $this->assertThemeIncompatibleText('Theme test with invalid semver core version', $incompatible_core_message);
    // Check for the error text of a theme without a content region.
    $this->assertText("This theme is missing a 'content' region.");
  }

  /**
   * Test uninstalling of themes works.
   */
  public function testUninstallingThemes() {
    // Install Bartik and set it as the default theme.
    \Drupal::service('theme_installer')->install(['bartik']);
    // Set up seven as the admin theme.
    \Drupal::service('theme_installer')->install(['seven']);
    $edit = [
      'admin_theme' => 'seven',
      'use_admin_theme' => TRUE,
    ];
    $this->drupalPostForm('admin/appearance', $edit, 'Save configuration');
    $this->drupalGet('admin/appearance');
    $this->clickLink(t('Set as default'));

    // Check that seven cannot be uninstalled as it is the admin theme.
    $this->assertNoRaw('Uninstall Seven theme');
    // Check that bartik cannot be uninstalled as it is the default theme.
    $this->assertNoRaw('Uninstall Bartik theme');
    // Check that the classy theme cannot be uninstalled as it is a base theme
    // of seven and bartik.
    $this->assertNoRaw('Uninstall Classy theme');

    // Install Stark and set it as the default theme.
    \Drupal::service('theme_installer')->install(['stark']);

    $edit = [
      'admin_theme' => 'stark',
      'use_admin_theme' => TRUE,
    ];
    $this->drupalPostForm('admin/appearance', $edit, 'Save configuration');

    // Check that seven can be uninstalled now.
    $this->assertRaw('Uninstall Seven theme');
    // Check that the classy theme still cannot be uninstalled as it is a
    // base theme of bartik.
    $this->assertNoRaw('Uninstall Classy theme');

    // Change the default theme to stark, stark is second in the list.
    $this->clickLink(t('Set as default'), 1);

    // Check that bartik can be uninstalled now.
    $this->assertRaw('Uninstall Bartik theme');

    // Check that the classy theme still can't be uninstalled as neither of its
    // base themes have been.
    $this->assertNoRaw('Uninstall Classy theme');

    // Uninstall each of the three themes starting with Bartik.
    $this->clickLink(t('Uninstall'));
    $this->assertRaw('The <em class="placeholder">Bartik</em> theme has been uninstalled');
    // Seven is the second in the list.
    $this->clickLink(t('Uninstall'));
    $this->assertRaw('The <em class="placeholder">Seven</em> theme has been uninstalled');

    // Check that the classy theme still can't be uninstalled as it is hidden.
    $this->assertNoRaw('Uninstall Classy theme');
  }

  /**
   * Tests installing a theme and setting it as default.
   */
  public function testInstallAndSetAsDefault() {
    $themes = [
      'bartik' => 'Bartik',
      'test_core_semver' => 'Theme test with semver core version',
    ];
    foreach ($themes as $theme_machine_name => $theme_name) {
      $this->drupalGet('admin/appearance');
      $this->getSession()->getPage()->findLink("Install $theme_name as default theme")->click();
      // Test the confirmation message.
      $this->assertText("$theme_name is now the default theme.");
      // Make sure the theme is now set as the default theme in config.
      $this->assertEqual($this->config('system.theme')->get('default'), $theme_machine_name);

      // This checks for a regression. See https://www.drupal.org/node/2498691.
      $this->assertNoText("The $theme_machine_name theme was not found.");

      $themes = \Drupal::service('theme_handler')->rebuildThemeData();
      $version = $themes[$theme_machine_name]->info['version'];

      // Confirm the theme is indicated as the default theme and administration
      // theme because the admin theme is the default theme.
      $out = $this->getSession()->getPage()->getContent();
      $this->assertTrue((bool) preg_match("/$theme_name " . preg_quote($version) . '\s{2,}\(default theme, administration theme\)/', $out));
    }
  }

  /**
   * Test the theme settings form when logo and favicon features are disabled.
   */
  public function testThemeSettingsNoLogoNoFavicon() {
    // Install theme with no logo and no favicon feature.
    $this->container->get('theme_installer')->install(['test_theme_settings_features']);
    // Visit this theme's settings page.
    $this->drupalGet('admin/appearance/settings/test_theme_settings_features');
    $edit = [];
    $this->drupalPostForm('admin/appearance/settings/test_theme_settings_features', $edit, 'Save configuration');
    $this->assertText('The configuration options have been saved.');
  }

  /**
   * Asserts that expected incompatibility text is displayed for a theme.
   *
   * @param string $theme_name
   *   Theme name to select element on page. This can be a partial name.
   * @param string $expected_text
   *   The expected incompatibility text.
   */
  private function assertThemeIncompatibleText($theme_name, $expected_text) {
    $this->assertSession()->elementExists('css', ".theme-info:contains(\"$theme_name\") .incompatible:contains(\"$expected_text\")");
  }

}
