<?php

namespace Drupal\Tests\content_moderation\Functional;

use Drupal\simpletest\ContentTypeCreationTrait;
use Drupal\simpletest\NodeCreationTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\workflows\Entity\Workflow;

/**
 * Test the content moderation local task.
 *
 * @group content_moderation
 */
class LocalTaskTest extends BrowserTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'content_moderation_test_local_task',
    'content_moderation',
    'block',
  ];

  /**
   * A test node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $testNode;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalPlaceBlock('local_tasks_block', ['id' => 'tabs_block']);
    $this->drupalLogin($this->createUser(['bypass node access']));

    $node_type = $this->createContentType();

    // Now enable moderation for subsequent nodes.
    $workflow = Workflow::load('editorial');
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', $node_type->id());
    $workflow->save();

    $this->testNode = $this->createNode([
      'type' => $node_type->id(),
      'moderation_state' => 'draft',
    ]);
  }

  /**
   * Tests local tasks behave with content_moderation enabled.
   */
  public function testLocalTasks() {
    $this->drupalGet(sprintf('node/%s', $this->testNode->id()));
    $this->assertTasks(TRUE);

    $this->clickLink('Task Without Upcast Node');
    $this->assertTasks(FALSE);
  }

  /**
   * Assert the correct tasks appear.
   */
  protected function assertTasks($with_upcast_node) {
    $this->assertSession()->linkExists('View');
    $this->assertSession()->linkExists('Task Without Upcast Node');
    $this->assertSession()->linkExists($with_upcast_node ? 'Edit draft' : 'Edit');
    $this->assertSession()->linkExists('Delete');
  }

}
