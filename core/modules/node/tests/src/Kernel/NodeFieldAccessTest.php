<?php

namespace Drupal\Tests\node\Kernel;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Tests node field level access.
 *
 * @group node
 */
class NodeFieldAccessTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node'];

  /**
   * Fields that only users with administer nodes permissions can change.
   *
   * @var array
   */
  protected $administrativeFields = [
    'status',
    'promote',
    'sticky',
    'created',
    'uid',
  ];

  /**
   * These fields are automatically managed and can not be changed by any user.
   *
   * @var array
   */
  protected $readOnlyFields = ['changed', 'revision_uid', 'revision_timestamp'];

  /**
   * Tests permissions on nodes status field.
   */
  public function testAccessToAdministrativeFields() {

    // Create the page node type with revisions disabled.
    $page = NodeType::create([
      'type' => 'page',
      'new_revision' => FALSE,
    ]);
    $page->save();

    // Create the article node type with revisions enabled.
    $article = NodeType::create([
      'type' => 'article',
      'new_revision' => TRUE,
    ]);
    $article->save();

    // An administrator user. No user exists yet, ensure that the first user
    // does not have UID 1.
    $content_admin_user = $this->createUser(['uid' => 2], ['administer nodes']);

    // Two different editor users.
    $page_creator_user = $this->createUser([], ['create page content', 'edit own page content', 'delete own page content']);
    $page_manager_user = $this->createUser([], ['create page content', 'edit any page content', 'delete any page content']);

    // An unprivileged user.
    $page_unrelated_user = $this->createUser([], ['access content']);

    // List of all users
    $test_users = [
      $content_admin_user,
      $page_creator_user,
      $page_manager_user,
      $page_unrelated_user,
    ];

    // Create three "Basic pages". One is owned by our test-user
    // "page_creator", one by "page_manager", and one by someone else.
    $node1 = Node::create([
      'title' => $this->randomMachineName(8),
      'uid' => $page_creator_user->id(),
      'type' => 'page',
    ]);
    $node2 = Node::create([
      'title' => $this->randomMachineName(8),
      'uid' => $page_manager_user->id(),
      'type' => 'article',
    ]);
    $node3 = Node::create([
      'title' => $this->randomMachineName(8),
      'type' => 'page',
    ]);

    foreach ($this->administrativeFields as $field) {

      // Checks on view operations.
      foreach ($test_users as $account) {
        $may_view = $node1->{$field}->access('view', $account);
        $this->assertTrue($may_view, new FormattableMarkup('Any user may view the field @name.', ['@name' => $field]));
      }

      // Checks on edit operations.
      $may_update = $node1->{$field}->access('edit', $page_creator_user);
      $this->assertFalse($may_update, new FormattableMarkup('Users with permission "edit own page content" is not allowed to the field @name.', ['@name' => $field]));
      $may_update = $node2->{$field}->access('edit', $page_creator_user);
      $this->assertFalse($may_update, new FormattableMarkup('Users with permission "edit own page content" is not allowed to the field @name.', ['@name' => $field]));
      $may_update = $node2->{$field}->access('edit', $page_manager_user);
      $this->assertFalse($may_update, new FormattableMarkup('Users with permission "edit any page content" is not allowed to the field @name.', ['@name' => $field]));
      $may_update = $node1->{$field}->access('edit', $page_manager_user);
      $this->assertFalse($may_update, new FormattableMarkup('Users with permission "edit any page content" is not allowed to the field @name.', ['@name' => $field]));
      $may_update = $node2->{$field}->access('edit', $page_unrelated_user);
      $this->assertFalse($may_update, new FormattableMarkup('Users not having permission "edit any page content" is not allowed to the field @name.', ['@name' => $field]));
      $may_update = $node1->{$field}->access('edit', $content_admin_user) && $node3->status->access('edit', $content_admin_user);
      $this->assertTrue($may_update, new FormattableMarkup('Users with permission "administer nodes" may edit @name fields on all nodes.', ['@name' => $field]));
    }

    foreach ($this->readOnlyFields as $field) {
      // Check view operation.
      foreach ($test_users as $account) {
        $may_view = $node1->{$field}->access('view', $account);
        $this->assertTrue($may_view, new FormattableMarkup('Any user may view the field @name.', ['@name' => $field]));
      }

      // Check edit operation.
      foreach ($test_users as $account) {
        $may_view = $node1->{$field}->access('edit', $account);
        $this->assertFalse($may_view, new FormattableMarkup('No user is not allowed to edit the field @name.', ['@name' => $field]));
      }
    }

    // Check the revision_log field on node 1 which has revisions disabled.
    $may_update = $node1->revision_log->access('edit', $content_admin_user);
    $this->assertTrue($may_update, 'A user with permission "administer nodes" can edit the revision_log field when revisions are disabled.');
    $may_update = $node1->revision_log->access('edit', $page_creator_user);
    $this->assertFalse($may_update, 'A user without permission "administer nodes" can not edit the revision_log field when revisions are disabled.');

    // Check the revision_log field on node 2 which has revisions enabled.
    $may_update = $node2->revision_log->access('edit', $content_admin_user);
    $this->assertTrue($may_update, 'A user with permission "administer nodes" can edit the revision_log field when revisions are enabled.');
    $may_update = $node2->revision_log->access('edit', $page_creator_user);
    $this->assertTrue($may_update, 'A user without permission "administer nodes" can edit the revision_log field when revisions are enabled.');
  }

}
