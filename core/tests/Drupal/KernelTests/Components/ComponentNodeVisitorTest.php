<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Components;

use Drupal\Tests\Core\Theme\Component\ComponentKernelTestBase;

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
