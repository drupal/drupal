<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Kernel\Theme;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests Twig 'raw' filter.
 *
 * @group Theme
 */
class TwigRawTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['twig_theme_test'];

  /**
   * Tests the raw filter inside an autoescape tag.
   */
  public function testAutoescapeRaw(): void {
    $test = [
      '#theme' => 'twig_raw_test',
      '#script' => '<script>alert("This alert is real because I will put it through the raw filter!");</script>',
    ];
    $rendered = \Drupal::service('renderer')->renderRoot($test);
    $this->setRawContent($rendered);
    $this->assertRaw('<script>alert("This alert is real because I will put it through the raw filter!");</script>');
  }

  /**
   * Tests autoescaping of unsafe content.
   *
   * This is one of the most important tests in Drupal itself in terms of
   * security.
   */
  public function testAutoescape(): void {
    $script = '<script>alert("This alert is unreal!");</script>';
    $build = [
      '#theme' => 'twig_autoescape_test',
      '#script' => $script,
    ];
    $rendered = \Drupal::service('renderer')->renderRoot($build);
    $this->setRawContent($rendered);
    $this->assertEscaped($script);
  }

}
