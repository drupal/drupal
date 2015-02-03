<?php

/**
 * @file
 * Definition of Drupal\node\Tests\NodeAdminTest.
 */

namespace Drupal\node\Tests;

/**
 * Tests node administration page functionality.
 *
 * @group node
 */
class NodeAdminTest extends NodeTestBase {
  /**
   * A user with permission to bypass access content.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('views');

  protected function setUp() {
    parent::setUp();

    // Remove the "view own unpublished content" permission which is set
    // by default for authenticated users so we can test this permission
    // correctly.
    user_role_revoke_permissions(DRUPAL_AUTHENTICATED_RID, array('view own unpublished content'));

    $this->adminUser = $this->drupalCreateUser(array('access administration pages', 'access content overview', 'administer nodes', 'bypass node access'));
    $this->base_user_1 = $this->drupalCreateUser(array('access content overview'));
    $this->base_user_2 = $this->drupalCreateUser(array('access content overview', 'view own unpublished content'));
    $this->base_user_3 = $this->drupalCreateUser(array('access content overview', 'bypass node access'));
  }

  /**
   * Tests that the table sorting works on the content admin pages.
   */
  function testContentAdminSort() {
    $this->drupalLogin($this->adminUser);

    $changed = REQUEST_TIME;
    foreach (array('dd', 'aa', 'DD', 'bb', 'cc', 'CC', 'AA', 'BB') as $prefix) {
      $changed += 1000;
      $node = $this->drupalCreateNode(array('title' => $prefix . $this->randomMachineName(6)));
      db_update('node_field_data')
        ->fields(array('changed' => $changed))
        ->condition('nid', $node->id())
        ->execute();
    }

    // Test that the default sort by node.changed DESC actually fires properly.
    $nodes_query = db_select('node_field_data', 'n')
      ->fields('n', array('title'))
      ->orderBy('changed', 'DESC')
      ->execute()
      ->fetchCol();

    $this->drupalGet('admin/content');
    foreach ($nodes_query as $delta => $string) {
      $elements = $this->xpath('//table[contains(@class, :class)]/tbody/tr[' . ($delta + 1) . ']/td[2]/a[normalize-space(text())=:label]', array(':class' => 'views-table', ':label' => $string));
      $this->assertTrue(!empty($elements), 'The node was found in the correct order.');
    }

    // Compare the rendered HTML node list to a query for the nodes ordered by
    // title to account for possible database-dependent sort order.
    $nodes_query = db_select('node_field_data', 'n')
      ->fields('n', array('title'))
      ->orderBy('title')
      ->execute()
      ->fetchCol();

    $this->drupalGet('admin/content', array('query' => array('sort' => 'asc', 'order' => 'title')));
    foreach ($nodes_query as $delta => $string) {
      $elements = $this->xpath('//table[contains(@class, :class)]/tbody/tr[' . ($delta + 1) . ']/td[2]/a[normalize-space(text())=:label]', array(':class' => 'views-table', ':label' => $string));
      $this->assertTrue(!empty($elements), 'The node was found in the correct order.');
    }
  }

  /**
   * Tests content overview with different user permissions.
   *
   * Taxonomy filters are tested separately.
   *
   * @see TaxonomyNodeFilterTestCase
   */
  function testContentAdminPages() {
    $this->drupalLogin($this->adminUser);

    $nodes['published_page'] = $this->drupalCreateNode(array('type' => 'page'));
    $nodes['published_article'] = $this->drupalCreateNode(array('type' => 'article'));
    $nodes['unpublished_page_1'] = $this->drupalCreateNode(array('type' => 'page', 'uid' => $this->base_user_1->id(), 'status' => 0));
    $nodes['unpublished_page_2'] = $this->drupalCreateNode(array('type' => 'page', 'uid' => $this->base_user_2->id(), 'status' => 0));

    // Verify view, edit, and delete links for any content.
    $this->drupalGet('admin/content');
    $this->assertResponse(200);
    foreach ($nodes as $node) {
      $this->assertLinkByHref('node/' . $node->id());
      $this->assertLinkByHref('node/' . $node->id() . '/edit');
      $this->assertLinkByHref('node/' . $node->id() . '/delete');
    }

    // Verify filtering by publishing status.
    $this->drupalGet('admin/content', array('query' => array('status' => TRUE)));

    $this->assertLinkByHref('node/' . $nodes['published_page']->id() . '/edit');
    $this->assertLinkByHref('node/' . $nodes['published_article']->id() . '/edit');
    $this->assertNoLinkByHref('node/' . $nodes['unpublished_page_1']->id() . '/edit');

    // Verify filtering by status and content type.
    $this->drupalGet('admin/content', array('query' => array('status' => TRUE, 'type' => 'page')));

    $this->assertLinkByHref('node/' . $nodes['published_page']->id() . '/edit');
    $this->assertNoLinkByHref('node/' . $nodes['published_article']->id() . '/edit');

    // Verify no operation links are displayed for regular users.
    $this->drupalLogout();
    $this->drupalLogin($this->base_user_1);
    $this->drupalGet('admin/content');
    $this->assertResponse(200);
    $this->assertLinkByHref('node/' . $nodes['published_page']->id());
    $this->assertLinkByHref('node/' . $nodes['published_article']->id());
    $this->assertNoLinkByHref('node/' . $nodes['published_page']->id() . '/edit');
    $this->assertNoLinkByHref('node/' . $nodes['published_page']->id() . '/delete');
    $this->assertNoLinkByHref('node/' . $nodes['published_article']->id() . '/edit');
    $this->assertNoLinkByHref('node/' . $nodes['published_article']->id() . '/delete');

    // Verify no unpublished content is displayed without permission.
    $this->assertNoLinkByHref('node/' . $nodes['unpublished_page_1']->id());
    $this->assertNoLinkByHref('node/' . $nodes['unpublished_page_1']->id() . '/edit');
    $this->assertNoLinkByHref('node/' . $nodes['unpublished_page_1']->id() . '/delete');

    // Verify no tableselect.
    $this->assertNoFieldByName('nodes[' . $nodes['published_page']->id() . ']', '', 'No tableselect found.');

    // Verify unpublished content is displayed with permission.
    $this->drupalLogout();
    $this->drupalLogin($this->base_user_2);
    $this->drupalGet('admin/content');
    $this->assertResponse(200);
    $this->assertLinkByHref('node/' . $nodes['unpublished_page_2']->id());
    // Verify no operation links are displayed.
    $this->assertNoLinkByHref('node/' . $nodes['unpublished_page_2']->id() . '/edit');
    $this->assertNoLinkByHref('node/' . $nodes['unpublished_page_2']->id() . '/delete');

    // Verify user cannot see unpublished content of other users.
    $this->assertNoLinkByHref('node/' . $nodes['unpublished_page_1']->id());
    $this->assertNoLinkByHref('node/' . $nodes['unpublished_page_1']->id() . '/edit');
    $this->assertNoLinkByHref('node/' . $nodes['unpublished_page_1']->id() . '/delete');

    // Verify no tableselect.
    $this->assertNoFieldByName('nodes[' . $nodes['unpublished_page_2']->id() . ']', '', 'No tableselect found.');

    // Verify node access can be bypassed.
    $this->drupalLogout();
    $this->drupalLogin($this->base_user_3);
    $this->drupalGet('admin/content');
    $this->assertResponse(200);
    foreach ($nodes as $node) {
      $this->assertLinkByHref('node/' . $node->id());
      $this->assertLinkByHref('node/' . $node->id() . '/edit');
      $this->assertLinkByHref('node/' . $node->id() . '/delete');
    }
  }

}
