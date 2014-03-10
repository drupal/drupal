<?php

/**
 * @file
 * Contains \Drupal\comment\Tests\CommentNonNodeTest.
 */

namespace Drupal\comment\Tests;

use Drupal\comment\CommentInterface;
use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\simpletest\WebTestBase;
use Drupal\Core\Entity\EntityInterface;

/**
 * Tests basic comment functionality against the entity_test entity type.
 */
class CommentNonNodeTest extends WebTestBase {

  public static $modules = array('comment', 'user', 'field_ui', 'entity_test');

  /**
   * An administrative user with permission to configure comment settings.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $admin_user;

  public static function getInfo() {
    return array(
      'name' => 'Comment non-node tests',
      'description' => 'Test commenting on a test entity.',
      'group' => 'Comment',
    );
  }

  function setUp() {
    parent::setUp();

    // Create a bundle for entity_test.
    entity_test_create_bundle('entity_test', 'Entity Test', 'entity_test');
    // Create comment field on entity_test bundle.
    $this->container->get('comment.manager')->addDefaultField('entity_test', 'entity_test');

    // Create test user.
    $this->admin_user = $this->drupalCreateUser(array(
      'administer comments',
      'skip comment approval',
      'post comments',
      'access comments',
      'view test entity',
      'administer entity_test content',
    ));

    // Enable anonymous and authenticated user comments.
    user_role_grant_permissions(DRUPAL_ANONYMOUS_RID, array(
      'access comments',
      'post comments',
      'skip comment approval',
    ));
    user_role_grant_permissions(DRUPAL_AUTHENTICATED_RID, array(
      'access comments',
      'post comments',
      'skip comment approval',
    ));

    // Create a test entity.
    $random_label = $this->randomName();
    $data = array('type' => 'entity_test', 'name' => $random_label);
    $this->entity = entity_create('entity_test', $data);
    $this->entity->save();
  }

  /**
   * Posts a comment.
   *
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   Entity to post comment on or NULL to post to the previously loaded page.
   * @param $comment
   *   Comment body.
   * @param $subject
   *   Comment subject.
   * @param $contact
   *   Set to NULL for no contact info, TRUE to ignore success checking, and
   *   array of values to set contact info.
   */
  function postComment(EntityInterface $entity, $comment, $subject = '', $contact = NULL) {
    $edit = array();
    $edit['comment_body[0][value]'] = $comment;

    $instance = $this->container->get('field.info')->getInstance('entity_test', 'entity_test', 'comment');
    $preview_mode = $instance->getSetting('preview');
    $subject_mode = $instance->getSetting('subject');

    // Must get the page before we test for fields.
    if ($entity !== NULL) {
      $this->drupalGet('comment/reply/entity_test/' . $entity->id() . '/comment');
    }

    if ($subject_mode == TRUE) {
      $edit['subject'] = $subject;
    }
    else {
      $this->assertNoFieldByName('subject', '', 'Subject field not found.');
    }

    if ($contact !== NULL && is_array($contact)) {
      $edit += $contact;
    }
    switch ($preview_mode) {
      case DRUPAL_REQUIRED:
        // Preview required so no save button should be found.
        $this->assertNoFieldByName('op', t('Save'), 'Save button not found.');
        $this->drupalPostForm(NULL, $edit, t('Preview'));
        // Don't break here so that we can test post-preview field presence and
        // function below.
      case DRUPAL_OPTIONAL:
        $this->assertFieldByName('op', t('Preview'), 'Preview button found.');
        $this->assertFieldByName('op', t('Save'), 'Save button found.');
        $this->drupalPostForm(NULL, $edit, t('Save'));
        break;

      case DRUPAL_DISABLED:
        $this->assertNoFieldByName('op', t('Preview'), 'Preview button not found.');
        $this->assertFieldByName('op', t('Save'), 'Save button found.');
        $this->drupalPostForm(NULL, $edit, t('Save'));
        break;
    }
    $match = array();
    // Get comment ID
    preg_match('/#comment-([0-9]+)/', $this->getURL(), $match);

    // Get comment.
    if ($contact !== TRUE) { // If true then attempting to find error message.
      if ($subject) {
        $this->assertText($subject, 'Comment subject posted.');
      }
      $this->assertText($comment, 'Comment body posted.');
      $this->assertTrue((!empty($match) && !empty($match[1])), 'Comment ID found.');
    }

    if (isset($match[1])) {
      return entity_load('comment', $match[1]);
    }
  }

  /**
   * Checks current page for specified comment.
   *
   * @param \Drupal\comment\CommentInterface $comment
   *   The comment object.
   * @param boolean $reply
   *   Boolean indicating whether the comment is a reply to another comment.
   *
   * @return boolean
   *   Boolean indicating whether the comment was found.
   */
  function commentExists(CommentInterface $comment = NULL, $reply = FALSE) {
    if ($comment) {
      $regex = '/' . ($reply ? '<div class="indented">(.*?)' : '');
      $regex .= '<a id="comment-' . $comment->id() . '"(.*?)';
      $regex .= $comment->getSubject() . '(.*?)';
      $regex .= $comment->comment_body->value . '(.*?)';
      $regex .= '/s';

      return (boolean) preg_match($regex, $this->drupalGetContent());
    }
    else {
      return FALSE;
    }
  }

