<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Theme;

use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests theme engine BC layer.
 */
#[Group('Theme')]
#[RunTestsInSeparateProcesses]
#[IgnoreDeprecations]
class BcEngineTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'test_theme_engine_theme';

  /**
   * Tests that .engine theme engines still work.
   */
  public function testPage(): void {
    $this->expectDeprecation('Drupal\Core\Theme\ActiveTheme::getOwner() is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. Theme engines are now tagged services instead of extensions. See https://www.drupal.org/node/3547356');
    $this->expectDeprecation('Using .engine files for theme engines is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. Convert test_theme_engine.engine to a service. See https://www.drupal.org/node/3547356');
    $this->drupalGet('');
    $this->assertSession()->statusCodeEquals(200);
  }

}
