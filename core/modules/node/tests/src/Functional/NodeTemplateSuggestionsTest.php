<?php

namespace Drupal\Tests\node\Functional;

/**
 * Tests node template suggestions.
 *
 * @group node
 */
class NodeTemplateSuggestionsTest extends NodeTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests if template_preprocess_node() generates the correct suggestions.
   */
  public function testNodeThemeHookSuggestions() {
    // Create node to be rendered.
    $node = $this->drupalCreateNode();
    $view_mode = 'full';

    // Simulate theming of the node.
    $build = \Drupal::entityTypeManager()->getViewBuilder('node')->view($node, $view_mode);

    $variables['elements'] = $build;
    $suggestions = \Drupal::moduleHandler()->invokeAll('theme_suggestions_node', [$variables]);

    $this->assertEqual($suggestions, ['node__full', 'node__page', 'node__page__full', 'node__' . $node->id(), 'node__' . $node->id() . '__full'], 'Found expected node suggestions.');

    // Change the view mode.
    $view_mode = 'node.my_custom_view_mode';
    $build = \Drupal::entityTypeManager()->getViewBuilder('node')->view($node, $view_mode);

    $variables['elements'] = $build;
    $suggestions = \Drupal::moduleHandler()->invokeAll('theme_suggestions_node', [$variables]);

    $this->assertEqual($suggestions, ['node__node_my_custom_view_mode', 'node__page', 'node__page__node_my_custom_view_mode', 'node__' . $node->id(), 'node__' . $node->id() . '__node_my_custom_view_mode'], 'Found expected node suggestions.');
  }

}
