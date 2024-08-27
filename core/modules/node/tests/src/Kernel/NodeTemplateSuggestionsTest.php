<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests node template suggestions.
 *
 * @group node
 */
class NodeTemplateSuggestionsTest extends KernelTestBase {

  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'user',
    'system',
  ];

  /**
   * Tests if template_preprocess_node() generates the correct suggestions.
   */
  public function testNodeThemeHookSuggestions(): void {
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');

    $this->installConfig(['system']);

    NodeType::create([
      'type' => 'page',
      'name' => 'Page',
    ])->save();

    // Create node to be rendered.
    $node = $this->createNode();
    $view_mode = 'full';

    // Simulate theming of the node.
    $build = \Drupal::entityTypeManager()->getViewBuilder('node')->view($node, $view_mode);

    $variables['elements'] = $build;
    $suggestions = \Drupal::moduleHandler()->invokeAll('theme_suggestions_node', [$variables]);

    $this->assertEquals(['node__full', 'node__page', 'node__page__full', 'node__' . $node->id(), 'node__' . $node->id() . '__full'], $suggestions, 'Found expected node suggestions.');

    // Change the view mode.
    $view_mode = 'node.my_custom_view_mode';
    $build = \Drupal::entityTypeManager()->getViewBuilder('node')->view($node, $view_mode);

    $variables['elements'] = $build;
    $suggestions = \Drupal::moduleHandler()->invokeAll('theme_suggestions_node', [$variables]);

    $this->assertEquals(['node__node_my_custom_view_mode', 'node__page', 'node__page__node_my_custom_view_mode', 'node__' . $node->id(), 'node__' . $node->id() . '__node_my_custom_view_mode'], $suggestions, 'Found expected node suggestions.');
  }

}
