<?php

/**
 * @file
 * Contains Drupal\comment\Tests\CommentTestBase.
 */

namespace Drupal\comment\Tests;

use Drupal\Core\Language\Language;
use Drupal\comment\Plugin\Core\Entity\Comment;
use Drupal\simpletest\WebTestBase;

/**
 * Provides setup and helper methods for comment tests.
 */
abstract class CommentTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('comment', 'node', 'history', 'field_ui', 'datetime');

  /**
   * An administrative user with permission to configure comment settings.
   *
   * @var Drupal\user\User
   */
  protected $admin_user;

  /**
   * A normal user with permission to post comments.
   *
   * @var Drupal\user\User
   */
  protected $web_user;

  /**
   * A test node to which comments will be posted.
   *
   * @var Drupal\node\Node
   */
  protected $node;

  function setUp() {
    parent::setUp();

    // Create an article content type only if it does not yet exist, so that
    // child classes may specify the standard profile.
    $types = node_type_get_types();
    if (empty($types['article'])) {
      $this->drupalCreateContentType(array('type' => 'article', 'name' => t('Article')));
    }

    // Create two test users.
    $this->admin_user = $this->drupalCreateUser(array(
      'administer content types',
      'administer comments',
      'administer comment fields',
      'skip comment approval',
      'post comments',
      'access comments',
      'access content',
     ));
    $this->web_user = $this->drupalCreateUser(array(
      'access comments',
      'post comments',
      'create article content',
      'edit own comments',
      'post comments',
      'skip comment approval',
      'access content',
    ));

    // Create a test node authored by the web user.
    $this->node = $this->drupalCreateNode(array('type' => 'article', 'promote' => 1, 'uid' => $this->web_user->id()));
  }

  /**
   * Posts a comment.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node|null $node
   *   Node to post comment on or NULL to post to the previusly loaded page.
   * @param $comment
   *   Comment body.
   * @param $subject
   *   Comment subject.
   * @param $contact
   *   Set to NULL for no contact info, TRUE to ignore success checking, and
   *   array of values to set contact info.
   */
  function postComment($node, $comment, $subject = '', $contact = NULL) {
    $langcode = Language::LANGCODE_NOT_SPECIFIED;
    $edit = array();
    $edit['comment_body[' . $langcode . '][0][value]'] = $comment;

    $preview_mode = variable_get('comment_preview_article', DRUPAL_OPTIONAL);
    $subject_mode = variable_get('comment_subject_field_article', 1);

    // Must get the page before we test for fields.
    if ($node !== NULL) {
      $this->drupalGet('comment/reply/' . $node->nid);
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
        $this->drupalPost(NULL, $edit, t('Preview'));
        // Don't break here so that we can test post-preview field presence and
        // function below.
      case DRUPAL_OPTIONAL:
        $this->assertFieldByName('op', t('Preview'), 'Preview button found.');
        $this->assertFieldByName('op', t('Save'), 'Save button found.');
        $this->drupalPost(NULL, $edit, t('Save'));
        break;

      case DRUPAL_DISABLED:
        $this->assertNoFieldByName('op', t('Preview'), 'Preview button not found.');
        $this->assertFieldByName('op', t('Save'), 'Save button found.');
        $this->drupalPost(NULL, $edit, t('Save'));
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
      $this->assertTrue((!empty($match) && !empty($match[1])), 'Comment id found.');
    }

    if (isset($match[1])) {
      $entity = comment_load($match[1], TRUE);
      return $entity;
    }
  }

  /**
   * Checks current page for specified comment.
   *
   * @param Drupal\comment\Comment $comment
   *   The comment object.
   * @param boolean $reply
   *   Boolean indicating whether the comment is a reply to another comment.
   *
   * @return boolean
   *   Boolean indicating whether the comment was found.
   */
  function commentExists(Comment $comment = NULL, $reply = FALSE) {
    if ($comment) {
      $regex = '/' . ($reply ? '<div class="indented">(.*?)' : '');
      $regex .= '<a id="comment-' . $comment->id() . '"(.*?)'; // Comment anchor.
      $regex .= $comment->subject->value . '(.*?)'; // Match subject.
      $regex .= $comment->comment_body->value . '(.*?)'; // Match comment.
      $regex .= '/s';

      return (boolean)preg_match($regex, $this->drupalGetContent());
    }
    else {
      return FALSE;
    }
  }

  /**
   * Deletes a comment.
   *
   * @param Drupal\comment\Comment $comment
   *   Comment to delete.
   */
  function deleteComment(Comment $comment) {
    $this->drupalPost('comment/' . $comment->id() . '/delete', array(), t('Delete'));
    $this->assertText(t('The comment and all its replies have been deleted.'), 'Comment deleted.');
  }

  /**
   * Sets the value governing whether the subject field should be enabled.
   *
   * @param boolean $enabled
   *   Boolean specifying whether the subject field should be enabled.
   */
  function setCommentSubject($enabled) {
    $this->setCommentSettings('comment_subject_field', ($enabled ? '1' : '0'), 'Comment subject ' . ($enabled ? 'enabled' : 'disabled') . '.');
  }

  /**
   * Sets the value governing the previewing mode for the comment form.
   *
   * @param int $mode
   *   The preview mode: DRUPAL_DISABLED, DRUPAL_OPTIONAL or DRUPAL_REQUIRED.
   */
  function setCommentPreview($mode) {
    switch ($mode) {
      case DRUPAL_DISABLED:
        $mode_text = 'disabled';
        break;

      case DRUPAL_OPTIONAL:
        $mode_text = 'optional';
        break;

      case DRUPAL_REQUIRED:
        $mode_text = 'required';
        break;
    }
    $this->setCommentSettings('comment_preview', $mode, format_string('Comment preview @mode_text.', array('@mode_text' => $mode_text)));
  }

  /**
   * Sets the value governing whether the comment form is on its own page.
   *
   * @param boolean $enabled
   *   TRUE if the comment form should be displayed on the same page as the
   *   comments; FALSE if it should be displayed on its own page.
   */
  function setCommentForm($enabled) {
    $this->setCommentSettings('comment_form_location', ($enabled ? COMMENT_FORM_BELOW : COMMENT_FORM_SEPARATE_PAGE), 'Comment controls ' . ($enabled ? 'enabled' : 'disabled') . '.');
  }

  /**
   * Sets the value governing restrictions on anonymous comments.
   *
   * @param integer $level
   *   The level of the contact information allowed for anonymous comments:
   *   - 0: No contact information allowed.
   *   - 1: Contact information allowed but not required.
   *   - 2: Contact information required.
   */
  function setCommentAnonymous($level) {
    $this->setCommentSettings('comment_anonymous', $level, format_string('Anonymous commenting set to level @level.', array('@level' => $level)));
  }

  /**
   * Sets the value specifying the default number of comments per page.
   *
   * @param integer $comments
   *   Comments per page value.
   */
  function setCommentsPerPage($number) {
    $this->setCommentSettings('comment_default_per_page', $number, format_string('Number of comments per page set to @number.', array('@number' => $number)));
  }

  /**
   * Sets a comment settings variable for the article content type.
   *
   * @param string $name
   *   Name of variable.
   * @param string $value
   *   Value of variable.
   * @param string $message
   *   Status message to display.
   */
  function setCommentSettings($name, $value, $message) {
    variable_set($name . '_article', $value);
    // Display status message.
    $this->pass($message);
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
    $this->drupalPost('admin/content/comment' . ($approval ? '/approval' : ''), $edit, t('Update'));

    if ($operation == 'delete') {
      $this->drupalPost(NULL, array(), t('Delete comments'));
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
   *   Comment id.
   */
  function getUnapprovedComment($subject) {
    $this->drupalGet('admin/content/comment/approval');
    preg_match('/href="(.*?)#comment-([^"]+)"(.*?)>(' . $subject . ')/', $this->drupalGetContent(), $match);

    return $match[2];
  }

}
