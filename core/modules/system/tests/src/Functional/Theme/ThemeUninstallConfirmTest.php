<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Theme;

use Drupal\Tests\BrowserTestBase;
use Drupal\user\UserInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the theme uninstall confirmation form.
 */
#[Group('Theme')]
#[RunTestsInSeparateProcesses]
class ThemeUninstallConfirmTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'block'];

  /**
   * An admin user with permission to administer themes.
   */
  protected UserInterface $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser(['administer themes']);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests the theme uninstall confirmation form basic workflow.
   */
  public function testThemeUninstallConfirmForm(): void {
    \Drupal::service('theme_installer')->install(['claro']);

    $this->drupalGet('admin/appearance');
    $this->clickLink('Uninstall Claro theme');
    $this->drupalGet('admin/appearance/uninstall', ['query' => ['theme' => 'claro']]);

    $this->assertSession()->pageTextContains('Uninstall Claro theme');
    $this->assertSession()->pageTextContains('The Claro theme will be completely uninstalled from your site, and all data from this theme will be lost!');
    $this->assertSession()->buttonExists('Uninstall');
    $this->assertSession()->linkExists('Cancel');
    $this->submitForm([], 'Uninstall');

    $this->assertSession()->pageTextContains('The Claro theme has been uninstalled');
    $this->assertSession()->addressEquals('admin/appearance');
    $this->assertSession()->linkExists('Install Claro theme');

    \Drupal::service('theme_handler')->refreshInfo();
    $themes = \Drupal::service('theme_handler')->listInfo();
    $this->assertArrayNotHasKey('claro', $themes);
  }

  /**
   * Tests that the default theme cannot be uninstalled.
   */
  public function testCannotUninstallDefaultTheme(): void {
    $this->drupalGet("admin/appearance/uninstall", ['query' => ['theme' => 'stark']]);

    $this->assertSession()->addressEquals('admin/appearance');
    $this->assertSession()->pageTextContains('Stark is the default theme and cannot be uninstalled');

    \Drupal::service('theme_handler')->refreshInfo();
    $themes = \Drupal::service('theme_handler')->listInfo();
    $this->assertNotEmpty($themes['stark']->status);
  }

  /**
   * Tests that the admin theme cannot be uninstalled.
   */
  public function testCannotUninstallAdminTheme(): void {
    \Drupal::service('theme_installer')->install(['claro']);
    $this->config('system.theme')->set('admin', 'claro')->save();
    $this->drupalGet('admin/appearance/uninstall', ['query' => ['theme' => 'claro']]);

    $this->assertSession()->addressEquals('admin/appearance');
    $this->assertSession()->pageTextContains('Claro is the admin theme and cannot be uninstalled');

    \Drupal::service('theme_handler')->refreshInfo();
    $themes = \Drupal::service('theme_handler')->listInfo();
    $this->assertNotEmpty($themes['claro']->status);
  }

  /**
   * Tests that a base theme with dependent sub-themes cannot be uninstalled.
   */
  public function testCannotUninstallBaseThemeWithDependentSubThemes(): void {
    \Drupal::service('theme_installer')->install(['test_base_theme', 'test_subtheme']);
    $this->drupalGet('admin/appearance/uninstall', ['query' => ['theme' => 'test_base_theme']]);

    $this->assertSession()->addressEquals('admin/appearance');
    $this->assertSession()->pageTextContains('Theme test base theme cannot be uninstalled because the following themes depend on it: Theme test subtheme');

    $this->drupalGet('admin/appearance/uninstall', ['query' => ['theme' => 'test_subtheme']]);
    $this->submitForm([], 'Uninstall');
    $this->assertSession()->pageTextContains('The Theme test subtheme theme has been uninstalled');

    $this->drupalGet('admin/appearance/uninstall', ['query' => ['theme' => 'test_base_theme']]);
    $this->submitForm([], 'Uninstall');
    $this->assertSession()->pageTextContains('The Theme test base theme theme has been uninstalled');
  }

  /**
   * Tests that configuration dependencies are displayed.
   */
  public function testConfigDependenciesDisplayed(): void {
    \Drupal::service('theme_installer')->install(['claro']);
    $this->drupalPlaceBlock('system_powered_by_block', [
      'region' => 'content',
      'theme' => 'claro',
      'id' => 'claro_powered_by',
      'label' => 'Powered by Claro',
    ]);

    $this->drupalGet('admin/appearance/uninstall', ['query' => ['theme' => 'claro']]);
    $this->assertSession()->pageTextContains('Configuration deletions');
    $this->assertSession()->pageTextContains('Powered by Claro');
    $this->submitForm([], 'Uninstall');

    $this->assertSession()->pageTextContains('The Claro theme has been uninstalled');

    $block = \Drupal::entityTypeManager()->getStorage('block')->load('claro_powered_by');
    $this->assertNull($block, 'Block config was deleted');
  }

  /**
   * Tests handling of invalid theme name.
   */
  public function testInvalidThemeName(): void {
    $this->drupalGet('admin/appearance/uninstall', ['query' => ['theme' => 'nonexistent']]);
    $this->assertSession()->addressEquals('admin/appearance');
    $this->assertSession()->pageTextContains('The nonexistent theme was not found');
  }

  /**
   * Tests handling of missing theme parameter.
   */
  public function testMissingThemeParameter(): void {
    $this->drupalGet('admin/appearance/uninstall');
    $this->assertSession()->statusCodeEquals(403);
  }

}
