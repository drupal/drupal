<?php

namespace Drupal\views\Tests;

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
  public static $modules = ['node', 'block'];

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
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

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

    $this->placeBlock('views_block:node_id_argument-block_1', ['region' => 'header']);
  }

  /**
   * Tests rendering caching of a views block with arguments.
   */
  public function testEmptyView() {
    $this->drupalGet('<front>');
    $this->assertEqual([], $this->cssSelect('div.region-header div.views-field-title'));

    $this->drupalGet($this->nodes[0]->toUrl());
    $result = (string) $this->cssSelect('div.region-header div.views-field-title')[0]->span;
    $this->assertEqual('test title 1', $result);

    $this->drupalGet($this->nodes[1]->toUrl());
    $result = (string) $this->cssSelect('div.region-header div.views-field-title')[0]->span;
    $this->assertEqual('test title 2', $result);

    $this->drupalGet($this->nodes[0]->toUrl());
    $result = (string) $this->cssSelect('div.region-header div.views-field-title')[0]->span;
    $this->assertEqual('test title 1', $result);
  }

}
