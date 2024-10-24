<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\System;

use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests the theme administration user interface.
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
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'block', 'file'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'starterkit_theme';

  /**
   * A test node.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected Node $node;

  /**
   * {@inheritdoc}
   */
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
   * Tests the theme settings form.
   */
  public function testThemeSettings(): void {
    // Ensure a disabled theme settings form URL returns 404.
    $this->drupalGet('admin/appearance/settings/olivero');
    $this->assertSession()->statusCodeEquals(404);
    // Ensure a non existent theme settings form URL returns 404.
    $this->drupalGet('admin/appearance/settings/' . $this->randomMachineName());
    $this->assertSession()->statusCodeEquals(404);
    // Ensure a hidden theme settings form URL returns 404.
    $this->assertTrue(\Drupal::service('theme_installer')->install(['stable9']));
    $this->drupalGet('admin/appearance/settings/stable9');
    $this->assertSession()->statusCodeEquals(404);

    // Specify a filesystem path to be used for the logo.
    $file = current($this->drupalGetTestFiles('image'));
    $file_relative = strtr($file->uri, ['public:/' => PublicStream::basePath()]);
    $default_theme_path = 'core/themes/starterkit_theme';

    /** @var \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator */
    $file_url_generator = \Drupal::service('file_url_generator');
    $supported_paths = [
      // Raw stream wrapper URI.
      $file->uri => [
        'form' => StreamWrapperManager::getTarget($file->uri),
        'src' => $file_url_generator->generateString($file->uri),
      ],
      // Relative path within the public filesystem.
      StreamWrapperManager::getTarget($file->uri) => [
        'form' => StreamWrapperManager::getTarget($file->uri),
        'src' => $file_url_generator->generateString($file->uri),
      ],
      // Relative path to a public file.
      $file_relative => [
        'form' => $file_relative,
        'src' => $file_url_generator->generateString($file->uri),
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
      $this->drupalGet('admin/appearance/settings');
      $this->submitForm($edit, 'Save configuration');
      $this->assertSession()->pageTextNotContains('The custom logo path is invalid.');
      $this->assertSession()->fieldValueEquals('logo_path', $expected['form']);

      // Verify logo path examples.
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
      $xpath = "//div[contains(@class, 'js-form-item-logo-path')]/div[@class='description']/code";
      $this->assertSession()->elementTextEquals('xpath', "{$xpath}[1]", $implicit_public_file);
      $this->assertSession()->elementTextEquals('xpath', "{$xpath}[2]", $explicit_file);
      $this->assertSession()->elementTextEquals('xpath', "{$xpath}[3]", $local_file);

      // Verify the actual 'src' attribute of the logo being output in a site
      // branding block.
      $this->drupalPlaceBlock('system_branding_block', ['region' => 'header']);
      $this->drupalGet('');
      $this->assertSession()->elementAttributeContains('xpath', '//header//a[@rel="home"]/img', 'src', $expected['src']);
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
      $this->assertSession()->pageTextContains('The custom logo path is invalid.');
    }

    // Upload a file to use for the logo. Try both the test image we've been
    // using so far and an SVG file.
    $upload_uris = [$file->uri, 'core/themes/olivero/logo.svg'];
    $this->drupalPlaceBlock('system_branding_block', ['region' => 'header']);
    foreach ($upload_uris as $upload_uri) {
      $edit = [
        'default_logo' => FALSE,
        'logo_path' => '',
        'files[logo_upload]' => \Drupal::service('file_system')->realpath($upload_uri),
      ];
      $this->drupalGet('admin/appearance/settings');
      $this->submitForm($edit, 'Save configuration');
      $this->assertSession()->pageTextContains('The configuration options have been saved.');

      $uploaded_filename = 'public://' . $this->getSession()->getPage()->findField('logo_path')->getValue();
      $this->drupalGet('');
      $this->assertSession()->elementAttributeContains('xpath', '//header//a[@rel="home"]/img', 'src', $file_url_generator->generateString($uploaded_filename));

      // Clear the logo or it will use previous value.
      $edit = [
        'default_logo' => FALSE,
        'logo_path' => '',
        'files[logo_upload]' => '',
      ];
      $this->drupalGet('admin/appearance/settings');
      $this->submitForm($edit, 'Save configuration');
    }

    $this->container->get('theme_installer')->install(['olivero']);

    // Ensure only valid themes are listed in the local tasks.
    $this->drupalPlaceBlock('local_tasks_block', ['region' => 'header']);
    $this->drupalGet('admin/appearance/settings');
    $theme_handler = \Drupal::service('theme_handler');
    $this->assertSession()->linkExists($theme_handler->getName('starterkit_theme'));
    $this->assertSession()->linkExists($theme_handler->getName('olivero'));
    $this->assertSession()->linkNotExists($theme_handler->getName('stable9'));

    // If a hidden theme is an admin theme it should be viewable.
    \Drupal::configFactory()->getEditable('system.theme')->set('admin', 'stable9')->save();
    \Drupal::service('router.builder')->rebuildIfNeeded();
    $this->drupalPlaceBlock('local_tasks_block', ['region' => 'header', 'theme' => 'stable9']);
    $this->drupalGet('admin/appearance/settings');
    $this->assertSession()->linkExists($theme_handler->getName('stable9'));
    $this->drupalGet('admin/appearance/settings/stable9');
    $this->assertSession()->statusCodeEquals(200);

    // Ensure default logo and favicons are not triggering custom path
    // validation errors if their custom paths are set on the form.
    $edit = [
      'default_logo' => TRUE,
      'logo_path' => 'public://whatever.png',
      'default_favicon' => TRUE,
      'favicon_path' => 'public://whatever.ico',
    ];
    $this->drupalGet('admin/appearance/settings');
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->pageTextNotContains('The custom logo path is invalid.');
    $this->assertSession()->pageTextNotContains('The custom favicon path is invalid.');
  }

  /**
   * Tests the theme settings logo form.
   */
  public function testThemeSettingsLogo(): void {
    // Visit Olivero's theme settings page to replace the logo.
    $this->container->get('theme_installer')->install(['olivero']);
    $this->drupalGet('admin/appearance/settings/olivero');
    $edit = [
      'default_logo' => FALSE,
      'logo_path' => 'core/misc/druplicon.png',
    ];
    $this->drupalGet('admin/appearance/settings/olivero');
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->fieldValueEquals('default_logo', FALSE);
    $this->assertSession()->fieldValueEquals('logo_path', 'core/misc/druplicon.png');

    // Make sure the logo and favicon settings are not available when the file
    // module is not enabled.
    \Drupal::service('module_installer')->uninstall(['file']);
    $this->drupalGet('admin/appearance/settings');
    $this->assertSession()->pageTextNotContains('Logo image settings');
    $this->assertSession()->pageTextNotContains('Shortcut icon settings');
  }

  /**
   * Tests the theme settings color input.
   */
  public function testThemeSettingsColorHexCode() : void {
    // Install the Olivero theme.
    $this->container->get('theme_installer')->install(['olivero']);

    // Define invalid and valid hex color codes.
    $invalid_hex_codes = [
      'xyz',
      '#xyz',
      '#ffff',
      '#00000',
      '#FFFFF ',
      '00#000',
    ];
    $valid_hex_codes = [
      '0F0',
      '#F0F',
      '#2ecc71',
      '0074cc',
    ];

    // Visit Olivero's theme settings page.
    $this->drupalGet('admin/appearance/settings/olivero');

    // Test invalid hex color codes.
    foreach ($invalid_hex_codes as $invalid_hex) {
      $this->submitForm(['base_primary_color' => $invalid_hex], 'Save configuration');
      // Invalid hex codes should throw error.
      $this->assertSession()->statusMessageContains('"' . $invalid_hex . '" is not a valid hexadecimal color.', 'error');
      $this->assertTrue($this->getSession()->getPage()->findField('base_primary_color')->hasClass('error'));
    }

    // Test valid hex color codes.
    foreach ($valid_hex_codes as $valid_hex) {
      $this->submitForm(['base_primary_color' => $valid_hex], 'Save configuration');
      $this->assertSession()->statusMessageContains('The configuration options have been saved.', 'status');
      $this->assertSame($valid_hex, $this->config('olivero.settings')->get('base_primary_color'));
    }
  }

  /**
   * Tests the 'rendered' cache tag is cleared when saving theme settings.
   */
  public function testThemeSettingsRenderCacheClear(): void {
    $this->container->get('theme_installer')->install(['olivero']);
    // Ensure the frontpage is cached for anonymous users. The render cache will
    // cleared by installing a theme.
    $this->drupalLogout();
    $this->drupalGet('');
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'MISS');
    $this->drupalGet('');
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'HIT');

    $this->drupalLogin($this->adminUser);
    // Save Olivero's theme settings which should invalidate the 'rendered' cache
    // tag in \Drupal\system\EventSubscriber\ConfigCacheTag.
    $this->drupalGet('admin/appearance/settings/olivero');
    $this->submitForm([], 'Save configuration');
    $this->drupalLogout();
    $this->drupalGet('');
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'MISS');
  }

  /**
   * Tests the administration theme functionality.
   */
  public function testAdministrationTheme(): void {
    $this->container->get('theme_installer')->install(['claro']);

    // Install an administration theme and show it on the node admin pages.
    $edit = [
      'admin_theme' => 'claro',
      'use_admin_theme' => TRUE,
    ];
    $this->drupalGet('admin/appearance');
    $this->submitForm($edit, 'Save configuration');

    // Check the display of non stable themes.
    $themes = \Drupal::service('extension.list.theme')->reset()->getList();
    $experimental_version = $themes['experimental_theme_test']->info['version'];
    $deprecated_version = $themes['deprecated_theme_test']->info['version'];
    $this->drupalGet('admin/appearance');
    $this->assertSession()->pageTextContains('Experimental test ' . $experimental_version . ' (experimental theme)');
    $this->assertSession()->pageTextContains('Test deprecated theme ' . $deprecated_version . ' (Deprecated)');
    $this->assertSession()->elementExists('xpath', "//a[contains(@href, 'http://example.com/deprecated_theme')]");

    // Check that the administration theme is used on an administration page.
    $this->drupalGet('admin/config');
    $this->assertSession()->responseContains('core/themes/claro');

    // Check that the site default theme used on node page.
    $this->drupalGet('node/' . $this->node->id());
    $this->assertSession()->responseContains('core/themes/starterkit_theme');

    // Check that the administration theme is used on the add content page.
    $this->drupalGet('node/add');
    $this->assertSession()->responseContains('core/themes/claro');

    // Check that the administration theme is used on the edit content page.
    $this->drupalGet('node/' . $this->node->id() . '/edit');
    $this->assertSession()->responseContains('core/themes/claro');

    // Disable the admin theme on the node admin pages.
    $edit = [
      'use_admin_theme' => FALSE,
    ];
    $this->drupalGet('admin/appearance');
    $this->submitForm($edit, 'Save configuration');

    // Check that obsolete themes are not displayed.
    $this->drupalGet('admin/appearance');
    $this->assertSession()->pageTextNotContains('Obsolete test theme');

    // Check that the administration theme is used on an administration page.
    $this->drupalGet('admin/config');
    $this->assertSession()->responseContains('core/themes/claro');

    // Ensure that the admin theme is also visible on the 403 page.
    $normal_user = $this->drupalCreateUser(['view the administration theme']);
    $this->drupalLogin($normal_user);
    // Check that the administration theme is used on an administration page.
    $this->drupalGet('admin/config');
    $this->assertSession()->statusCodeEquals(403);
    $this->assertSession()->responseContains('core/themes/claro');
    $this->drupalLogin($this->adminUser);

    // Check that the site default theme used on the add content page.
    $this->drupalGet('node/add');
    $this->assertSession()->responseContains('core/themes/starterkit_theme');

    // Reset to the default theme settings.
    $edit = [
      'admin_theme' => '',
      'use_admin_theme' => FALSE,
    ];
    $this->drupalGet('admin/appearance');
    $this->submitForm($edit, 'Save configuration');

    // Check that the site default theme used on administration page.
    $this->drupalGet('admin');
    $this->assertSession()->responseContains('core/themes/starterkit_theme');

    // Check that the site default theme used on the add content page.
    $this->drupalGet('node/add');
    $this->assertSession()->responseContains('core/themes/starterkit_theme');
  }

  /**
   * Tests switching the default theme.
   */
  public function testSwitchDefaultTheme(): void {
    /** @var \Drupal\Core\Extension\ThemeInstallerInterface $theme_installer */
    $theme_installer = \Drupal::service('theme_installer');
    // First, install Stark and set it as the default theme programmatically.
    $theme_installer->install(['stark']);
    $this->config('system.theme')->set('default', 'stark')->save();
    $this->drupalPlaceBlock('local_tasks_block');

    // Install Olivero and set it as the default theme.
    $theme_installer->install(['olivero']);
    $this->drupalGet('admin/appearance');
    $this->clickLink('Set as default');
    $this->assertEquals('olivero', $this->config('system.theme')->get('default'));

    // Test the default theme on the secondary links (blocks admin page).
    $this->drupalGet('admin/structure/block');
    $this->assertSession()->pageTextContains('Olivero');
    // Switch back to Stark and test again to test that the menu cache is cleared.
    $this->drupalGet('admin/appearance');
    // Stark is the first 'Set as default' link.
    $this->clickLink('Set as default');
    $this->drupalGet('admin/structure/block');
    $this->assertSession()->pageTextContains('Stark');
  }

  /**
   * Tests themes can't be installed when the base theme or engine is missing.
   *
   * Include test for themes that have a missing base theme somewhere further up
   * the chain than the immediate base theme.
   */
  public function testInvalidTheme(): void {
    // theme_page_test_system_info_alter() un-hides all hidden themes.
    $this->container->get('module_installer')->install(['theme_page_test']);
    // Clear the system_list() and theme listing cache to pick up the change.
    $this->container->get('theme_handler')->reset();
    $this->drupalGet('admin/appearance');
    $this->assertSession()->pageTextContains('This theme requires the base theme not_real_test_base_theme to operate correctly.');
    $this->assertSession()->pageTextContains('This theme requires the base theme test_invalid_base_theme to operate correctly.');
    $this->assertSession()->pageTextContains('This theme requires the theme engine not_real_engine to operate correctly.');
    // Check for the error text of a theme with the wrong core version
    // using 7.x and ^7.
    $incompatible_core_message = 'This theme is not compatible with Drupal ' . \Drupal::VERSION . ". Check that the .info.yml file contains a compatible 'core' or 'core_version_requirement' value.";
    $this->assertThemeIncompatibleText('Theme test with invalid semver core version', $incompatible_core_message);
    // Check for the error text of a theme without a content region.
    $this->assertSession()->pageTextContains("This theme is missing a 'content' region.");
  }

  /**
   * Tests uninstalling of themes works.
   */
  public function testUninstallingThemes(): void {
    // Install olivero.
    \Drupal::service('theme_installer')->install(['olivero']);
    // Set up Claro as the admin theme.
    \Drupal::service('theme_installer')->install(['claro']);
    $edit = [
      'admin_theme' => 'claro',
      'use_admin_theme' => TRUE,
    ];
    $this->drupalGet('admin/appearance');
    $this->submitForm($edit, 'Save configuration');

    // Set olivero as the default theme.
    $this->cssSelect('a[title="Set Olivero as default theme"]')[0]->click();
    // Check that claro cannot be uninstalled as it is the admin theme.
    $this->assertSession()->responseNotContains('Uninstall claro theme');
    // Check that olivero cannot be uninstalled as it is the default theme.
    $this->assertSession()->responseNotContains('Uninstall Olivero theme');

    // Install Stark and set it as the default theme.
    \Drupal::service('theme_installer')->install(['stark']);

    $edit = [
      'admin_theme' => 'stark',
      'use_admin_theme' => TRUE,
    ];
    $this->drupalGet('admin/appearance');
    $this->submitForm($edit, 'Save configuration');

    // Check that claro can be uninstalled now.
    $this->assertSession()->responseContains('Uninstall claro theme');

    // Change the default theme to stark, stark is second in the list.
    $this->clickLink('Set as default', 1);

    // Check that olivero can be uninstalled now.
    $this->assertSession()->responseContains('Uninstall Olivero theme');

    // Uninstall each of the two themes starting with Olivero.
    $this->cssSelect('a[title="Uninstall Olivero theme"]')[0]->click();
    $this->assertSession()->responseContains('The <em class="placeholder">Olivero</em> theme has been uninstalled');
    $this->cssSelect('a[title="Uninstall Claro theme"]')[0]->click();
    $this->assertSession()->responseContains('The <em class="placeholder">Claro</em> theme has been uninstalled');
  }

  /**
   * Tests installing a theme and setting it as default.
   */
  public function testInstallAndSetAsDefault(): void {
    $this->markTestSkipped('Skipped due to major version-specific logic. See https://www.drupal.org/project/drupal/issues/3359322');
    $themes = [
      'olivero' => 'Olivero',
      'test_core_semver' => 'Theme test with semver core version',
    ];
    foreach ($themes as $theme_machine_name => $theme_name) {
      $this->drupalGet('admin/appearance');
      $this->getSession()->getPage()->findLink("Install $theme_name as default theme")->click();
      // Test the confirmation message.
      $this->assertSession()->pageTextContains("$theme_name is now the default theme.");
      // Make sure the theme is now set as the default theme in config.
      $this->assertEquals($theme_machine_name, $this->config('system.theme')->get('default'));

      // This checks for a regression. See https://www.drupal.org/node/2498691.
      $this->assertSession()->pageTextNotContains("The $theme_machine_name theme was not found.");

      $themes = \Drupal::service('extension.list.theme')->reset()->getList();
      $version = $themes[$theme_machine_name]->info['version'];

      // Confirm the theme is indicated as the default theme and administration
      // theme because the admin theme is the default theme.
      $out = $this->getSession()->getPage()->getContent();
      $this->assertTrue((bool) preg_match("/$theme_name " . preg_quote($version) . '\s{2,}\(default theme, administration theme\)/', $out));
    }
  }

  /**
   * Tests the theme settings form when logo and favicon features are disabled.
   */
  public function testThemeSettingsNoLogoNoFavicon(): void {
    // Install theme with no logo and no favicon feature.
    $this->container->get('theme_installer')->install(['test_theme_settings_features']);
    // Visit this theme's settings page.
    $this->drupalGet('admin/appearance/settings/test_theme_settings_features');
    $edit = [];
    $this->drupalGet('admin/appearance/settings/test_theme_settings_features');
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
  }

  /**
   * Asserts that expected incompatibility text is displayed for a theme.
   *
   * @param string $theme_name
   *   Theme name to select element on page. This can be a partial name.
   * @param string $expected_text
   *   The expected incompatibility text.
   */
  private function assertThemeIncompatibleText(string $theme_name, string $expected_text): void {
    $this->assertSession()->elementExists('css', ".theme-info:contains(\"$theme_name\") .incompatible:contains(\"$expected_text\")");
  }

}
