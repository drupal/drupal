<?php

/**
 * @file
 * Definition of Drupal\search\Tests\SearchCommentTest.
 */

namespace Drupal\search\Tests;

/**
 * Test integration searching comments.
 */
class SearchCommentTest extends SearchTestBase {
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
    parent::setUp(array('comment'));

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
    // Enable check_plain() for 'Filtered HTML' text format.
    $filtered_html_format_id = 'filtered_html';
    $edit = array(
      'filters[filter_html_escape][status]' => TRUE,
    );
    $this->drupalPost('admin/config/content/formats/' . $filtered_html_format_id, $edit, t('Save configuration'));
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
    $edit_comment['comment_body[' . LANGUAGE_NOT_SPECIFIED . '][0][value]'] = '<h1>' . $comment_body . '</h1>';
    $full_html_format_id = 'full_html';
    $edit_comment['comment_body[' . LANGUAGE_NOT_SPECIFIED . '][0][format]'] = $full_html_format_id;
    $this->drupalPost('comment/reply/' . $node->nid, $edit_comment, t('Save'));

    // Invoke search index update.
    $this->drupalLogout();
    $this->cronRun();

    // Search for the comment subject.
    $edit = array(
      'search_block_form' => "'" . $edit_comment['subject'] . "'",
    );
    $this->drupalPost('', $edit, t('Search'));
    $this->assertText($node->title, t('Node found in search results.'));
    $this->assertText($edit_comment['subject'], t('Comment subject found in search results.'));

    // Search for the comment body.
    $edit = array(
      'search_block_form' => "'" . $comment_body . "'",
    );
    $this->drupalPost('', $edit, t('Search'));
    $this->assertText($node->title, t('Node found in search results.'));

    // Verify that comment is rendered using proper format.
    $this->assertText($comment_body, t('Comment body text found in search results.'));
    $this->assertNoRaw(t('n/a'), t('HTML in comment body is not hidden.'));
    $this->assertNoRaw(check_plain($edit_comment['comment_body[' . LANGUAGE_NOT_SPECIFIED . '][0][value]']), t('HTML in comment body is not escaped.'));

    // Hide comments.
    $this->drupalLogin($this->admin_user);
    $node->comment = 0;
    $node->save();

    // Invoke search index update.
    $this->drupalLogout();
    $this->cronRun();

    // Search for $title.
    $this->drupalPost('', $edit, t('Search'));
    $this->assertNoText($comment_body, t('Comment body text not found in search results.'));
  }

  /**
   * Verify access rules for comment indexing with different permissions.
   */
  function testSearchResultsCommentAccess() {
    $comment_body = 'Test comment body';
    $this->comment_subject = 'Test comment subject';
    $this->admin_role = $this->admin_user->roles;
    unset($this->admin_role[DRUPAL_AUTHENTICATED_RID]);
    $this->admin_role = key($this->admin_role);

    // Create a node.
    variable_set('comment_preview_article', DRUPAL_OPTIONAL);
    $this->node = $this->drupalCreateNode(array('type' => 'article'));

    // Post a comment using 'Full HTML' text format.
    $edit_comment = array();
    $edit_comment['subject'] = $this->comment_subject;
    $edit_comment['comment_body[' . LANGUAGE_NOT_SPECIFIED . '][0][value]'] = '<h1>' . $comment_body . '</h1>';
    $this->drupalPost('comment/reply/' . $this->node->nid, $edit_comment, t('Save'));

    $this->drupalLogout();
    $this->setRolePermissions(DRUPAL_ANONYMOUS_RID);
    $this->checkCommentAccess('Anon user has search permission but no access comments permission, comments should not be indexed');

    $this->setRolePermissions(DRUPAL_ANONYMOUS_RID, TRUE);
    $this->checkCommentAccess('Anon user has search permission and access comments permission, comments should be indexed', TRUE);

    $this->drupalLogin($this->admin_user);
    $this->drupalGet('admin/people/permissions');

    // Disable search access for authenticated user to test admin user.
    $this->setRolePermissions(DRUPAL_AUTHENTICATED_RID, FALSE, FALSE);

    $this->setRolePermissions($this->admin_role);
    $this->checkCommentAccess('Admin user has search permission but no access comments permission, comments should not be indexed');

    $this->setRolePermissions($this->admin_role, TRUE);
    $this->checkCommentAccess('Admin user has search permission and access comments permission, comments should be indexed', TRUE);

    $this->setRolePermissions(DRUPAL_AUTHENTICATED_RID);
    $this->checkCommentAccess('Authenticated user has search permission but no access comments permission, comments should not be indexed');

    $this->setRolePermissions(DRUPAL_AUTHENTICATED_RID, TRUE);
    $this->checkCommentAccess('Authenticated user has search permission and access comments permission, comments should be indexed', TRUE);

    // Verify that access comments permission is inherited from the
    // authenticated role.
    $this->setRolePermissions(DRUPAL_AUTHENTICATED_RID, TRUE, FALSE);
    $this->setRolePermissions($this->admin_role);
    $this->checkCommentAccess('Admin user has search permission and no access comments permission, but comments should be indexed because admin user inherits authenticated user\'s permission to access comments', TRUE);

    // Verify that search content permission is inherited from the authenticated
    // role.
    $this->setRolePermissions(DRUPAL_AUTHENTICATED_RID, TRUE, TRUE);
    $this->setRolePermissions($this->admin_role, TRUE, FALSE);
    $this->checkCommentAccess('Admin user has access comments permission and no search permission, but comments should be indexed because admin user inherits authenticated user\'s permission to search', TRUE);

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
  function checkCommentAccess($message, $assume_access = FALSE) {
    // Invoke search index update.
    search_touch_node($this->node->nid);
    $this->cronRun();

    // Search for the comment subject.
    $edit = array(
      'search_block_form' => "'" . $this->comment_subject . "'",
    );
    $this->drupalPost('', $edit, t('Search'));
    $method = $assume_access ? 'assertText' : 'assertNoText';
    $verb = $assume_access ? 'found' : 'not found';
    $this->{$method}($this->node->title, "Node $verb in search results: " . $message);
    $this->{$method}($this->comment_subject, "Comment subject $verb in search results: " . $message);
  }

  /**
   * Verify that 'add new comment' does not appear in search results or index.
   */
  function testAddNewComment() {
    // Create a node with a short body.
    $settings = array(
      'type' => 'article',
      'title' => 'short title',
      'body' => array(LANGUAGE_NOT_SPECIFIED => array(array('value' => 'short body text'))),
    );

    $user = $this->drupalCreateUser(array('search content', 'create article content', 'access content'));
    $this->drupalLogin($user);

    $node = $this->drupalCreateNode($settings);
    // Verify that if you view the node on its own page, 'add new comment'
    // is there.
    $this->drupalGet('node/' . $node->nid);
    $this->assertText(t('Add new comment'), t('Add new comment appears on node page'));

    // Run cron to index this page.
    $this->drupalLogout();
    $this->cronRun();

    // Search for 'comment'. Should be no results.
    $this->drupalLogin($user);
    $this->drupalPost('search/node', array('keys' => 'comment'), t('Search'));
    $this->assertText(t('Your search yielded no results'), t('No results searching for the word comment'));

    // Search for the node title. Should be found, and 'Add new comment' should
    // not be part of the search snippet.
    $this->drupalPost('search/node', array('keys' => 'short'), t('Search'));
    $this->assertText($node->title, t('Search for keyword worked'));
    $this->assertNoText(t('Add new comment'), t('Add new comment does not appear on search results page'));
  }
}
