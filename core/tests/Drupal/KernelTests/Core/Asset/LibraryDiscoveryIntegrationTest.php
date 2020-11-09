<?php

namespace Drupal\KernelTests\Core\Asset;

use Drupal\Core\Asset\Exception\InvalidLibrariesExtendSpecificationException;
use Drupal\Core\Asset\Exception\InvalidLibrariesOverrideSpecificationException;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the library discovery and library discovery parser.
 *
 * @group Render
 */
class LibraryDiscoveryIntegrationTest extends KernelTestBase {

  /**
   * The library discovery service.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['theme_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->container->get('theme_installer')->install(['test_theme', 'classy']);
    $this->libraryDiscovery = $this->container->get('library.discovery');
  }

  /**
   * Tests that hook_library_info is invoked and the cache is cleared.
   */
  public function testHookLibraryInfoByTheme() {
    // Activate test_theme and verify that the library 'kitten' is added using
    // hook_library_info_alter().
    $this->activateTheme('test_theme');
    $this->assertNotEmpty($this->libraryDiscovery->getLibraryByName('test_theme', 'kitten'));

    // Now make classy the active theme and assert that library is not added.
    $this->activateTheme('classy');
    $this->assertFalse($this->libraryDiscovery->getLibraryByName('test_theme', 'kitten'));
  }

  /**
   * Tests that libraries-override are applied to library definitions.
   */
  public function testLibrariesOverride() {
    // Assert some classy libraries that will be overridden or removed.
    $this->activateTheme('classy');
    $this->assertAssetInLibrary('core/themes/classy/css/components/button.css', 'classy', 'base', 'css');
    $this->assertAssetInLibrary('core/themes/classy/css/components/collapse-processed.css', 'classy', 'base', 'css');
    $this->assertAssetInLibrary('core/themes/classy/css/components/container-inline.css', 'classy', 'base', 'css');
    $this->assertAssetInLibrary('core/themes/classy/css/components/details.css', 'classy', 'base', 'css');
    $this->assertAssetInLibrary('core/themes/classy/css/components/dialog.css', 'classy', 'dialog', 'css');

    // Confirmatory assert on core library to be removed.
    $this->assertNotEmpty($this->libraryDiscovery->getLibraryByName('core', 'drupal.progress'), 'Confirmatory test on "core/drupal.progress"');

    // Activate test theme that defines libraries overrides.
    $this->activateTheme('test_theme');

    // Assert that entire library was correctly overridden.
    $this->assertEqual($this->libraryDiscovery->getLibraryByName('core', 'drupal.collapse'), $this->libraryDiscovery->getLibraryByName('test_theme', 'collapse'), 'Entire library correctly overridden.');

    // Assert that classy library assets were correctly overridden or removed.
    $this->assertNoAssetInLibrary('core/themes/classy/css/components/button.css', 'classy', 'base', 'css');
    $this->assertNoAssetInLibrary('core/themes/classy/css/components/collapse-processed.css', 'classy', 'base', 'css');
    $this->assertNoAssetInLibrary('core/themes/classy/css/components/container-inline.css', 'classy', 'base', 'css');
    $this->assertNoAssetInLibrary('core/themes/classy/css/components/details.css', 'classy', 'base', 'css');
    $this->assertNoAssetInLibrary('core/themes/classy/css/components/dialog.css', 'classy', 'dialog', 'css');

    $this->assertAssetInLibrary('core/modules/system/tests/themes/test_theme/css/my-button.css', 'classy', 'base', 'css');
    $this->assertAssetInLibrary('core/modules/system/tests/themes/test_theme/css/my-collapse-processed.css', 'classy', 'base', 'css');
    $this->assertAssetInLibrary('themes/my_theme/css/my-container-inline.css', 'classy', 'base', 'css');
    $this->assertAssetInLibrary('themes/my_theme/css/my-details.css', 'classy', 'base', 'css');

    // Assert that entire library was correctly removed.
    $this->assertFalse($this->libraryDiscovery->getLibraryByName('core', 'drupal.progress'), 'Entire library correctly removed.');

    // Assert that overridden library asset still retains attributes.
    $library = $this->libraryDiscovery->getLibraryByName('core', 'jquery');
    foreach ($library['js'] as $definition) {
      if ($definition['data'] == 'core/modules/system/tests/themes/test_theme/js/collapse.js') {
        $this->assertTrue($definition['minified']);
        $this->assertSame(-20, $definition['weight'], 'Previous attributes retained');
        break;
      }
    }
  }

  /**
   * Tests libraries-override on drupalSettings.
   */
  public function testLibrariesOverrideDrupalSettings() {
    // Activate test theme that attempts to override drupalSettings.
    $this->activateTheme('test_theme_libraries_override_with_drupal_settings');

    // Assert that drupalSettings cannot be overridden and throws an exception.
    try {
      $this->libraryDiscovery->getLibraryByName('core', 'drupal.ajax');
      $this->fail('Throw Exception when trying to override drupalSettings');
    }
    catch (InvalidLibrariesOverrideSpecificationException $e) {
      $expected_message = 'drupalSettings may not be overridden in libraries-override. Trying to override core/drupal.ajax/drupalSettings. Use hook_library_info_alter() instead.';
      $this->assertEqual($e->getMessage(), $expected_message, 'Throw Exception when trying to override drupalSettings');
    }
  }

