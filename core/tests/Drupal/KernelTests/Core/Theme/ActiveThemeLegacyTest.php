<?php

namespace Drupal\KernelTests\Core\Theme;

use Drupal\Core\Theme\ActiveTheme;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests legacy code in ActiveTheme.
 *
 * @coversDefaultClass \Drupal\Core\Theme\ActiveTheme
 * @group Theme
 * @group legacy
 */
class ActiveThemeLegacyTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * Tests \Drupal\Core\Theme\ActiveTheme::getBaseThemes() deprecation.
   *
   * @covers ::getBaseThemes
   * @expectedDeprecation \Drupal\Core\Theme\ActiveTheme::getBaseThemes() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Theme\ActiveTheme::getBaseThemeExtensions() instead. See https://www.drupal.org/node/3019948
   */
  public function testGetBaseThemes() {
    $this->container->get('theme_installer')->install(['test_subsubtheme']);
    $this->config('system.theme')->set('default', 'test_subsubtheme')->save();
    $active_theme = \Drupal::theme()->getActiveTheme();

    // Ensure that active theme representations of base themes can be retrieved.
    $base_themes = $active_theme->getBaseThemes();
    $this->assertInstanceOf(ActiveTheme::class, $base_themes['test_subtheme']);
    $this->assertSame(['test_subtheme', 'test_basetheme'], array_keys($base_themes));

    // Ensure that we can get base themes from base themes.
    $test_subtheme_base_themes = $base_themes['test_subtheme']->getBaseThemes();
    $this->assertSame(['test_basetheme'], array_keys($test_subtheme_base_themes));
    $this->assertEmpty($test_subtheme_base_themes['test_basetheme']->getBaseThemes());
  }

  /**
   * Tests BC layer in constructor.
   *
   * @covers ::__construct
   * @expectedDeprecation The 'base_themes' key is deprecated in Drupal 8.7.0  and support for it will be removed in Drupal 9.0.0. Use 'base_theme_extensions' instead. See https://www.drupal.org/node/3019948
   */
  public function testConstructor() {
    $themes = $this->container->get('extension.list.theme')->getList();

    $values = [
      'name' => $themes['test_basetheme']->getName(),
      'extension' => $themes['test_basetheme'],
    ];
    $base_active_theme = new ActiveTheme($values);

    $values = [
      'name' => $themes['test_subtheme']->getName(),
      'extension' => $themes['test_subtheme'],
      'base_themes' => ['test_basetheme' => $base_active_theme],
    ];
    $active_theme = new ActiveTheme($values);

    $base_extensions = $active_theme->getBaseThemeExtensions();
    $this->assertSame($base_extensions['test_basetheme'], $themes['test_basetheme']);
    $this->assertSame(['test_basetheme'], array_keys($base_extensions));
  }

}
