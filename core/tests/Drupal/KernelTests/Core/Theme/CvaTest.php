<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Theme;

use Drupal\Core\Extension\ThemeInstallerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * Tests using CVA in Twig templates.
 */
#[Group('Theme')]
#[RunTestsInSeparateProcesses]
final class CvaTest extends KernelTestBase {

  /**
   * Tests rendering a component that uses CVA.
   *
   * @param string $prop_value
   *   The value of the prop to pass to the component.
   * @param string $expected_class
   *   The expected CSS class that should appear based on the prop value.
   */
  #[TestWith(['nice', 'friendly'])]
  #[TestWith(['mean', 'unfriendly'])]
  public function testCva(string $prop_value, string $expected_class): void {
    \Drupal::service(ThemeInstallerInterface::class)->install([
      'sdc_theme_test',
    ]);

    $build = [
      '#type' => 'component',
      '#component' => 'sdc_theme_test:cva',
      '#props' => ['friendliness' => $prop_value],
    ];
    $rendered = (string) \Drupal::service(RendererInterface::class)
      ->renderRoot($build);
    $expected_html = sprintf(' class="cva-friendliness %s"', $expected_class);
    $this->assertStringContainsString($expected_html, $rendered);
  }

}
