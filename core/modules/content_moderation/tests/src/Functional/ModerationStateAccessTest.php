<?php

namespace Drupal\Tests\content_moderation\Functional;

use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the view access control handler for moderation state entities.
 *
 * @group content_moderation
 */
class ModerationStateAccessTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'content_moderation_test_views',
    'content_moderation',
  ];

  /**
   * Test the view operation access handler with the view permission.
   */
  public function testViewShowsCorrectStates() {
    $node_type_id = 'test';
    $this->createNodeType('Test', $node_type_id);

    $permissions = [
      'access content',
      'view all revisions',
      'view moderation states',
    ];
    $editor1 = $this->drupalCreateUser($permissions);
    $this->drupalLogin($editor1);

    $node_1 = Node::create([
      'type' => $node_type_id,
      'title' => 'Draft node',
      'uid' => $editor1->id(),
    ]);
    $node_1->moderation_state->target_id = 'draft';
    $node_1->save();

    $node_2 = Node::create([
      'type' => $node_type_id,
      'title' => 'Published node',
      'uid' => $editor1->id(),
    ]);
    $node_2->moderation_state->target_id = 'published';
    $node_2->save();

    // Resave the node with a new state.
    $node_2->setTitle('Archived node');
    $node_2->moderation_state->target_id = 'archived';
    $node_2->save();

    // Now show the View, and confirm that the state labels are showing.
    $this->drupalGet('/latest');
    $page = $this->getSession()->getPage();
    $this->assertTrue($page->hasLink('Draft'));
    $this->assertTrue($page->hasLink('Archived'));
    $this->assertFalse($page->hasLink('Published'));

    // Now log in as an admin and test the same thing.
    $permissions = [
      'access content',
      'view all revisions',
      'administer moderation states',
    ];
    $admin1 = $this->drupalCreateUser($permissions);
    $this->drupalLogin($admin1);

    $this->drupalGet('/latest');
    $page = $this->getSession()->getPage();
    $this->assertEquals(200, $this->getSession()->getStatusCode());
    $this->assertTrue($page->hasLink('Draft'));
    $this->assertTrue($page->hasLink('Archived'));
    $this->assertFalse($page->hasLink('Published'));
  }

  /**
   * Creates a new node type.
   *
   * @param string $label
   *   The human-readable label of the type to create.
   * @param string $machine_name
   *   The machine name of the type to create.
   *
   * @return NodeType
   *   The node type just created.
   */
  protected function createNodeType($label, $machine_name) {
    /** @var NodeType $node_type */
    $node_type = NodeType::create([
      'type' => $machine_name,
      'label' => $label,
    ]);
    $node_type->setThirdPartySetting('content_moderation', 'enabled', TRUE);
    $node_type->save();

    return $node_type;
  }

}
