<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Components;

use Drupal\Core\Theme\ComponentNegotiator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the component negotiator.
 */
#[CoversClass(ComponentNegotiator::class)]
#[Group('sdc')]
#[RunTestsInSeparateProcesses]
class ComponentNegotiatorTest extends ComponentKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'sdc_test',
    'sdc_test_replacements',
  ];

  /**
   * Themes to install.
   *
   * @var string[]
   */
  protected static $themes = [
    'sdc_theme_test_enforce_schema', 'sdc_theme_test',
  ];

  /**
   * Tests negotiate.
   *
   * @legacy-covers ::negotiate
   */
  public function testNegotiate(): void {
    $data = [
      ['sdc_test:my-banner', NULL],
      ['sdc_theme_test:my-card', 'sdc_theme_test_enforce_schema:my-card'],
      [
        'sdc_test:my-button',
        'sdc_test_replacements:my-button',
      ],
        ['invalid:component', NULL],
        ['invalid^component', NULL],
        ['', NULL],
    ];
    array_walk($data, function ($test_input): void {
      [$requested_id, $expected_id] = $test_input;
      $negotiated_id = $this->negotiator->negotiate(
        $requested_id,
        $this->manager->getDefinitions(),
      );
      $this->assertSame($expected_id, $negotiated_id);
    });
  }

  /**
   * Tests rendering components with component replacement.
   */
  public function testRenderWithReplacements(): void {
    $build = [
      '#type' => 'inline_template',
      '#template' => "{{ include('sdc_test:my-button') }}",
      '#context' => ['text' => 'Like!', 'iconType' => 'like'],
    ];
    $crawler = $this->renderComponentRenderArray($build);
    $this->assertNotEmpty($crawler->filter('#sdc-wrapper button[data-component-id="sdc_test_replacements:my-button"]'));
    $this->assertNotEmpty($crawler->filter('#sdc-wrapper button .sdc-id:contains("sdc_test_replacements:my-button")'));

    // Now test component replacement on themes.
    $build = [
      '#type' => 'inline_template',
      '#template' => "{{ include('sdc_theme_test:my-card') }}",
      '#context' => ['header' => 'Foo bar'],
      '#variant' => 'horizontal',
    ];
    $crawler = $this->renderComponentRenderArray($build);
    $this->assertNotEmpty($crawler->filter('#sdc-wrapper .component--my-card--replaced__body'));
  }

}
