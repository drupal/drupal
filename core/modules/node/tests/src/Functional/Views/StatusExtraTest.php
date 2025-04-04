<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Functional\Views;

use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Drupal\Tests\node\Traits\NodeAccessTrait;

/**
 * Tests the node.status_extra field handler.
 *
 * @group node
 * @see \Drupal\node\Plugin\views\filter\Status
 */
class StatusExtraTest extends NodeTestBase {

  use NodeAccessTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node_test_views', 'content_moderation'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_status_extra'];

  /**
   * Tests the status extra filter.
   */
  public function testStatusExtra(): void {
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
    $this->assertSession()->pageTextContains($node_published->label());
    $this->assertSession()->pageTextContains($node_unpublished->label());
    $this->assertSession()->pageTextContains($node_unpublished2->label());
    $this->assertSession()->pageTextContains($node_unpublished3->label());

    // The privileged user should simply see all nodes.
    $this->drupalLogin($privileged_user);
    $this->drupalGet('test_status_extra');
    $this->assertSession()->pageTextContains($node_published->label());
    $this->assertSession()->pageTextContains($node_unpublished->label());
    $this->assertSession()->pageTextContains($node_unpublished2->label());
    $this->assertSession()->pageTextContains($node_unpublished3->label());

    // The node author should see the published node and their own node.
    $this->drupalLogin($node_author);
    $this->drupalGet('test_status_extra');
    $this->assertSession()->pageTextContains($node_published->label());
    $this->assertSession()->pageTextNotContains($node_unpublished->label());
    $this->assertSession()->pageTextContains($node_unpublished2->label());
    $this->assertSession()->pageTextNotContains($node_unpublished3->label());

    // The normal user should just see the published node.
    $this->drupalLogin($normal_user);
    $this->drupalGet('test_status_extra');
    $this->assertSession()->pageTextContains($node_published->label());
    $this->assertSession()->pageTextNotContains($node_unpublished->label());
    $this->assertSession()->pageTextNotContains($node_unpublished2->label());
    $this->assertSession()->pageTextNotContains($node_unpublished3->label());

    // The author without the permission to see their own unpublished node
    // should just see the published node.
    $this->drupalLogin($node_author_not_unpublished);
    $this->drupalGet('test_status_extra');
    $this->assertSession()->pageTextContains($node_published->label());
    $this->assertSession()->pageTextNotContains($node_unpublished->label());
    $this->assertSession()->pageTextNotContains($node_unpublished2->label());
    $this->assertSession()->pageTextNotContains($node_unpublished3->label());

    \Drupal::service('module_installer')->install(['node_access_test']);
    NodeType::create(['type' => 'page', 'name' => 'page'])->save();
    $this->addPrivateField(NodeType::load('page'));
    node_access_rebuild();
    $node_published_private = $this->drupalCreateNode(['uid' => $admin_user->id(), 'private' => ['value' => 1]]);
    $node_unpublished_private = $this->drupalCreateNode(['uid' => $admin_user->id(), 'status' => NodeInterface::NOT_PUBLISHED, 'private' => ['value' => 1]]);

    // An unprivileged user must not see the published and unpublished content
    // when access is granted via hook_node_access_grants().
    $this->drupalLogin($this->drupalCreateUser());
    $this->drupalGet('test_status_extra');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains($node_published_private->label());
    $this->assertSession()->pageTextNotContains($node_unpublished_private->label());

    // A privileged user must see the published and unpublished content
    // when access is granted via hook_node_access_grants().
    $this->drupalLogin($this->drupalCreateUser(values: [
      'roles' => $this->drupalCreateRole([
        'node test view',
      ]),
    ]));
    $this->drupalGet('test_status_extra');
    $this->assertSession()->pageTextContains($node_published_private->label());
    $this->assertSession()->pageTextContains($node_unpublished_private->label());
  }

}
