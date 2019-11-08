<?php

namespace Drupal\Tests\content_moderation\Functional;

use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;

/**
 * Tests the view access control handler for moderation state entities.
 *
 * @group content_moderation
 */
class ModerationStateAccessTest extends BrowserTestBase {

  use ContentModerationTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'content_moderation',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $node_type = NodeType::create([
      'type' => 'test',
      'label' => 'Test',
    ]);
    $node_type->save();

    $workflow = $this->createEditorialWorkflow();
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'test');
    $workflow->save();

    $this->container->get('module_installer')->install(['content_moderation_test_views']);
  }

  /**
   * Test the view operation access handler with the view permission.
   */
  public function testViewShowsCorrectStates() {
    $permissions = [
      'access content',
      'view all revisions',
    ];
    $editor1 = $this->drupalCreateUser($permissions);
    $this->drupalLogin($editor1);

    $node_1 = Node::create([
      'type' => 'test',
      'title' => 'Draft node',
      'uid' => $editor1->id(),
    ]);
    $node_1->moderation_state->value = 'draft';
    $node_1->save();

    $node_2 = Node::create([
      'type' => 'test',
      'title' => 'Published node',
      'uid' => $editor1->id(),
    ]);
    $node_2->moderation_state->value = 'published';
    $node_2->save();

    // Resave the node with a new state.
    $node_2->setTitle('Archived node');
    $node_2->moderation_state->value = 'archived';
    $node_2->save();

    // Now show the View, and confirm that the state labels are showing.
    $this->drupalGet('/latest');
    $page = $this->getSession()->getPage();
    $this->assertTrue($page->hasContent('Draft'));
    $this->assertTrue($page->hasContent('Archived'));
    $this->assertFalse($page->hasContent('Published'));

    // Now log in as an admin and test the same thing.
    $permissions = [
      'access content',
      'view all revisions',
    ];
    $admin1 = $this->drupalCreateUser($permissions);
    $this->drupalLogin($admin1);

    $this->drupalGet('/latest');
    $page = $this->getSession()->getPage();
    $this->assertEquals(200, $this->getSession()->getStatusCode());
    $this->assertTrue($page->hasContent('Draft'));
    $this->assertTrue($page->hasContent('Archived'));
    $this->assertFalse($page->hasContent('Published'));
  }

}
