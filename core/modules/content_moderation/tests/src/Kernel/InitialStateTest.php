<?php

namespace Drupal\Tests\content_moderation\Kernel;

use Drupal\entity_test\Entity\EntityTestRev;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;

/**
 * Tests the correct initial states are set on install.
 *
 * @group content_moderation
 */
class InitialStateTest extends KernelTestBase {

  use ContentModerationTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'node',
    'user',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('node', 'node_access');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test_rev');
  }

  /**
   * Tests the correct initial state.
   */
  public function testInitialState() {
    $node_type = NodeType::create([
      'type' => 'example',
      'name' => 'Example',
    ]);
    $node_type->save();

    // Test with an entity type that implements EntityPublishedInterface.
    $unpublished_node = Node::create([
      'type' => 'example',
      'title' => 'Unpublished node',
      'status' => 0,
    ]);
    $unpublished_node->save();

    $published_node = Node::create([
      'type' => 'example',
      'title' => 'Published node',
      'status' => 1,
    ]);
    $published_node->save();

    // Test with an entity type that doesn't implement EntityPublishedInterface.
    $entity_test = EntityTestRev::create();
    $entity_test->save();

    \Drupal::service('module_installer')->install(['content_moderation'], TRUE);
    $workflow = $this->createEditorialWorkflow();
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'example');
    $workflow->getTypePlugin()->addEntityTypeAndBundle('entity_test_rev', 'entity_test_rev');
    $workflow->save();

    $loaded_unpublished_node = Node::load($unpublished_node->id());
    $loaded_published_node = Node::load($published_node->id());
    $loaded_entity_test = EntityTestRev::load($entity_test->id());
    $this->assertEquals('draft', $loaded_unpublished_node->moderation_state->value);
    $this->assertEquals('published', $loaded_published_node->moderation_state->value);
    $this->assertEquals('draft', $loaded_entity_test->moderation_state->value);

    $presave_node = Node::create([
      'type' => 'example',
      'title' => 'Presave node',
    ]);
    $this->assertEquals('draft', $presave_node->moderation_state->value);
  }

}
