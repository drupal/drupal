<?php

namespace Drupal\views\Tests\Plugin;

use Drupal\Core\Plugin\Context\ContextDefinitionInterface;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Tests\ViewTestBase;
use Drupal\system\Tests\Cache\AssertPageCacheContextsAndTagsTrait;

/**
 * A test for contextual filters exposed as block context.
 *
 * @group views
 */
class ContextualFiltersBlockContextTest extends ViewTestBase {

  use AssertPageCacheContextsAndTagsTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['block', 'block_test_views', 'views_ui', 'node'];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_view_block_with_context'];

  /**
   * Test node type.
   *
   * @var \Drupal\node\NodeTypeInterface
   */
  protected $nodeType;

  /**
   * Test nodes.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected $nodes;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    ViewTestData::createTestViews(get_class($this), ['block_test_views']);
    $this->enableViewsTestModule();

    $this->nodeType = $this->container->get('entity_type.manager')
      ->getStorage('node_type')
      ->create([
        'name' => 'Test node type',
        'type' => 'test',
      ]);
    $this->nodeType->save();

    $this->nodes[0] = $this->container->get('entity_type.manager')
      ->getStorage('node')
      ->create(['type' => $this->nodeType->id(), 'title' => 'First test node']);
    $this->nodes[0]->save();

    $this->nodes[1] = $this->container->get('entity_type.manager')
      ->getStorage('node')
      ->create(['type' => $this->nodeType->id(), 'title' => 'Second test node']);
    $this->nodes[1]->save();
  }

  /**
   * Tests exposed context.
   */
  public function testBlockContext() {
    $this->drupalLogin($this->drupalCreateUser(['administer views', 'administer blocks']));

    // Check if context was correctly propagated to the block.
    $definition = $this->container->get('plugin.manager.block')
      ->getDefinition('views_block:test_view_block_with_context-block_1');
    $this->assertTrue($definition['context']['nid'] instanceof ContextDefinitionInterface);
    /** @var \Drupal\Core\Plugin\Context\ContextDefinitionInterface $context */
    $context = $definition['context']['nid'];
    $this->assertEqual($context->getDataType(), 'entity:node', 'Context definition data type is correct.');
    $this->assertEqual($context->getLabel(), 'Content: ID', 'Context definition label is correct.');
    $this->assertFalse($context->isRequired(), 'Context is not required.');

    // Place test block via block UI to check if contexts are correctly exposed.
    $this->drupalGet(
      'admin/structure/block/add/views_block:test_view_block_with_context-block_1/classy',
      ['query' => ['region' => 'content']]
    );
    $edit = [
      'settings[context_mapping][nid]' => '@node.node_route_context:node',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save block');

    // Check if mapping saved correctly.
    /** @var \Drupal\block\BlockInterface $block */
    $block = $this->container->get('entity_type.manager')
      ->getStorage('block')
      ->load('views_block__test_view_block_with_context_block_1');
    $expected_settings = [
      'id' => 'views_block:test_view_block_with_context-block_1',
      'label' => '',
      'provider' => 'views',
      'label_display' => 'visible',
      'views_label' => '',
      'items_per_page' => 'none',
      'context_mapping' => ['nid' => '@node.node_route_context:node']
    ];
    $this->assertEqual($block->getPlugin()->getConfiguration(), $expected_settings, 'Block settings are correct.');

    // Make sure view behaves as expected.
    $this->drupalGet('<front>');
    $this->assertText('Test view: No results found.');

    $this->drupalGet($this->nodes[0]->toUrl());
    $this->assertText('Test view row: First test node');

    $this->drupalGet($this->nodes[1]->toUrl());
    $this->assertText('Test view row: Second test node');

    // Check the second block which should expose two integer contexts, one
    // based on the numeric plugin and the other based on numeric validation.
    $definition = $this->container->get('plugin.manager.block')
      ->getDefinition('views_block:test_view_block_with_context-block_2');
    $this->assertTrue($definition['context']['created'] instanceof ContextDefinitionInterface);
    /** @var \Drupal\Core\Plugin\Context\ContextDefinitionInterface $context */
    $context = $definition['context']['created'];
    $this->assertEqual($context->getDataType(), 'integer', 'Context definition data type is correct.');
    $this->assertEqual($context->getLabel(), 'Content: Authored on', 'Context definition label is correct.');
    $this->assertFalse($context->isRequired(), 'Context is not required.');

    $this->assertTrue($definition['context']['vid'] instanceof ContextDefinitionInterface);
    /** @var \Drupal\Core\Plugin\Context\ContextDefinitionInterface $context */
    $context = $definition['context']['vid'];
    $this->assertEqual($context->getDataType(), 'integer', 'Context definition data type is correct.');
    $this->assertEqual($context->getLabel(), 'Content: Revision ID', 'Context definition label is correct.');
    $this->assertFalse($context->isRequired(), 'Context is not required.');

    $this->assertTrue($definition['context']['title'] instanceof ContextDefinitionInterface);
    /** @var \Drupal\Core\Plugin\Context\ContextDefinitionInterface $context */
    $context = $definition['context']['title'];
    $this->assertEqual($context->getDataType(), 'string', 'Context definition data type is correct.');
    $this->assertEqual($context->getLabel(), 'Content: Title', 'Context definition label is correct.');
    $this->assertFalse($context->isRequired(), 'Context is not required.');
  }

}
