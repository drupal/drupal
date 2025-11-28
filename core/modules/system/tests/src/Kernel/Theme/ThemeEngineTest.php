<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Kernel\Theme;

use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

// cspell:ignore nyan

/**
 * Tests theme engine functionality.
 */
#[IgnoreDeprecations]
#[Group('Theme')]
#[RunTestsInSeparateProcesses]
class ThemeEngineTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    \Drupal::service('theme_installer')->install(['test_theme_engine_theme']);
  }

  /**
   * Tests deprecated theme engine .engine files.
   */
  public function testThemeEngineDeprecation(): void {
    $this->expectDeprecation('Using .engine files for theme engines is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. Convert test_theme_engine.engine to a service. See https://www.drupal.org/node/3547356');
    \Drupal::service('theme.initialization')->initTheme('test_theme_engine_theme');
    // Ensure that \Drupal\Core\Theme\ThemeManager::getThemeEngine() does not
    // error when the theme engine service is not found.
    $this->assertNull(\Drupal::service('theme.manager')->getThemeEngine('test_theme_engine_theme'));
  }

}
