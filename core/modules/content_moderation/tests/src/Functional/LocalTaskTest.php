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

    $node_type = $this->createContentType([
      'type' => 'test_content_type',
    ]);

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
    // The default state is a draft.
    $this->drupalGet(sprintf('node/%s', $this->testNode->id()));
    $this->assertTasks('Edit draft');

    // When published as the live revision, the label changes.
    $this->testNode->moderation_state = 'published';
    $this->testNode->save();
    $this->drupalGet(sprintf('node/%s', $this->testNode->id()));
    $this->assertTasks('New draft');

    $tags = $this->drupalGetHeader('X-Drupal-Cache-Tags');
    $this->assertContains('node:1', $tags);
    $this->assertContains('node_type:test_content_type', $tags);

    // Without an upcast node, the state cannot be determined.
    $this->clickLink('Task Without Upcast Node');
    $this->assertTasks('Edit');
  }

  /**
   * Assert the correct tasks appear.
   *
   * @param string $edit_tab_label
   *   The edit tab label to assert.
   */
  protected function assertTasks($edit_tab_label) {
    $this->assertSession()->linkExists('View');
    $this->assertSession()->linkExists('Task Without Upcast Node');
    $this->assertSession()->linkExists($edit_tab_label);
    $this->assertSession()->linkExists('Delete');
  }

}