  /**
   * Tests libraries-override on malformed assets.
   */
  public function testLibrariesOverrideMalformedAsset() {
    // Activate test theme that overrides with a malformed asset.
    $this->activateTheme('test_theme_libraries_override_with_invalid_asset');

    // Assert that improperly formed asset "specs" throw an exception.
    try {
      $this->libraryDiscovery->getLibraryByName('core', 'drupal.dialog');
      $this->fail('Throw Exception when specifying invalid override');
    }
    catch (InvalidLibrariesOverrideSpecificationException $e) {
      $expected_message = 'Library asset core/drupal.dialog/css is not correctly specified. It should be in the form "extension/library_name/sub_key/path/to/asset.js".';
      $this->assertEqual($e->getMessage(), $expected_message, 'Throw Exception when specifying invalid override');
    }
  }

  /**
   * Tests library assets with other ways for specifying paths.
   */
  public function testLibrariesOverrideOtherAssetLibraryNames() {
    // Activate a test theme that defines libraries overrides on other types of
    // assets.
    $this->activateTheme('test_theme');

    // Assert Drupal-relative paths.
    $this->assertAssetInLibrary('themes/my_theme/css/dropbutton.css', 'core', 'drupal.dropbutton', 'css');

    // Assert stream wrapper paths.
    $this->assertAssetInLibrary('public://my_css/vertical-tabs.css', 'core', 'drupal.vertical-tabs', 'css');

    // Assert a protocol-relative URI.
    $this->assertAssetInLibrary('//my-server/my_theme/css/jquery_ui.css', 'core', 'jquery.ui', 'css');

    // Assert an absolute URI.
    $this->assertAssetInLibrary('http://example.com/my_theme/css/farbtastic.css', 'core', 'jquery.farbtastic', 'css');
  }

  /**
   * Tests that base theme libraries-override still apply in sub themes.
   */
  public function testBaseThemeLibrariesOverrideInSubTheme() {
    // Activate a test theme that has subthemes.
    $this->activateTheme('test_subtheme');

    // Assert that libraries-override specified in the base theme still applies
    // in the sub theme.
    $this->assertNoAssetInLibrary('core/misc/dialog/dialog.js', 'core', 'drupal.dialog', 'js');
    $this->assertAssetInLibrary('core/modules/system/tests/themes/test_basetheme/css/farbtastic.css', 'core', 'jquery.farbtastic', 'css');
  }

  /**
   * Tests libraries-extend.
   */
  public function testLibrariesExtend() {
    // Activate classy themes and verify the libraries are not extended.
    $this->activateTheme('classy');
    $this->assertNoAssetInLibrary('core/modules/system/tests/themes/test_theme_libraries_extend/css/extend_1.css', 'classy', 'book-navigation', 'css');
    $this->assertNoAssetInLibrary('core/modules/system/tests/themes/test_theme_libraries_extend/js/extend_1.js', 'classy', 'book-navigation', 'js');
    $this->assertNoAssetInLibrary('core/modules/system/tests/themes/test_theme_libraries_extend/css/extend_2.css', 'classy', 'book-navigation', 'css');

    // Activate the theme that extends the book-navigation library in classy.
    $this->activateTheme('test_theme_libraries_extend');
    $this->assertAssetInLibrary('core/modules/system/tests/themes/test_theme_libraries_extend/css/extend_1.css', 'classy', 'book-navigation', 'css');
    $this->assertAssetInLibrary('core/modules/system/tests/themes/test_theme_libraries_extend/js/extend_1.js', 'classy', 'book-navigation', 'js');
    $this->assertAssetInLibrary('core/modules/system/tests/themes/test_theme_libraries_extend/css/extend_2.css', 'classy', 'book-navigation', 'css');

    // Activate a sub theme and confirm that it inherits the library assets
    // extended in the base theme as well as its own.
    $this->assertNoAssetInLibrary('core/modules/system/tests/themes/test_basetheme/css/base-libraries-extend.css', 'classy', 'base', 'css');
    $this->assertNoAssetInLibrary('core/modules/system/tests/themes/test_subtheme/css/sub-libraries-extend.css', 'classy', 'base', 'css');
    $this->activateTheme('test_subtheme');
    $this->assertAssetInLibrary('core/modules/system/tests/themes/test_basetheme/css/base-libraries-extend.css', 'classy', 'base', 'css');
    $this->assertAssetInLibrary('core/modules/system/tests/themes/test_subtheme/css/sub-libraries-extend.css', 'classy', 'base', 'css');

    // Activate test theme that extends with a non-existent library. An
    // exception should be thrown.
    $this->activateTheme('test_theme_libraries_extend');
    try {
      $this->libraryDiscovery->getLibraryByName('core', 'drupal.dialog');
      $this->fail('Throw Exception when specifying non-existent libraries-extend.');
    }
    catch (InvalidLibrariesExtendSpecificationException $e) {
      $expected_message = 'The specified library "test_theme_libraries_extend/non_existent_library" does not exist.';
      $this->assertEqual($e->getMessage(), $expected_message, 'Throw Exception when specifying non-existent libraries-extend.');
    }

    // Also, test non-string libraries-extend. An exception should be thrown.
    $this->container->get('theme_installer')->install(['test_theme']);
    try {
      $this->libraryDiscovery->getLibraryByName('test_theme', 'collapse');
      $this->fail('Throw Exception when specifying non-string libraries-extend.');
    }
    catch (InvalidLibrariesExtendSpecificationException $e) {
      $expected_message = 'The libraries-extend specification for each library must be a list of strings.';
      $this->assertEqual($e->getMessage(), $expected_message, 'Throw Exception when specifying non-string libraries-extend.');
    }
  }

