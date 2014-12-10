<?php

/**
 * @file
 * Definition of Drupal\search\Tests\SearchCommentTest.
 */

namespace Drupal\search\Tests;

use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\Component\Utility\String;
use Drupal\field\Entity\FieldConfig;

/**
 * Tests integration searching comments.
 *
 * @group search
 */
class SearchCommentTest extends SearchTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('filter', 'node', 'comment');

  protected $admin_user;

  protected function setUp() {
    parent::setUp();

    $full_html_format = entity_create('filter_format', array(
      'format' => 'full_html',
      'name' => 'Full HTML',
      'weight' => 1,
      'filters' => array(),
    ));
    $full_html_format->save();

    // Create and log in an administrative user having access to the Full HTML
    // text format.
    $permissions = array(
      'administer filters',
      $full_html_format->getPermissionName(),
      'administer permissions',
      'create page content',
      'post comments',
      'skip comment approval',
      'access comments',
    );
    $this->admin_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->admin_user);
    // Add a comment field.
    $this->container->get('comment.manager')->addDefaultField('node', 'article');
  }

  /**
   * Verify that comments are rendered using proper format in search results.
   */
  function testSearchResultsComment() {
    $node_storage = $this->container->get('entity.manager')->getStorage('node');
    // Create basic_html format that escapes all HTML.
    $basic_html_format = entity_create('filter_format', array(
      'format' => 'basic_html',
      'name' => 'Basic HTML',
      'weight' => 1,
      'filters' => array(
        'filter_html_escape' => array('status' => 1),
      ),
      'roles' => array(DRUPAL_AUTHENTICATED_RID),
    ));
    $basic_html_format->save();

    $comment_body = 'Test comment body';

    // Make preview optional.
    $field = FieldConfig::loadByName('node', 'article', 'comment');
    $field->settings['preview'] = DRUPAL_OPTIONAL;
    $field->save();

    // Allow anonymous users to search content.
    $edit = array(
      DRUPAL_ANONYMOUS_RID . '[search content]' => 1,
      DRUPAL_ANONYMOUS_RID . '[access comments]' => 1,
      DRUPAL_ANONYMOUS_RID . '[post comments]' => 1,
    );
    $this->drupalPostForm('admin/people/permissions', $edit, t('Save permissions'));

    // Create a node.
    $node = $this->drupalCreateNode(array('type' => 'article'));
    // Post a comment using 'Full HTML' text format.
    $edit_comment = array();
    $edit_comment['subject[0][value]'] = 'Test comment subject';
    $edit_comment['comment_body[0][value]'] = '<h1>' . $comment_body . '</h1>';
    $full_html_format_id = 'full_html';
    $edit_comment['comment_body[0][format]'] = $full_html_format_id;
    $this->drupalPostForm('comment/reply/node/' . $node->id() .'/comment', $edit_comment, t('Save'));

    // Invoke search index update.
    $this->drupalLogout();
    $this->cronRun();

    // Search for the comment subject.
    $edit = array(
      'keys' => "'" . $edit_comment['subject[0][value]'] . "'",
    );
    $this->drupalPostForm('search/node', $edit, t('Search'));
    $node_storage->resetCache(array($node->id()));
    $node2 = $node_storage->load($node->id());
    $this->assertText($node2->label(), 'Node found in search results.');
    $this->assertText($edit_comment['subject[0][value]'], 'Comment subject found in search results.');

    // Search for the comment body.
    $edit = array(
      'keys' => "'" . $comment_body . "'",
    );
    $this->drupalPostForm(NULL, $edit, t('Search'));
    $this->assertText($node2->label(), 'Node found in search results.');

    // Verify that comment is rendered using proper format.
    $this->assertText($comment_body, 'Comment body text found in search results.');
    $this->assertNoRaw(t('n/a'), 'HTML in comment body is not hidden.');
    $this->assertNoEscaped($edit_comment['comment_body[0][value]'], 'HTML in comment body is not escaped.');

    // Hide comments.
    $this->drupalLogin($this->admin_user);
    $node->set('comment', CommentItemInterface::HIDDEN);
    $node->save();

    // Invoke search index update.
    $this->drupalLogout();
    $this->cronRun();

    // Search for $title.
    $this->drupalPostForm('search/node', $edit, t('Search'));
    $this->assertText(t('Your search yielded no results.'));
  }

  /**
   * Verify access rules for comment indexing with different permissions.
   */
  function testSearchResultsCommentAccess() {
    $comment_body = 'Test comment body';
    $this->comment_subject = 'Test comment subject';
    $roles = $this->admin_user->getRoles(TRUE);
    $this->admin_role = $roles[0];

    // Create a node.
    // Make preview optional.
    $field = FieldConfig::loadByName('node', 'article', 'comment');
    $field->settings['preview'] = DRUPAL_OPTIONAL;
    $field->save();
    $this->node = $this->drupalCreateNode(array('type' => 'article'));

    // Post a comment using 'Full HTML' text format.
    $edit_comment = array();
    $edit_comment['subject[0][value]'] = $this->comment_subject;
    $edit_comment['comment_body[0][value]'] = '<h1>' . $comment_body . '</h1>';
    $this->drupalPostForm('comment/reply/node/' . $this->node->id() . '/comment', $edit_comment, t('Save'));

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

    $this->drupalGet('node/' . $this->node->id());
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
    search_mark_for_reindex('node_search', $this->node->id());
    $this->cronRun();

    // Search for the comment subject.
    $edit = array(
      'keys' => "'" . $this->comment_subject . "'",
    );
    $this->drupalPostForm('search/node', $edit, t('Search'));

    if ($assume_access) {
      $expected_node_result = $this->assertText($this->node->label());
      $expected_comment_result = $this->assertText($this->comment_subject);
    }
    else {
      $expected_node_result = $this->assertText(t('Your search yielded no results.'));
      $expected_comment_result = $this->assertText(t('Your search yielded no results.'));
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

    $user = $this->drupalCreateUser(array(
      'search content',
      'create article content',
      'access content',
      'post comments',
      'access comments',
    ));
    $this->drupalLogin($user);

    $node = $this->drupalCreateNode($settings);
    // Verify that if you view the node on its own page, 'add new comment'
    // is there.
    $this->drupalGet('node/' . $node->id());
    $this->assertText(t('Add new comment'));

    // Run cron to index this page.
    $this->drupalLogout();
    $this->cronRun();

    // Search for 'comment'. Should be no results.
    $this->drupalLogin($user);
    $this->drupalPostForm('search/node', array('keys' => 'comment'), t('Search'));
    $this->assertText(t('Your search yielded no results'));

    // Search for the node title. Should be found, and 'Add new comment' should
    // not be part of the search snippet.
    $this->drupalPostForm('search/node', array('keys' => 'short'), t('Search'));
    $this->assertText($node->label(), 'Search for keyword worked');
    $this->assertNoText(t('Add new comment'));
  }
}
