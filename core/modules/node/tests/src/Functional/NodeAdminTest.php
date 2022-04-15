<?php

namespace Drupal\Tests\node\Functional;

use Drupal\Core\Database\Database;
use Drupal\user\RoleInterface;

/**
 * Tests node administration page functionality.
 *
 * @group node
 */
class NodeAdminTest extends NodeTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with permission to bypass access content.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * A user with the 'access content overview' permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $baseUser1;

  /**
   * A normal user with permission to view own unpublished content.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $baseUser2;

  /**
   * A normal user with permission to bypass node access content.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $baseUser3;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['views'];

  protected function setUp(): void {
    parent::setUp();

    // Remove the "view own unpublished content" permission which is set
    // by default for authenticated users so we can test this permission
    // correctly.
    user_role_revoke_permissions(RoleInterface::AUTHENTICATED_ID, ['view own unpublished content']);

    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
      'access content overview',
      'administer nodes',
      'bypass node access',
    ]);
    $this->baseUser1 = $this->drupalCreateUser(['access content overview']);
    $this->baseUser2 = $this->drupalCreateUser([
      'access content overview',
      'view own unpublished content',
    ]);
    $this->baseUser3 = $this->drupalCreateUser([
      'access content overview',
      'bypass node access',
    ]);
  }

  /**
   * Tests that the table sorting works on the content admin pages.
   */
  public function testContentAdminSort() {
    $this->drupalLogin($this->adminUser);

    $changed = REQUEST_TIME;
    $connection = Database::getConnection();
    foreach (['dd', 'aa', 'DD', 'bb', 'cc', 'CC', 'AA', 'BB'] as $prefix) {
      $changed += 1000;
      $node = $this->drupalCreateNode(['title' => $prefix . $this->randomMachineName(6)]);
      $connection->update('node_field_data')
        ->fields(['changed' => $changed])
        ->condition('nid', $node->id())
        ->execute();
    }

    // Test that the default sort by node.changed DESC actually fires properly.
    $nodes_query = $connection->select('node_field_data', 'n')
      ->fields('n', ['title'])
      ->orderBy('changed', 'DESC')
      ->execute()
      ->fetchCol();

    $this->drupalGet('admin/content');
    foreach ($nodes_query as $delta => $string) {
      // Verify that the node was found in the correct order.
      $this->assertSession()->elementExists('xpath', $this->assertSession()->buildXPathQuery('//table/tbody/tr[' . ($delta + 1) . ']/td[2]/a[normalize-space(text())=:label]', [
        ':label' => $string,
      ]));
    }

    // Compare the rendered HTML node list to a query for the nodes ordered by
    // title to account for possible database-dependent sort order.
    $nodes_query = $connection->select('node_field_data', 'n')
      ->fields('n', ['title'])
      ->orderBy('title')
      ->execute()
      ->fetchCol();

    $this->drupalGet('admin/content', ['query' => ['sort' => 'asc', 'order' => 'title']]);
    foreach ($nodes_query as $delta => $string) {
      // Verify that the node was found in the correct order.
      $this->assertSession()->elementExists('xpath', $this->assertSession()->buildXPathQuery('//table/tbody/tr[' . ($delta + 1) . ']/td[2]/a[normalize-space(text())=:label]', [
        ':label' => $string,
      ]));
    }
  }

  /**
   * Tests content overview with different user permissions.
   *
   * Taxonomy filters are tested separately.
   *
   * @see TaxonomyNodeFilterTestCase
   */
  public function testContentAdminPages() {
    $this->drupalLogin($this->adminUser);

    // Use an explicit changed time to ensure the expected order in the content
    // admin listing. We want these to appear in the table in the same order as
    // they appear in the following code, and the 'content' View has a table
    // style configuration with a default sort on the 'changed' field DESC.
    $time = time();
    $nodes['published_page'] = $this->drupalCreateNode(['type' => 'page', 'changed' => $time--]);
    $nodes['published_article'] = $this->drupalCreateNode(['type' => 'article', 'changed' => $time--]);
    $nodes['unpublished_page_1'] = $this->drupalCreateNode(['type' => 'page', 'changed' => $time--, 'uid' => $this->baseUser1->id(), 'status' => 0]);
    $nodes['unpublished_page_2'] = $this->drupalCreateNode(['type' => 'page', 'changed' => $time, 'uid' => $this->baseUser2->id(), 'status' => 0]);

    // Verify view, edit, and delete links for any content.
    $this->drupalGet('admin/content');
    $this->assertSession()->statusCodeEquals(200);

    $node_type_labels = $this->xpath('//td[contains(@class, "views-field-type")]');
    $delta = 0;
    foreach ($nodes as $node) {
      $this->assertSession()->linkByHrefExists('node/' . $node->id());
      $this->assertSession()->linkByHrefExists('node/' . $node->id() . '/edit');
      $this->assertSession()->linkByHrefExists('node/' . $node->id() . '/delete');
      // Verify that we can see the content type label.
      $this->assertEquals(trim($node_type_labels[$delta]->getText()), $node->type->entity->label());
      $delta++;
    }

    // Verify filtering by publishing status.
    $this->drupalGet('admin/content', ['query' => ['status' => TRUE]]);

    $this->assertSession()->linkByHrefExists('node/' . $nodes['published_page']->id() . '/edit');
    $this->assertSession()->linkByHrefExists('node/' . $nodes['published_article']->id() . '/edit');
    $this->assertSession()->linkByHrefNotExists('node/' . $nodes['unpublished_page_1']->id() . '/edit');

    // Verify filtering by status and content type.
    $this->drupalGet('admin/content', ['query' => ['status' => TRUE, 'type' => 'page']]);

    $this->assertSession()->linkByHrefExists('node/' . $nodes['published_page']->id() . '/edit');
    $this->assertSession()->linkByHrefNotExists('node/' . $nodes['published_article']->id() . '/edit');

    // Verify no operation links are displayed for regular users.
    $this->drupalLogout();
    $this->drupalLogin($this->baseUser1);
    $this->drupalGet('admin/content');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkByHrefExists('node/' . $nodes['published_page']->id());
    $this->assertSession()->linkByHrefExists('node/' . $nodes['published_article']->id());
    $this->assertSession()->linkByHrefNotExists('node/' . $nodes['published_page']->id() . '/edit');
    $this->assertSession()->linkByHrefNotExists('node/' . $nodes['published_page']->id() . '/delete');
    $this->assertSession()->linkByHrefNotExists('node/' . $nodes['published_article']->id() . '/edit');
    $this->assertSession()->linkByHrefNotExists('node/' . $nodes['published_article']->id() . '/delete');

    // Verify no unpublished content is displayed without permission.
    $this->assertSession()->linkByHrefNotExists('node/' . $nodes['unpublished_page_1']->id());
    $this->assertSession()->linkByHrefNotExists('node/' . $nodes['unpublished_page_1']->id() . '/edit');
    $this->assertSession()->linkByHrefNotExists('node/' . $nodes['unpublished_page_1']->id() . '/delete');

    // Verify no tableselect.
    $this->assertSession()->fieldNotExists('nodes[' . $nodes['published_page']->id() . ']');

    // Verify unpublished content is displayed with permission.
    $this->drupalLogout();
    $this->drupalLogin($this->baseUser2);
    $this->drupalGet('admin/content');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkByHrefExists('node/' . $nodes['unpublished_page_2']->id());
    // Verify no operation links are displayed.
    $this->assertSession()->linkByHrefNotExists('node/' . $nodes['unpublished_page_2']->id() . '/edit');
    $this->assertSession()->linkByHrefNotExists('node/' . $nodes['unpublished_page_2']->id() . '/delete');

    // Verify user cannot see unpublished content of other users.
    $this->assertSession()->linkByHrefNotExists('node/' . $nodes['unpublished_page_1']->id());
    $this->assertSession()->linkByHrefNotExists('node/' . $nodes['unpublished_page_1']->id() . '/edit');
    $this->assertSession()->linkByHrefNotExists('node/' . $nodes['unpublished_page_1']->id() . '/delete');

    // Verify no tableselect.
    $this->assertSession()->fieldNotExists('nodes[' . $nodes['unpublished_page_2']->id() . ']');

    // Verify node access can be bypassed.
    $this->drupalLogout();
    $this->drupalLogin($this->baseUser3);
    $this->drupalGet('admin/content');
    $this->assertSession()->statusCodeEquals(200);
    foreach ($nodes as $node) {
      $this->assertSession()->linkByHrefExists('node/' . $node->id());
      $this->assertSession()->linkByHrefExists('node/' . $node->id() . '/edit');
      $this->assertSession()->linkByHrefExists('node/' . $node->id() . '/delete');
    }
  }

}