  /**
   * Checks whether the commenter's contact information is displayed.
   *
   * @return boolean
   *   Contact info is available.
   */
  function commentContactInfoAvailable() {
    return preg_match('/(input).*?(name="name").*?(input).*?(name="mail").*?(input).*?(name="homepage")/s', $this->drupalGetContent());
  }

  /**
   * Performs the specified operation on the specified comment.
   *
   * @param object $comment
   *   Comment to perform operation on.
   * @param string $operation
   *   Operation to perform.
   * @param boolean $aproval
   *   Operation is found on approval page.
   */
  function performCommentOperation($comment, $operation, $approval = FALSE) {
    $edit = array();
    $edit['operation'] = $operation;
    $edit['comments[' . $comment->id() . ']'] = TRUE;
    $this->drupalPostForm('admin/content/comment' . ($approval ? '/approval' : ''), $edit, t('Update'));

    if ($operation == 'delete') {
      $this->drupalPostForm(NULL, array(), t('Delete comments'));
      $this->assertRaw(format_plural(1, 'Deleted 1 comment.', 'Deleted @count comments.'), format_string('Operation "@operation" was performed on comment.', array('@operation' => $operation)));
    }
    else {
      $this->assertText(t('The update has been performed.'), format_string('Operation "@operation" was performed on comment.', array('@operation' => $operation)));
    }
  }

  /**
   * Gets the comment ID for an unapproved comment.
   *
   * @param string $subject
   *   Comment subject to find.
   *
   * @return integer
   *   Comment ID.
   */
  function getUnapprovedComment($subject) {
    $this->drupalGet('admin/content/comment/approval');
    preg_match('/href="(.*?)#comment-([^"]+)"(.*?)>(' . $subject . ')/', $this->drupalGetContent(), $match);

    return $match[2];
  }

