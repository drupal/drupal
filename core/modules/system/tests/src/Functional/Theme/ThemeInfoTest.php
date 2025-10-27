<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Theme;

use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests processing of theme .info.yml properties.
 */
#[Group('Theme')]
#[RunTestsInSeparateProcesses]
class ThemeInfoTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['theme_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests libraries-override.
   */
  public function testStylesheets(): void {
    $this->container->get('theme_installer')->install(['test_base_theme', 'test_subtheme']);
    $this->config('system.theme')
      ->set('default', 'test_subtheme')
      ->save();

    $base = $this->getThemePath('test_base_theme');
    $sub = $this->getThemePath('test_subtheme') . '/css';

    // All removals are expected to be based on a file's path and name and
    // should work nevertheless.
    $this->drupalGet('theme-test/info/stylesheets');

    $this->assertSession()->elementsCount('xpath', '//link[contains(@href, "' . $base . '/base-add.css")]', 1);
    $this->assertSession()->elementNotExists('xpath', '//link[contains(@href, "base-remove.css")]');

    $this->assertSession()->elementsCount('xpath', '//link[contains(@href, "' . $sub . '/sub-add.css")]', 1);
    $this->assertSession()->elementNotExists('xpath', '//link[contains(@href, "sub-remove.css")]');
    $this->assertSession()->elementNotExists('xpath', '//link[contains(@href, "base-add.sub-remove.css")]');

    // Verify that CSS files with the same name are loaded from both the base
    // theme and subtheme.
    $this->assertSession()->elementsCount('xpath', '//link[contains(@href, "' . $base . '/same-name.css")]', 1);
    $this->assertSession()->elementsCount('xpath', '//link[contains(@href, "' . $sub . '/same-name.css")]', 1);

  }

  /**
   * Tests that changes to the info file are picked up.
   */
  public function testChanges(): void {
    $this->container->get('theme_installer')->install(['test_theme']);
    $this->config('system.theme')->set('default', 'test_theme')->save();
    $this->container->get('theme.manager')->resetActiveTheme();

    $active_theme = $this->container->get('theme.manager')->getActiveTheme();
    // Make sure we are not testing the wrong theme.
    $this->assertEquals('test_theme', $active_theme->getName());
    $this->assertEquals(
      ['starterkit_theme/base', 'starterkit_theme/messages', 'core/normalize', 'test_theme/global-styling'],
      $active_theme->getLibraries(),
    );

    // @see theme_test_system_info_alter()
    $this->container->get('state')->set('theme_test.modify_info_files', TRUE);
    $this->resetAll();
    $active_theme = $this->container->get('theme.manager')->getActiveTheme();
    $this->assertEquals(
      ['starterkit_theme/base', 'starterkit_theme/messages', 'core/normalize', 'test_theme/global-styling', 'core/once'],
      $active_theme->getLibraries(),
    );
  }

}
