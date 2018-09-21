<?php

namespace Drupal\Tests\content_moderation\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;

/**
 * @coversDefaultClass \Drupal\content_moderation\Plugin\Field\ModerationStateFieldItemList
 *
 * @group content_moderation
 */
class ModerationStateFieldItemListTest extends KernelTestBase {

  use ContentModerationTestTrait;

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

    NodeType::create([
      'type' => 'unmoderated',
    ])->save();

    $node_type = NodeType::create([
      'type' => 'example',
    ]);
    $node_type->save();
    $workflow = $this->createEditorialWorkflow();
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
    $this->assertFalse($this->testNode->isPublished());
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

  /**
   * @covers ::getValue
   */
  public function testGetValue() {
    $this->assertEquals([['value' => 'draft']], $this->testNode->moderation_state->getValue());
  }

  /**
   * @covers ::get
   */
  public function testGet() {
    $this->assertEquals('draft', $this->testNode->moderation_state->get(0)->value);
    $this->setExpectedException(\InvalidArgumentException::class);
    $this->testNode->moderation_state->get(2);
  }

  /**
   * Tests the computed field when it is unset or set to an empty value.
   */
  public function testSetEmptyState() {
    $this->testNode->moderation_state->value = '';
    $this->assertEquals('draft', $this->testNode->moderation_state->value);

    $this->testNode->moderation_state = '';
    $this->assertEquals('draft', $this->testNode->moderation_state->value);

    unset($this->testNode->moderation_state);
    $this->assertEquals('draft', $this->testNode->moderation_state->value);

    $this->testNode->moderation_state = NULL;
    $this->assertEquals('draft', $this->testNode->moderation_state->value);
  }

  /**
   * Test the list class with a non moderated entity.
   */
  public function testNonModeratedEntity() {
    $unmoderated_node = Node::create([
      'type' => 'unmoderated',
      'title' => 'Test title',
    ]);
    $unmoderated_node->save();
    $this->assertEquals(0, $unmoderated_node->moderation_state->count());

    $unmoderated_node->moderation_state = NULL;
    $this->assertEquals(0, $unmoderated_node->moderation_state->count());
  }

  /**
   * Tests that moderation state changes also change the related entity state.
   *
   * @dataProvider moderationStateChangesTestCases
   */
  public function testModerationStateChanges($initial_state, $final_state, $first_published, $first_is_default, $second_published, $second_is_default) {
    $this->testNode->moderation_state->value = $initial_state;
    $this->assertEquals($first_published, $this->testNode->isPublished());
    $this->assertEquals($first_is_default, $this->testNode->isDefaultRevision());
    $this->testNode->save();

    $this->testNode->moderation_state->value = $final_state;
    $this->assertEquals($second_published, $this->testNode->isPublished());
    $this->assertEquals($second_is_default, $this->testNode->isDefaultRevision());
  }

  /**
   * Data provider for ::testModerationStateChanges
   */
  public function moderationStateChangesTestCases() {
    return [
      'Draft to draft' => [
        'draft',
        'draft',
        FALSE,
        TRUE,
        FALSE,
        TRUE,
      ],
      'Draft to published' => [
        'draft',
        'published',
        FALSE,
        TRUE,
        TRUE,
        TRUE,
      ],
      'Published to published' => [
        'published',
        'published',
        TRUE,
        TRUE,
        TRUE,
        TRUE,
      ],
      'Published to draft' => [
        'published',
        'draft',
        TRUE,
        TRUE,
        FALSE,
        FALSE,
      ],
    ];
  }

  /**
   * Test updating the state for an entity without a workflow.
   */
  public function testEntityWithNoWorkflow() {
    $node_type = NodeType::create([
      'type' => 'example_no_workflow',
    ]);
    $node_type->save();
    $test_node = Node::create([
      'type' => 'example_no_workflow',
      'title' => 'Test node with no workflow',
    ]);
    $test_node->save();

    /** @var \Drupal\content_moderation\ModerationInformationInterface $content_moderation_info */
    $content_moderation_info = \Drupal::service('content_moderation.moderation_information');
    $workflow = $content_moderation_info->getWorkflowForEntity($test_node);
    $this->assertNull($workflow);

    $this->assertTrue($test_node->isPublished());
    $test_node->moderation_state->setValue('draft');
    // The entity is still published because there is not a workflow.
    $this->assertTrue($test_node->isPublished());
  }

  /**
   * Test the moderation_state field after an entity has been serialized.
   *
   * @dataProvider entityUnserializeTestCases
   */
  public function testEntityUnserialize($state, $default, $published) {
    $this->testNode->moderation_state->value = $state;

    $this->assertEquals($state, $this->testNode->moderation_state->value);
    $this->assertEquals($default, $this->testNode->isDefaultRevision());
    $this->assertEquals($published, $this->testNode->isPublished());

    $unserialized = unserialize(serialize($this->testNode));

    $this->assertEquals($state, $unserialized->moderation_state->value);
    $this->assertEquals($default, $unserialized->isDefaultRevision());
    $this->assertEquals($published, $unserialized->isPublished());
  }

  /**
   * Test cases for ::testEntityUnserialize.
   */
  public function entityUnserializeTestCases() {
    return [
      'Default draft state' => [
        'draft',
        TRUE,
        FALSE,
      ],
      'Non-default published state' => [
        'published',
        TRUE,
        TRUE,
      ],
    ];
  }

  /**
   * Test saving a moderated node with an existing ID.
   *
   * @dataProvider moderatedEntityWithExistingIdTestCases
   */
  public function testModeratedEntityWithExistingId($state) {
    $node = Node::create([
      'title' => 'Test title',
      'type' => 'example',
      'nid' => 999,
      'moderation_state' => $state,
    ]);
    $node->save();
    $this->assertEquals($state, $node->moderation_state->value);
  }

  /**
   * Test cases for ::testModeratedEntityWithExistingId.
   */
  public function moderatedEntityWithExistingIdTestCases() {
    return [
      'Draft non-default state' => [
        'draft',
      ],
      'Published default state' => [
        'published',
      ],
    ];
  }

}
