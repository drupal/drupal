<?php

namespace Drupal\Tests\content_moderation\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\workflows\Entity\Workflow;

/**
 * @coversDefaultClass \Drupal\content_moderation\Plugin\Field\ModerationStateFieldItemList
 *
 * @group content_moderation
 */
class ModerationStateFieldItemListTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'content_moderation',
    'user',
    'system',
    'language',
    'workflows',
  ];

  /**
   * @var \Drupal\node\NodeInterface
   */
  protected $testNode;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('node', 'node_access');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('content_moderation_state');
    $this->installConfig('content_moderation');

    $node_type = NodeType::create([
      'type' => 'example',
    ]);
    $node_type->save();
    $workflow = Workflow::load('editorial');
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'example');
    $workflow->save();

    $this->testNode = Node::create([
      'type' => 'example',
      'title' => 'Test title',
    ]);
    $this->testNode->save();
    \Drupal::entityTypeManager()->getStorage('node')->resetCache();
    $this->testNode = Node::load($this->testNode->id());
  }

  /**
   * Test the field item list when accessing an index.
   */
  public function testArrayIndex() {
    $this->assertEquals('draft', $this->testNode->moderation_state[0]->value);
  }

  /**
   * Test the field item list when iterating.
   */
  public function testArrayIteration() {
    $states = [];
    foreach ($this->testNode->moderation_state as $item) {
      $states[] = $item->value;
    }
    $this->assertEquals(['draft'], $states);
  }

}
