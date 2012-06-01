<?php

/**
 * @file
 * Definition of Drupal\node\Tests\NodeAdminTest.
 */

namespace Drupal\node\Tests;

/**
 * Test node administration page functionality.
 */
class NodeAdminTest extends NodeTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Node administration',
      'description' => 'Test node administration page functionality.',
      'group' => 'Node',
    );
  }

  function setUp() {
    parent::setUp();

    // Remove the "view own unpublished content" permission which is set
    // by default for authenticated users so we can test this permission
    // correctly.
    user_role_revoke_permissions(DRUPAL_AUTHENTICATED_RID, array('view own unpublished content'));

    $this->admin_user = $this->drupalCreateUser(array('access administration pages', 'access content overview', 'administer nodes', 'bypass node access'));
    $this->base_user_1 = $this->drupalCreateUser(array('access content overview'));
    $this->base_user_2 = $this->drupalCreateUser(array('access content overview', 'view own unpublished content'));
    $this->base_user_3 = $this->drupalCreateUser(array('access content overview', 'bypass node access'));
  }

  /**
   * Tests that the table sorting works on the content admin pages.
   */
  function testContentAdminSort() {
    $this->drupalLogin($this->admin_user);
    foreach (array('dd', 'aa', 'DD', 'bb', 'cc', 'CC', 'AA', 'BB') as $prefix) {
      $this->drupalCreateNode(array('title' => $prefix . $this->randomName(6)));
    }

    // Test that the default sort by node.changed DESC actually fires properly.
    $nodes_query = db_select('node', 'n')
      ->fields('n', array('nid'))
      ->orderBy('changed', 'DESC')
      ->execute()
      ->fetchCol();

    $nodes_form = array();
    $this->drupalGet('admin/content');
    foreach ($this->xpath('//table/tbody/tr/td/div/input/@value') as $input) {
      $nodes_form[] = $input;
    }
    $this->assertEqual($nodes_query, $nodes_form, 'Nodes are sorted in the form according to the default query.');

    // Compare the rendered HTML node list to a query for the nodes ordered by
    // title to account for possible database-dependent sort order.
    $nodes_query = db_select('node', 'n')
      ->fields('n', array('nid'))
      ->orderBy('title')
      ->execute()
      ->fetchCol();

    $nodes_form = array();
    $this->drupalGet('admin/content', array('query' => array('sort' => 'asc', 'order' => 'Title')));
    foreach ($this->xpath('//table/tbody/tr/td/div/input/@value') as $input) {
      $nodes_form[] = $input;
    }
    $this->assertEqual($nodes_query, $nodes_form, 'Nodes are sorted in the form the same as they are in the query.');
  }

  /**
   * Tests content overview with different user permissions.
   *
   * Taxonomy filters are tested separately.
   * @see TaxonomyNodeFilterTestCase
   */
  function testContentAdminPages() {
    $this->drupalLogin($this->admin_user);

    $nodes['published_page'] = $this->drupalCreateNode(array('type' => 'page'));
    $nodes['published_article'] = $this->drupalCreateNode(array('type' => 'article'));
    $nodes['unpublished_page_1'] = $this->drupalCreateNode(array('type' => 'page', 'uid' => $this->base_user_1->uid, 'status' => 0));
    $nodes['unpublished_page_2'] = $this->drupalCreateNode(array('type' => 'page', 'uid' => $this->base_user_2->uid, 'status' => 0));

    // Verify view, edit, and delete links for any content.
    $this->drupalGet('admin/content');
    $this->assertResponse(200);
    foreach ($nodes as $node) {
      $this->assertLinkByHref('node/' . $node->nid);
      $this->assertLinkByHref('node/' . $node->nid . '/edit');
      $this->assertLinkByHref('node/' . $node->nid . '/delete');
      // Verify tableselect.
      $this->assertFieldByName('nodes[' . $node->nid . ']', '', t('Tableselect found.'));
    }

    // Verify filtering by publishing status.
    $edit = array(
      'status' => 'status-1',
    );
    $this->drupalPost(NULL, $edit, t('Filter'));

    $this->assertRaw(t('where %property is %value', array('%property' => t('status'), '%value' => 'published')), t('Content list is filtered by status.'));

    $this->assertLinkByHref('node/' . $nodes['published_page']->nid . '/edit');
    $this->assertLinkByHref('node/' . $nodes['published_article']->nid . '/edit');
    $this->assertNoLinkByHref('node/' . $nodes['unpublished_page_1']->nid . '/edit');

    // Verify filtering by status and content type.
    $edit = array(
      'type' => 'page',
    );
    $this->drupalPost(NULL, $edit, t('Refine'));

    $this->assertRaw(t('where %property is %value', array('%property' => t('status'), '%value' => 'published')), t('Content list is filtered by status.'));
    $this->assertRaw(t('and where %property is %value', array('%property' => t('type'), '%value' => 'Basic page')), t('Content list is filtered by content type.'));

    $this->assertLinkByHref('node/' . $nodes['published_page']->nid . '/edit');
    $this->assertNoLinkByHref('node/' . $nodes['published_article']->nid . '/edit');

    // Verify no operation links are displayed for regular users.
    $this->drupalLogout();
    $this->drupalLogin($this->base_user_1);
    $this->drupalGet('admin/content');
    $this->assertResponse(200);
    $this->assertLinkByHref('node/' . $nodes['published_page']->nid);
    $this->assertLinkByHref('node/' . $nodes['published_article']->nid);
    $this->assertNoLinkByHref('node/' . $nodes['published_page']->nid . '/edit');
    $this->assertNoLinkByHref('node/' . $nodes['published_page']->nid . '/delete');
    $this->assertNoLinkByHref('node/' . $nodes['published_article']->nid . '/edit');
    $this->assertNoLinkByHref('node/' . $nodes['published_article']->nid . '/delete');

    // Verify no unpublished content is displayed without permission.
    $this->assertNoLinkByHref('node/' . $nodes['unpublished_page_1']->nid);
    $this->assertNoLinkByHref('node/' . $nodes['unpublished_page_1']->nid . '/edit');
    $this->assertNoLinkByHref('node/' . $nodes['unpublished_page_1']->nid . '/delete');

    // Verify no tableselect.
    $this->assertNoFieldByName('nodes[' . $nodes['published_page']->nid . ']', '', t('No tableselect found.'));

    // Verify unpublished content is displayed with permission.
    $this->drupalLogout();
    $this->drupalLogin($this->base_user_2);
    $this->drupalGet('admin/content');
    $this->assertResponse(200);
    $this->assertLinkByHref('node/' . $nodes['unpublished_page_2']->nid);
    // Verify no operation links are displayed.
    $this->assertNoLinkByHref('node/' . $nodes['unpublished_page_2']->nid . '/edit');
    $this->assertNoLinkByHref('node/' . $nodes['unpublished_page_2']->nid . '/delete');

    // Verify user cannot see unpublished content of other users.
    $this->assertNoLinkByHref('node/' . $nodes['unpublished_page_1']->nid);
    $this->assertNoLinkByHref('node/' . $nodes['unpublished_page_1']->nid . '/edit');
    $this->assertNoLinkByHref('node/' . $nodes['unpublished_page_1']->nid . '/delete');

    // Verify no tableselect.
    $this->assertNoFieldByName('nodes[' . $nodes['unpublished_page_2']->nid . ']', '', t('No tableselect found.'));

    // Verify node access can be bypassed.
    $this->drupalLogout();
    $this->drupalLogin($this->base_user_3);
    $this->drupalGet('admin/content');
    $this->assertResponse(200);
    foreach ($nodes as $node) {
      $this->assertLinkByHref('node/' . $node->nid);
      $this->assertLinkByHref('node/' . $node->nid . '/edit');
      $this->assertLinkByHref('node/' . $node->nid . '/delete');
    }
  }
}
