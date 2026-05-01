<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Kernel\Theme;

use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

// cspell:ignore nyan
/**
 * Tests the multi theme engine support.
 */
#[Group('Theme')]
#[RunTestsInSeparateProcesses]
class EngineNyanCatTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'theme_test', 'nyan_cat'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    \Drupal::service('theme_installer')->install(['test_theme_nyan_cat_engine']);
  }

  /**
   * Ensures a theme's template is overridable based on the 'template' filename.
   */
  public function testTemplateOverride(): void {
    $this->config('system.theme')
      ->set('default', 'test_theme_nyan_cat_engine')
      ->save();
    $this->drupalGet('theme-test/template-test');
    $this->assertSession()->pageTextContains('Success: Template overridden with Nyan Cat theme. All of them');
  }

}
