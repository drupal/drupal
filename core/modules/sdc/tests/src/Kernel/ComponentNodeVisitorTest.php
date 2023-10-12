<?php

namespace Drupal\Tests\sdc\Kernel;

/**
 * Tests the node visitor.
 *
 * @coversDefaultClass \Drupal\sdc\Twig\ComponentNodeVisitor
 * @group sdc
 *
 * @internal
 */
final class ComponentNodeVisitorTest extends ComponentKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['sdc', 'sdc_other_node_visitor'];

  /**
   * {@inheritdoc}
   */
  protected static $themes = ['sdc_theme_test'];

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

}
