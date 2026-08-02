<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Kernel\Theme;

use Drupal\Core\Theme\ThemeInitializationInterface;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests theme file extension deprecations.
 */
#[Group('Theme')]
#[RunTestsInSeparateProcesses]
class ThemeDeprecationTest extends KernelTestBase {

  /**
   * Tests that .theme deprecations are emitted.
   */
  #[IgnoreDeprecations]
  public function testThemeExtensionDeprecation(): void {
    \Drupal::service('theme_installer')->install(['test_theme_discovery_deprecation']);
    \Drupal::theme()->setActiveTheme(\Drupal::service(ThemeInitializationInterface::class)->initTheme('test_theme_discovery_deprecation'));
    $this->expectUserDeprecationMessage('Using test_theme_discovery_deprecation.theme is deprecated in drupal:12.0.0 and is removed from drupal:13.0.0. Use classes instead. See https://www.drupal.org/node/3581222');
  }

}
