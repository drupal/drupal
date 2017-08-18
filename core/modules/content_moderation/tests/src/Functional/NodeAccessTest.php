<?php

namespace Drupal\Tests\content_moderation\Functional;

/**
 * Tests permission access control around nodes.
 *
 * @group content_moderation
 */
class NodeAccessTest extends ModerationStateTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'content_moderation',
    'block',
    'block_content',
    'node',
    'node_access_test_empty',
  ];

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissions = [
    'administer workflows',
    'access administration pages',
    'administer content types',
    'administer nodes',
    'view latest version',
    'view any unpublished content',
    'access content overview',
    'use editorial transition create_new_draft',
    'use editorial transition publish',
    'bypass node access',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalLogin($this->adminUser);
    $this->createContentTypeFromUi('Moderated content', 'moderated_content', FALSE);
    $this->grantUserPermissionToCreateContentOfType($this->adminUser, 'moderated_content');

    // Rebuild permissions because hook_node_grants() is implemented by the
    // node_access_test_empty module.
    node_access_rebuild();
  }

  /**
   * Verifies that a non-admin user can still access the appropriate pages.
   */
  public function testPageAccess() {
    $this->drupalLogin($this->adminUser);

    // Access the node form before moderation is enabled, the publication state
    // should now be visible.
    $this->drupalGet('node/add/moderated_content');
    $this->assertSession()->fieldExists('Published');

    // Now enable the workflow.
    $this->enableModerationThroughUi('moderated_content', 'editorial');

    // Access that the status field is no longer visible.
    $this->drupalGet('node/add/moderated_content');
    $this->assertSession()->fieldNotExists('Published');

    // Create a node to test with.
    $this->drupalPostForm(NULL, [
      'title[0][value]' => 'moderated content',
      'moderation_state[0][state]' => 'draft',
    ], t('Save'));
    $node = $this->getNodeByTitle('moderated content');
    if (!$node) {
      $this->fail('Test node was not saved correctly.');
    }

    $view_path = 'node/' . $node->id();
    $edit_path = 'node/' . $node->id() . '/edit';
    $latest_path = 'node/' . $node->id() . '/latest';

    // Now make a new user and verify that the new user's access is correct.
    $user = $this->createUser([
      'use editorial transition create_new_draft',
      'view latest version',
      'view any unpublished content',
    ]);
    $this->drupalLogin($user);

    $this->drupalGet($edit_path);
    $this->assertResponse(403);

    $this->drupalGet($latest_path);
    $this->assertResponse(403);
    $this->drupalGet($view_path);
    $this->assertResponse(200);

    // Publish the node.
    $this->drupalLogin($this->adminUser);
    $this->drupalPostForm($edit_path, [
      'moderation_state[0][state]' => 'published',
    ], t('Save'));

    // Ensure access works correctly for anonymous users.
    $this->drupalLogout();

    $this->drupalGet($edit_path);
    $this->assertResponse(403);

    $this->drupalGet($latest_path);
    $this->assertResponse(403);
    $this->drupalGet($view_path);
    $this->assertResponse(200);

    // Create a pending revision for the 'Latest revision' tab.
    $this->drupalLogin($this->adminUser);
    $this->drupalPostForm($edit_path, [
      'title[0][value]' => 'moderated content revised',
      'moderation_state[0][state]' => 'draft',
    ], t('Save'));

    $this->drupalLogin($user);

    $this->drupalGet($edit_path);
    $this->assertResponse(403);

    $this->drupalGet($latest_path);
    $this->assertResponse(200);
    $this->drupalGet($view_path);
    $this->assertResponse(200);

    // Now make another user, who should not be able to see pending revisions.
    $user = $this->createUser([
      'use editorial transition create_new_draft',
    ]);
    $this->drupalLogin($user);

    $this->drupalGet($edit_path);
    $this->assertResponse(403);

    $this->drupalGet($latest_path);
    $this->assertResponse(403);
    $this->drupalGet($view_path);
    $this->assertResponse(200);
  }

}