  /**
   * Tests anonymous comment functionality.
   */
  function testCommentFunctionality() {
    $limited_user = $this->drupalCreateUser(array(
      'administer entity_test fields'
    ));
    $this->drupalLogin($limited_user);
    // Test that default field exists.
    $this->drupalGet('entity_test/structure/entity_test/fields');
    $this->assertText(t('Comment settings'));
    $this->assertLinkByHref('entity_test/structure/entity_test/fields/entity_test.entity_test.comment');
    // Test widget hidden option is not visible when there's no comments.
    $this->drupalGet('entity_test/structure/entity_test/entity-test/fields/entity_test.entity_test.comment');
    $this->assertNoField('edit-default-value-input-comment-und-0-status-0');

    $this->drupalLogin($this->admin_user);

    // Post a comment.
    $comment1 = $this->postComment($this->entity, $this->randomName(), $this->randomName());
    $this->assertTrue($this->commentExists($comment1), 'Comment on test entity exists.');

    // Assert the breadcrumb is valid.
    $this->drupalGet('comment/reply/entity_test/' . $this->entity->id() . '/comment');
    $this->assertLink($this->entity->label());

    // Unpublish the comment.
    $this->performCommentOperation($comment1, 'unpublish');
    $this->drupalGet('admin/content/comment/approval');
    $this->assertRaw('comments[' . $comment1->id() . ']', 'Comment was unpublished.');

    // Publish the comment.
    $this->performCommentOperation($comment1, 'publish', TRUE);
    $this->drupalGet('admin/content/comment');
    $this->assertRaw('comments[' . $comment1->id() . ']', 'Comment was published.');

    // Delete the comment.
    $this->performCommentOperation($comment1, 'delete');
    $this->drupalGet('admin/content/comment');
    $this->assertNoRaw('comments[' . $comment1->id() . ']', 'Comment was deleted.');

    // Post another comment.
    $comment1 = $this->postComment($this->entity, $this->randomName(), $this->randomName());
    $this->assertTrue($this->commentExists($comment1), 'Comment on test entity exists.');

    // Check that the comment was found.
    $this->drupalGet('admin/content/comment');
    $this->assertRaw('comments[' . $comment1->id() . ']', 'Comment was published.');

    // Check that entity access applies to administrative page.
    $this->assertText($this->entity->label(), 'Name of commented account found.');
    $limited_user = $this->drupalCreateUser(array(
      'administer comments',
    ));
    $this->drupalLogin($limited_user);
    $this->drupalGet('admin/content/comment');
    $this->assertNoText($this->entity->label(), 'No commented account name found.');

    $this->drupalLogout();

    // Deny anonymous users access to comments.
    user_role_change_permissions(DRUPAL_ANONYMOUS_RID, array(
      'access comments' => FALSE,
      'post comments' => FALSE,
      'skip comment approval' => FALSE,
      'view test entity' => TRUE,
    ));

    // Attempt to view comments while disallowed.
    $this->drupalGet('entity-test/' . $this->entity->id());
    $this->assertNoPattern('@<h2[^>]*>Comments</h2>@', 'Comments were not displayed.');
    $this->assertNoLink('Add new comment', 'Link to add comment was found.');

    // Attempt to view test entity comment form while disallowed.
    $this->drupalGet('comment/reply/entity_test/' . $this->entity->id() . '/comment');
    $this->assertText('You are not authorized to post comments', 'Error attempting to post comment.');
    $this->assertNoFieldByName('subject', '', 'Subject field not found.');
    $this->assertNoFieldByName('comment_body[0][value]', '', 'Comment field not found.');

    user_role_change_permissions(DRUPAL_ANONYMOUS_RID, array(
      'access comments' => TRUE,
      'post comments' => FALSE,
      'view test entity' => TRUE,
      'skip comment approval' => FALSE,
    ));
    $this->drupalGet('entity_test/' . $this->entity->id());
    $this->assertPattern('@<h2[^>]*>Comments</h2>@', 'Comments were displayed.');
    $this->assertLink('Log in', 0, 'Link to log in was found.');
    $this->assertLink('register', 0, 'Link to register was found.');
    $this->assertNoFieldByName('subject', '', 'Subject field not found.');
    $this->assertNoFieldByName('comment_body[0][value]', '', 'Comment field not found.');

    // Test the combination of anonymous users being able to post, but not view
    // comments, to ensure that access to post comments doesn't grant access to
    // view them.
    user_role_change_permissions(DRUPAL_ANONYMOUS_RID, array(
      'access comments' => FALSE,
      'post comments' => TRUE,
      'skip comment approval' => TRUE,
      'view test entity' => TRUE,
    ));
    $this->drupalGet('entity_test/' . $this->entity->id());
    $this->assertNoPattern('@<h2[^>]*>Comments</h2>@', 'Comments were not displayed.');
    $this->assertFieldByName('subject', '', 'Subject field found.');
    $this->assertFieldByName('comment_body[0][value]', '', 'Comment field found.');

    $this->drupalGet('comment/reply/entity_test/' . $this->entity->id() . '/comment/' . $comment1->id());
    $this->assertText('You are not authorized to view comments');
    $this->assertNoText($comment1->getSubject(), 'Comment not displayed.');

    // Test comment field widget changes.
    $limited_user = $this->drupalCreateUser(array(
      'administer entity_test fields',
      'view test entity',
      'administer entity_test content',
    ));
    $this->drupalLogin($limited_user);
    $this->drupalGet('entity_test/structure/entity_test/fields/entity_test.entity_test.comment');
    $this->assertNoFieldChecked('edit-default-value-input-comment-0-status-0');
    $this->assertNoFieldChecked('edit-default-value-input-comment-0-status-1');
    $this->assertFieldChecked('edit-default-value-input-comment-0-status-2');
    // Test comment option change in field settings.
    $edit = array('default_value_input[comment][0][status]' => CommentItemInterface::CLOSED);
    $this->drupalPostForm(NULL, $edit, t('Save settings'));
    $this->drupalGet('entity_test/structure/entity_test/fields/entity_test.entity_test.comment');
    $this->assertNoFieldChecked('edit-default-value-input-comment-0-status-0');
    $this->assertFieldChecked('edit-default-value-input-comment-0-status-1');
    $this->assertNoFieldChecked('edit-default-value-input-comment-0-status-2');

    // Add a new comment field.
    $this->drupalGet('entity_test/structure/entity_test/fields');
    $edit = array(
      'fields[_add_new_field][label]' => 'Foobar',
      'fields[_add_new_field][field_name]' => 'foobar',
      'fields[_add_new_field][type]' => 'comment',
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->drupalPostForm(NULL, array(), t('Save field settings'));
    $this->drupalPostForm(NULL, array(), t('Save settings'));
    $this->assertRaw(t('Saved %name configuration', array('%name' => 'Foobar')));

    // Test the new entity commenting inherits default.
    $random_label = $this->randomName();
    $data = array('bundle' => 'entity_test', 'name' => $random_label);
    $new_entity = entity_create('entity_test', $data);
    $new_entity->save();
    $this->drupalGet('entity_test/manage/' . $new_entity->id());
    $this->assertNoFieldChecked('edit-field-foobar-0-status-1');
    $this->assertFieldChecked('edit-field-foobar-0-status-2');
    $this->assertNoField('edit-field-foobar-0-status-0');

    $this->drupalGet('comment/reply/entity_test/comment/' . $new_entity->id());
    $this->assertNoFieldByName('subject', '', 'Subject field found.');
    $this->assertNoFieldByName('comment_body[0][value]', '', 'Comment field found.');
  }

}
