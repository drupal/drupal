<?php

namespace Drupal\Tests\views\Functional;

use Drupal\node\Entity\Node;

/**
 * Tests render caching of blocks provided by views.
 *
 * @group views
 */
class RenderCacheWebTest extends ViewTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'block', 'views_test_render_cache'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['node_id_argument'];

  /**
   * The created nodes.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected $nodes;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = ['views_test_config']): void {
    parent::setUp($import_test_views, $modules);

    $node_type = $this->drupalCreateContentType(['type' => 'test_type']);
    $node = Node::create([
      'title' => 'test title 1',
      'type' => $node_type->id(),
    ]);
    $node->save();
    $this->nodes[] = $node;

    $node = Node::create([
      'title' => 'test title 2',
      'type' => $node_type->id(),
    ]);
    $node->save();
    $this->nodes[] = $node;
  }

  /**
   * Tests rendering caching of a views block with arguments.
   */
  public function testEmptyView() {
    $this->placeBlock('views_block:node_id_argument-block_1', ['region' => 'header']);
    $this->drupalGet('<front>');
    $this->assertEquals([], $this->cssSelect('div.region-header div.views-field-title'));

    $this->drupalGet($this->nodes[0]->toUrl());
    $result = $this->cssSelect('div.region-header div.views-field-title')[0]->getText();
    $this->assertEquals('test title 1', $result);

    $this->drupalGet($this->nodes[1]->toUrl());
    $result = $this->cssSelect('div.region-header div.views-field-title')[0]->getText();
    $this->assertEquals('test title 2', $result);

    $this->drupalGet($this->nodes[0]->toUrl());
    $result = $this->cssSelect('div.region-header div.views-field-title')[0]->getText();
    $this->assertEquals('test title 1', $result);
  }

  /**
   * Tests render caching for display rendered with different args on same page.
   */
  public function testRepeatedDisplay() {
    $this->drupalGet("views_test_render_cache/node_id_argument/block_1/{$this->nodes[0]->id()}/{$this->nodes[1]->id()}");
    // Confirm there are two displays.
    $displays = $this->cssSelect('.views-element-container .view-id-node_id_argument.view-display-id-block_1');
    $this->assertCount(2, $displays, 'There are two displays');
    // First display should only have test title 1.
    $this->assertSame($this->nodes[0]->getTitle(), $displays[0]->getText());
    // Second display should only have test title 2.
    $this->assertSame($this->nodes[1]->getTitle(), $displays[1]->getText());
  }

}
