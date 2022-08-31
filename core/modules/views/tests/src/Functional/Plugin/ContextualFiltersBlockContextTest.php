<?php

namespace Drupal\Tests\views\Functional\Plugin;

use Drupal\Core\Plugin\Context\ContextDefinitionInterface;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;
use Drupal\Tests\views\Functional\ViewTestBase;
use Drupal\views\Tests\ViewTestData;

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
  protected static $modules = [
    'block',
    'block_test_views',
    'views_ui',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'starterkit_theme';

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
  protected function setUp($import_test_views = TRUE, $modules = []): void {
    parent::setUp($import_test_views, $modules);

    ViewTestData::createTestViews(static::class, ['block_test_views']);
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
    $this->drupalLogin($this->drupalCreateUser([
      'administer views',
      'administer blocks',
    ]));

    // Check if context was correctly propagated to the block.
    $definition = $this->container->get('plugin.manager.block')
      ->getDefinition('views_block:test_view_block_with_context-block_1');
    $this->assertInstanceOf(ContextDefinitionInterface::class, $definition['context_definitions']['nid']);
    /** @var \Drupal\Core\Plugin\Context\ContextDefinitionInterface $context */
    $context = $definition['context_definitions']['nid'];
    $this->assertEquals('entity:node', $context->getDataType(), 'Context definition data type is correct.');
    $this->assertEquals('Content: ID', $context->getLabel(), 'Context definition label is correct.');
    $this->assertFalse($context->isRequired(), 'Context is not required.');

    // Place test block via block UI to check if contexts are correctly exposed.
    $this->drupalGet(
      'admin/structure/block/add/views_block:test_view_block_with_context-block_1/starterkit_theme',
      ['query' => ['region' => 'content']]
    );
    $edit = [
      'settings[context_mapping][nid]' => '@node.node_route_context:node',
    ];
    $this->submitForm($edit, 'Save block');

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
      'context_mapping' => ['nid' => '@node.node_route_context:node'],
    ];
    $this->assertEquals($expected_settings, $block->getPlugin()->getConfiguration(), 'Block settings are correct.');

    // Make sure view behaves as expected.
    $this->drupalGet('<front>');
    $this->assertSession()->pageTextContains('Test view: No results found.');

    $this->drupalGet($this->nodes[0]->toUrl());
    $this->assertSession()->pageTextContains('Test view row: First test node');

    $this->drupalGet($this->nodes[1]->toUrl());
    $this->assertSession()->pageTextContains('Test view row: Second test node');

    // Check the second block which should expose two integer contexts, one
    // based on the numeric plugin and the other based on numeric validation.
    $definition = $this->container->get('plugin.manager.block')
      ->getDefinition('views_block:test_view_block_with_context-block_2');
    $this->assertInstanceOf(ContextDefinitionInterface::class, $definition['context_definitions']['created']);
    /** @var \Drupal\Core\Plugin\Context\ContextDefinitionInterface $context */
    $context = $definition['context_definitions']['created'];
    $this->assertEquals('integer', $context->getDataType(), 'Context definition data type is correct.');
    $this->assertEquals('Content: Authored on', $context->getLabel(), 'Context definition label is correct.');
    $this->assertFalse($context->isRequired(), 'Context is not required.');

    $this->assertInstanceOf(ContextDefinitionInterface::class, $definition['context_definitions']['vid']);
    /** @var \Drupal\Core\Plugin\Context\ContextDefinitionInterface $context */
    $context = $definition['context_definitions']['vid'];
    $this->assertEquals('integer', $context->getDataType(), 'Context definition data type is correct.');
    $this->assertEquals('Content: Revision ID', $context->getLabel(), 'Context definition label is correct.');
    $this->assertFalse($context->isRequired(), 'Context is not required.');

    $this->assertInstanceOf(ContextDefinitionInterface::class, $definition['context_definitions']['title']);
    /** @var \Drupal\Core\Plugin\Context\ContextDefinitionInterface $context */
    $context = $definition['context_definitions']['title'];
    $this->assertEquals('string', $context->getDataType(), 'Context definition data type is correct.');
    $this->assertEquals('Content: Title', $context->getLabel(), 'Context definition label is correct.');
    $this->assertFalse($context->isRequired(), 'Context is not required.');
  }

}
