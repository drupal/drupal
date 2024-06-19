<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Theme;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\UpdatePathTestTrait;

/**
 * Tests low-level theme functions.
 *
 * @group Theme
 */
class ThemeUpdateTest extends BrowserTestBase {
  use UpdatePathTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'starterkit_theme';

  /**
   * Ensures preprocess functions run even for suggestion implementations.
   *
   * The theme hook used by this test has its base preprocess function in a
   * separate file, so this test also ensures that the file is correctly loaded
   * when needed.
   */
  public function testThemeUpdates(): void {
    \Drupal::service('module_installer')->install(['test_module_required_by_theme']);
    $this->rebuildAll();
    \Drupal::state()->set('test_theme_depending_on_modules.system_info_alter', ['dependencies' => ['test_module_required_by_theme', 'stark']]);
    \Drupal::service('theme_installer')->install(['test_theme_depending_on_modules']);
    $this->assertTrue(\Drupal::service('theme_handler')->themeExists('test_theme_depending_on_modules'), 'test_theme_depending_on_modules theme installed');
    \Drupal::state()->set('test_theme_depending_on_modules.system_info_alter', FALSE);
    \Drupal::state()->set('test_theme_depending_on_modules.post_update', TRUE);
    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('test_another_module_required_by_theme'));
    $this->runUpdates();
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('test_another_module_required_by_theme'));

    $this->assertSession()->addressEquals('update.php/results');
    $this->assertSession()->responseContains('test_theme_depending_on_modules theme');
    $this->assertSession()->responseContains('Post update message from theme post update function');
  }

  /**
   * {@inheritdoc}
   */
  protected function doSelectionTest() {
    // Ensure that the theme's post update appears as expected.
    $this->assertSession()->responseContains('test_theme_depending_on_modules theme');
    $this->assertSession()->responseContains('Install a dependent module.');
  }

}