  /**
   * Test deprecated libraries.
   *
   * @group legacy
   */
  public function testDeprecatedLibrary() {
    $this->expectDeprecation('Theme "theme_test" is overriding a deprecated library. The "theme_test/deprecated_library" asset library is deprecated in drupal:X.0.0 and is removed from drupal:Y.0.0. Use another library instead. See https://www.example.com');
    $this->expectDeprecation('Theme "theme_test" is extending a deprecated library. The "theme_test/another_deprecated_library" asset library is deprecated in drupal:X.0.0 and is removed from drupal:Y.0.0. Use another library instead. See https://www.example.com');
    $this->expectDeprecation('The "theme_test/deprecated_library" asset library is deprecated in drupal:X.0.0 and is removed from drupal:Y.0.0. Use another library instead. See https://www.example.com');
    $this->expectDeprecation('The "theme_test/another_deprecated_library" asset library is deprecated in drupal:X.0.0 and is removed from drupal:Y.0.0. Use another library instead. See https://www.example.com');
    $this->activateTheme('test_legacy_theme');
    $this->libraryDiscovery->getLibraryByName('theme_test', 'deprecated_library');
    $this->libraryDiscovery->getLibraryByName('theme_test', 'another_deprecated_library');
  }

  /**
   * Activates a specified theme.
   *
   * Installs the theme if not already installed and makes it the active theme.
   *
   * @param string $theme_name
   *   The name of the theme to be activated.
   */
  protected function activateTheme($theme_name) {
    $this->container->get('theme_installer')->install([$theme_name]);

    /** @var \Drupal\Core\Theme\ThemeInitializationInterface $theme_initializer */
    $theme_initializer = $this->container->get('theme.initialization');

    /** @var \Drupal\Core\Theme\ThemeManagerInterface $theme_manager */
    $theme_manager = $this->container->get('theme.manager');

    $theme_manager->setActiveTheme($theme_initializer->getActiveThemeByName($theme_name));

    $this->libraryDiscovery->clearCachedDefinitions();

    $this->assertSame($theme_name, $theme_manager->getActiveTheme()->getName());
  }

  /**
   * Asserts that the specified asset is in the given library.
   *
   * @param string $asset
   *   The asset file with the path for the file.
   * @param string $extension
   *   The extension in which the $library is defined.
   * @param string $library_name
   *   Name of the library.
   * @param mixed $sub_key
   *   The library sub key where the given asset is defined.
   * @param string $message
   *   (optional) A message to display with the assertion.
   *
   * @return bool
   *   TRUE if the specified asset is found in the library.
   */
  protected function assertAssetInLibrary($asset, $extension, $library_name, $sub_key, $message = NULL) {
    if (!isset($message)) {
      $message = sprintf('Asset %s found in library "%s/%s"', $asset, $extension, $library_name);
    }
    $library = $this->libraryDiscovery->getLibraryByName($extension, $library_name);
    foreach ($library[$sub_key] as $definition) {
      if ($asset == $definition['data']) {
        return TRUE;
      }
    }
    return $this->fail($message);
  }

  /**
   * Asserts that the specified asset is not in the given library.
   *
   * @param string $asset
   *   The asset file with the path for the file.
   * @param string $extension
   *   The extension in which the $library_name is defined.
   * @param string $library_name
   *   Name of the library.
   * @param mixed $sub_key
   *   The library sub key where the given asset is defined.
   * @param string $message
   *   (optional) A message to display with the assertion.
   *
   * @return bool
   *   TRUE if the specified asset is not found in the library.
   */
  protected function assertNoAssetInLibrary($asset, $extension, $library_name, $sub_key, $message = NULL) {
    if (!isset($message)) {
      $message = sprintf('Asset %s not found in library "%s/%s"', $asset, $extension, $library_name);
    }
    $library = $this->libraryDiscovery->getLibraryByName($extension, $library_name);
    foreach ($library[$sub_key] as $definition) {
      if ($asset == $definition['data']) {
        return $this->fail($message);
      }
    }
    return TRUE;
  }

}
