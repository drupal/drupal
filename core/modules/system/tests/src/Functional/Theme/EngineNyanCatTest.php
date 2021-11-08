<?php

namespace Drupal\Tests\system\Functional\Theme;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the multi theme engine support.
 *
 * @group Theme
 */
class EngineNyanCatTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['theme_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected function setUp(): void {
    parent::setUp();
    \Drupal::service('theme_installer')->install(['test_theme_nyan_cat_engine']);
  }

  /**
   * Ensures a theme's template is overridable based on the 'template' filename.
   *
   * @group legacy
   *
   * @todo https://www.drupal.org/project/drupal/issues/3246981 Remove
   *   nyan_cat_init() and the legacy group and expected deprecation from this
   *   test.
   */
  public function testTemplateOverride() {
    $this->expectDeprecation('THEME_ENGINE_init() is deprecated in drupal:9.3.0 and removed in drupal:10.0.0. There is no replacement. See https://www.drupal.org/node/3246978');
    $this->config('system.theme')
      ->set('default', 'test_theme_nyan_cat_engine')
      ->save();
    $this->drupalGet('theme-test/template-test');
    $this->assertSession()->pageTextContains('Success: Template overridden with Nyan Cat theme. All of them');
  }

}
