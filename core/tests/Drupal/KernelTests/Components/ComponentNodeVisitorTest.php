<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Components;

/**
 * Tests the node visitor.
 *
 * @coversDefaultClass \Drupal\Core\Template\ComponentNodeVisitor
 * @group sdc
 */
class ComponentNodeVisitorTest extends ComponentKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'sdc_other_node_visitor'];

  /**
   * {@inheritdoc}
   */
  protected static $themes = ['sdc_theme_test'];

  const DEBUG_COMPONENT_ID_PATTERN = '/<!-- ([\n\s\S]*) Component start: ([\SA-Za-z+-:]+) -->/';
  const DEBUG_VARIANT_ID_PATTERN = '/<!-- [\n\s\S]* with variant: "([\SA-Za-z+-]+)" -->/';

  /**
   * Test that other visitors can modify Twig nodes.
   */
  public function testOtherVisitorsCanModifyTwigNodes(): void {
    $build = [
      '#type' => 'inline_template',
      '#template' => "{% embed('sdc_theme_test_base:my-card-no-schema') %}{% block card_body %}Foo bar{% endblock %}{% endembed %}",
    ];
    $this->renderComponentRenderArray($build);

    // If this is reached, the test passed.
    $this->assertTrue(TRUE);
  }

  /**
   * Test debug output for sdc components with component id and variant.
   */
  public function testDebugRendersComponentStartWithVariant(): void {
    // Enable twig theme debug to ensure that any
    // changes to theme debugging format force checking
    // that the auto paragraph filter continues to be applied
    // correctly.
    $twig = \Drupal::service('twig');
    $twig->enableDebug();

    $build = [
      '#type' => 'component',
      '#component' => 'sdc_theme_test:my-card',
      '#variant' => 'vertical',
      '#props' => [
        'header' => 'My header',
      ],
      '#slots' => [
        'card_body' => 'Foo bar',
      ],
    ];
    $crawler = $this->renderComponentRenderArray($build);
    $content = $crawler->html();

    $matches = [];
    \preg_match_all(self::DEBUG_COMPONENT_ID_PATTERN, $content, $matches);
    $this->assertSame($matches[2][0], 'sdc_theme_test:my-card');

    \preg_match_all(self::DEBUG_VARIANT_ID_PATTERN, $content, $matches);
    $this->assertSame($matches[1][0], 'vertical');
  }

}
