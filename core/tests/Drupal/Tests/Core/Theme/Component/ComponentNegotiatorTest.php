<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Theme\Component;

/**
 * Tests the component negotiator.
 *
 * @coversDefaultClass \Drupal\Core\Theme\ComponentNegotiator
 * @group sdc
 */
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
   * @covers ::negotiate
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
    array_walk($data, function ($test_input) {
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
    ];
    $crawler = $this->renderComponentRenderArray($build);
    $this->assertNotEmpty($crawler->filter('#sdc-wrapper .component--my-card--replaced__body'));
  }

}
