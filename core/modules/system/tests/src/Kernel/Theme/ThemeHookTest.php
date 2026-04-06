<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Kernel\Theme;

use Drupal\Core\Theme\ThemeInitializationInterface;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests low-level theme functions.
 */
#[Group('Theme')]
#[RunTestsInSeparateProcesses]
class ThemeHookTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    \Drupal::service('theme_installer')->install(['procedural_hook_theme']);
    \Drupal::theme()->setActiveTheme(\Drupal::service(ThemeInitializationInterface::class)->initTheme('procedural_hook_theme'));
  }

  /**
   * Tests that procedural hooks are collected and executed.
   */
  public function testProceduralHookCollection(): void {
    $args = [];
    \Drupal::theme()->alter('procedural', $args);
    $this->assertEquals(['Procedural theme hook executed.'], $args);
  }

  /**
   * Tests that procedural hooks with #[LegacyHook] are properly ignored.
   */
  public function testLegacyHookInThemes(): void {
    $args = [];
    \Drupal::theme()->alter('procedural_legacy', $args);
    $this->assertEquals(['OOP theme hook executed.'], $args);
  }

}
