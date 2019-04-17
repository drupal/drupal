<?php

namespace Drupal\Tests\node\Functional\Views;

use Drupal\node\NodeInterface;

/**
 * Tests the node.status_extra field handler.
 *
 * @group node
 * @see \Drupal\node\Plugin\views\filter\Status
 */
class StatusExtraTest extends NodeTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node_test_views', 'content_moderation'];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_status_extra'];

  /**
   * Tests the status extra filter.
   */
  public function testStatusExtra() {
    $node_author = $this->drupalCreateUser(['view own unpublished content']);
    $node_author_not_unpublished = $this->drupalCreateUser();
    $normal_user = $this->drupalCreateUser();
    $privileged_user = $this->drupalCreateUser(['view any unpublished content']);
    $admin_user = $this->drupalCreateUser(['bypass node access']);

    // Create one published and one unpublished node by the admin.
    $node_published = $this->drupalCreateNode(['uid' => $admin_user->id()]);
    $node_unpublished = $this->drupalCreateNode(['uid' => $admin_user->id(), 'status' => NodeInterface::NOT_PUBLISHED]);

    // Create one unpublished node by a certain author user.
    $node_unpublished2 = $this->drupalCreateNode(['uid' => $node_author->id(), 'status' => NodeInterface::NOT_PUBLISHED]);

    // Create one unpublished node by a user who does not have the `view own
    // unpublished content` permission.
    $node_unpublished3 = $this->drupalCreateNode(['uid' => $node_author_not_unpublished->id(), 'status' => NodeInterface::NOT_PUBLISHED]);

    // The administrator should simply see all nodes.
    $this->drupalLogin($admin_user);
    $this->drupalGet('test_status_extra');
    $this->assertText($node_published->label());
    $this->assertText($node_unpublished->label());
    $this->assertText($node_unpublished2->label());
    $this->assertText($node_unpublished3->label());

    // The privileged user should simply see all nodes.
    $this->drupalLogin($privileged_user);
    $this->drupalGet('test_status_extra');
    $this->assertText($node_published->label());
    $this->assertText($node_unpublished->label());
    $this->assertText($node_unpublished2->label());
    $this->assertText($node_unpublished3->label());

    // The node author should see the published node and their own node.
    $this->drupalLogin($node_author);
    $this->drupalGet('test_status_extra');
    $this->assertText($node_published->label());
    $this->assertNoText($node_unpublished->label());
    $this->assertText($node_unpublished2->label());
    $this->assertNoText($node_unpublished3->label());

    // The normal user should just see the published node.
    $this->drupalLogin($normal_user);
    $this->drupalGet('test_status_extra');
    $this->assertText($node_published->label());
    $this->assertNoText($node_unpublished->label());
    $this->assertNoText($node_unpublished2->label());
    $this->assertNoText($node_unpublished3->label());

    // The author without the permission to see their own unpublished node should
    // just see the published node.
    $this->drupalLogin($node_author_not_unpublished);
    $this->drupalGet('test_status_extra');
    $this->assertText($node_published->label());
    $this->assertNoText($node_unpublished->label());
    $this->assertNoText($node_unpublished2->label());
    $this->assertNoText($node_unpublished3->label());
  }

}
