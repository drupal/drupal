<?php

/**
 * @file
 * Definition of Drupal\search\Tests\SearchCommentTest.
 */

namespace Drupal\search\Tests;

use Drupal\Core\Language\Language;

/**
 * Test integration searching comments.
 */
class SearchCommentTest extends SearchTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('comment');

  protected $profile = 'standard';

  protected $admin_user;

  public static function getInfo() {
    return array(
      'name' => 'Comment Search tests',
      'description' => 'Verify text formats and filters used elsewhere.',
      'group' => 'Search',
    );
  }

  function setUp() {
    parent::setUp();

    // Create and log in an administrative user having access to the Full HTML
    // text format.
    $full_html_format = filter_format_load('full_html');
    $permissions = array(
      'administer filters',
      filter_permission_name($full_html_format),
      'administer permissions',
      'create page content',
      'skip comment approval',
      'access comments',
    );
    $this->admin_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->admin_user);
  }

  /**
   * Verify that comments are rendered using proper format in search results.
   */
  function testSearchResultsComment() {
    $comment_body = 'Test comment body';

    variable_set('comment_preview_article', DRUPAL_OPTIONAL);
    // Enable check_plain() for 'Basic HTML' text format.
    $basic_html_format_id = 'basic_html';
    $edit = array(
      'filters[filter_html_escape][status]' => TRUE,
    );
    $this->drupalPost('admin/config/content/formats/manage/' . $basic_html_format_id, $edit, t('Save configuration'));
    // Allow anonymous users to search content.
    $edit = array(
      DRUPAL_ANONYMOUS_RID . '[search content]' => 1,
      DRUPAL_ANONYMOUS_RID . '[access comments]' => 1,
      DRUPAL_ANONYMOUS_RID . '[post comments]' => 1,
    );
    $this->drupalPost('admin/people/permissions', $edit, t('Save permissions'));

    // Create a node.
    $node = $this->drupalCreateNode(array('type' => 'article'));
    // Post a comment using 'Full HTML' text format.
    $edit_comment = array();
    $edit_comment['subject'] = 'Test comment subject';
    $edit_comment['comment_body[' . Language::LANGCODE_NOT_SPECIFIED . '][0][value]'] = '<h1>' . $comment_body . '</h1>';
    $full_html_format_id = 'full_html';
    $edit_comment['comment_body[' . Language::LANGCODE_NOT_SPECIFIED . '][0][format]'] = $full_html_format_id;
    $this->drupalPost('comment/reply/' . $node->nid, $edit_comment, t('Save'));

    // Invoke search index update.
    $this->drupalLogout();
    $this->cronRun();

    // Search for the comment subject.
    $edit = array(
      'search_block_form' => "'" . $edit_comment['subject'] . "'",
    );
    $this->drupalPost('', $edit, t('Search'));
    $node2 = node_load($node->nid, TRUE);
    $this->assertText($node2->label(), 'Node found in search results.');
    $this->assertText($edit_comment['subject'], 'Comment subject found in search results.');

    // Search for the comment body.
    $edit = array(
      'search_block_form' => "'" . $comment_body . "'",
    );
    $this->drupalPost('', $edit, t('Search'));
    $this->assertText($node2->label(), 'Node found in search results.');

    // Verify that comment is rendered using proper format.
    $this->assertText($comment_body, 'Comment body text found in search results.');
    $this->assertNoRaw(t('n/a'), 'HTML in comment body is not hidden.');
    $this->assertNoRaw(check_plain($edit_comment['comment_body[' . Language::LANGCODE_NOT_SPECIFIED . '][0][value]']), 'HTML in comment body is not escaped.');

    // Hide comments.
    $this->drupalLogin($this->admin_user);
    $node->comment = 0;
    $node->save();

    // Invoke search index update.
    $this->drupalLogout();
    $this->cronRun();

    // Search for $title.
    $this->drupalPost('', $edit, t('Search'));
    $this->assertNoText($comment_body, 'Comment body text not found in search results.');
  }

  /**
   * Verify access rules for comment indexing with different permissions.
   */
  function testSearchResultsCommentAccess() {
    $comment_body = 'Test comment body';
    $this->comment_subject = 'Test comment subject';
    $this->admin_role = $this->admin_user->roles[0];

    // Create a node.
    variable_set('comment_preview_article', DRUPAL_OPTIONAL);
    $this->node = $this->drupalCreateNode(array('type' => 'article'));

    // Post a comment using 'Full HTML' text format.
    $edit_comment = array();
    $edit_comment['subject'] = $this->comment_subject;
    $edit_comment['comment_body[' . Language::LANGCODE_NOT_SPECIFIED . '][0][value]'] = '<h1>' . $comment_body . '</h1>';
    $this->drupalPost('comment/reply/' . $this->node->nid, $edit_comment, t('Save'));

    $this->drupalLogout();
    $this->setRolePermissions(DRUPAL_ANONYMOUS_RID);
    $this->assertCommentAccess(FALSE, 'Anon user has search permission but no access comments permission, comments should not be indexed');

    $this->setRolePermissions(DRUPAL_ANONYMOUS_RID, TRUE);
    $this->assertCommentAccess(TRUE, 'Anon user has search permission and access comments permission, comments should be indexed');

    $this->drupalLogin($this->admin_user);
    $this->drupalGet('admin/people/permissions');

    // Disable search access for authenticated user to test admin user.
    $this->setRolePermissions(DRUPAL_AUTHENTICATED_RID, FALSE, FALSE);

    $this->setRolePermissions($this->admin_role);
    $this->assertCommentAccess(FALSE, 'Admin user has search permission but no access comments permission, comments should not be indexed');

    $this->setRolePermissions($this->admin_role, TRUE);
    $this->assertCommentAccess(TRUE, 'Admin user has search permission and access comments permission, comments should be indexed');

    $this->setRolePermissions(DRUPAL_AUTHENTICATED_RID);
    $this->assertCommentAccess(FALSE, 'Authenticated user has search permission but no access comments permission, comments should not be indexed');

    $this->setRolePermissions(DRUPAL_AUTHENTICATED_RID, TRUE);
    $this->assertCommentAccess(TRUE, 'Authenticated user has search permission and access comments permission, comments should be indexed');

    // Verify that access comments permission is inherited from the
    // authenticated role.
    $this->setRolePermissions(DRUPAL_AUTHENTICATED_RID, TRUE, FALSE);
    $this->setRolePermissions($this->admin_role);
    $this->assertCommentAccess(TRUE, 'Admin user has search permission and no access comments permission, but comments should be indexed because admin user inherits authenticated user\'s permission to access comments');

    // Verify that search content permission is inherited from the authenticated
    // role.
    $this->setRolePermissions(DRUPAL_AUTHENTICATED_RID, TRUE, TRUE);
    $this->setRolePermissions($this->admin_role, TRUE, FALSE);
    $this->assertCommentAccess(TRUE, 'Admin user has access comments permission and no search permission, but comments should be indexed because admin user inherits authenticated user\'s permission to search');
  }

  /**
   * Set permissions for role.
   */
  function setRolePermissions($rid, $access_comments = FALSE, $search_content = TRUE) {
    $permissions = array(
      'access comments' => $access_comments,
      'search content' => $search_content,
    );
    user_role_change_permissions($rid, $permissions);
  }

  /**
   * Update search index and search for comment.
   */
  function assertCommentAccess($assume_access, $message) {
    // Invoke search index update.
    search_touch_node($this->node->nid);
    $this->cronRun();

    // Search for the comment subject.
    $edit = array(
      'search_block_form' => "'" . $this->comment_subject . "'",
    );
    $this->drupalPost('', $edit, t('Search'));

    if ($assume_access) {
      $expected_node_result = $this->assertText($this->node->label());
      $expected_comment_result = $this->assertText($this->comment_subject);
    }
    else {
      $expected_node_result = $this->assertNoText($this->node->label());
      $expected_comment_result = $this->assertNoText($this->comment_subject);
    }
    $this->assertTrue($expected_node_result && $expected_comment_result, $message);
  }

  /**
   * Verify that 'add new comment' does not appear in search results or index.
   */
  function testAddNewComment() {
    // Create a node with a short body.
    $settings = array(
      'type' => 'article',
      'title' => 'short title',
      'body' => array(array('value' => 'short body text')),
    );

    $user = $this->drupalCreateUser(array('search content', 'create article content', 'access content'));
    $this->drupalLogin($user);

    $node = $this->drupalCreateNode($settings);
    // Verify that if you view the node on its own page, 'add new comment'
    // is there.
    $this->drupalGet('node/' . $node->nid);
    $this->assertText(t('Add new comment'), 'Add new comment appears on node page');

    // Run cron to index this page.
    $this->drupalLogout();
    $this->cronRun();

    // Search for 'comment'. Should be no results.
    $this->drupalLogin($user);
    $this->drupalPost('search/node', array('keys' => 'comment'), t('Search'));
    $this->assertText(t('Your search yielded no results'), 'No results searching for the word comment');

    // Search for the node title. Should be found, and 'Add new comment' should
    // not be part of the search snippet.
    $this->drupalPost('search/node', array('keys' => 'short'), t('Search'));
    $this->assertText($node->label(), 'Search for keyword worked');
    $this->assertNoText(t('Add new comment'), 'Add new comment does not appear on search results page');
  }
}
