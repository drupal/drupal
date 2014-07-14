<?php

/**
 * @file
 * Contains \Drupal\comment\Tests\CommentTestBase.
 */

namespace Drupal\comment\Tests;

use Drupal\comment\Entity\CommentType;
use Drupal\comment\Entity\Comment;
use Drupal\comment\CommentInterface;
use Drupal\field\Entity\FieldInstanceConfig;
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
   * @var \Drupal\user\UserInterface
   */
  protected $admin_user;

  /**
   * A normal user with permission to post comments.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $web_user;

  /**
   * A test node to which comments will be posted.
   *
   * @var \Drupal\node\NodeInterface
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
      'administer comment types',
      'administer comment fields',
      'administer comment display',
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
      'skip comment approval',
      'access content',
    ));

    // Create comment field on article.
    $this->container->get('comment.manager')->addDefaultField('node', 'article');

    // Create a test node authored by the web user.
    $this->node = $this->drupalCreateNode(array('type' => 'article', 'promote' => 1, 'uid' => $this->web_user->id()));
  }

  /**
   * Posts a comment.
   *
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   Node to post comment on or NULL to post to the previously loaded page.
   * @param string $comment
   *   Comment body.
   * @param string $subject
   *   Comment subject.
   * @param string $contact
   *   Set to NULL for no contact info, TRUE to ignore success checking, and
   *   array of values to set contact info.
   * @param string $field_name
   *   (optional) Field name through which the comment should be posted.
   *   Defaults to 'comment'.
   *
   * @return \Drupal\comment\CommentInterface|null
   *   The posted comment or NULL when posted comment was not found.
   */
  public function postComment($entity, $comment, $subject = '', $contact = NULL, $field_name = 'comment') {
    $edit = array();
    $edit['comment_body[0][value]'] = $comment;

    if ($entity !== NULL) {
      $instance = FieldInstanceConfig::loadByName('node', $entity->bundle(), $field_name);
    }
    else {
      $instance = FieldInstanceConfig::loadByName('node', 'article', $field_name);
    }
    $preview_mode = $instance->settings['preview'];

    // Must get the page before we test for fields.
    if ($entity !== NULL) {
      $this->drupalGet('comment/reply/node/' . $entity->id() . '/' . $field_name);
    }

    // Determine the visibility of subject form field.
    if (entity_get_form_display('comment', 'comment', 'default')->getComponent('subject')) {
      // Subject input allowed.
      $edit['subject[0][value]'] = $subject;
    }
    else {
      $this->assertNoFieldByName('subject[0][value]', '', 'Subject field not found.');
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
      $this->assertTrue((!empty($match) && !empty($match[1])), 'Comment id found.');
    }

    if (isset($match[1])) {
      \Drupal::entityManager()->getStorage('comment')->resetCache(array($match[1]));
      return Comment::load($match[1]);
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
   * Deletes a comment.
   *
   * @param \Drupal\comment\CommentInterface $comment
   *   Comment to delete.
   */
  function deleteComment(CommentInterface $comment) {
    $this->drupalPostForm('comment/' . $comment->id() . '/delete', array(), t('Delete'));
    $this->assertText(t('The comment and all its replies have been deleted.'), 'Comment deleted.');
  }

  /**
   * Sets the value governing whether the subject field should be enabled.
   *
   * @param bool $enabled
   *   Boolean specifying whether the subject field should be enabled.
   */
  public function setCommentSubject($enabled) {
    $form_display = entity_get_form_display('comment', 'comment', 'default');
    if ($enabled) {
      $form_display->setComponent('subject', array(
        'type' => 'string',
      ));
    }
    else {
      $form_display->removeComponent('subject');
    }
    $form_display->save();
    // Display status message.
    $this->pass('Comment subject ' . ($enabled ? 'enabled' : 'disabled') . '.');
  }

  /**
   * Sets the value governing the previewing mode for the comment form.
   *
   * @param int $mode
   *   The preview mode: DRUPAL_DISABLED, DRUPAL_OPTIONAL or DRUPAL_REQUIRED.
   * @param string $field_name
   *   (optional) Field name through which the comment should be posted.
   *   Defaults to 'comment'.
   */
  public function setCommentPreview($mode, $field_name = 'comment') {
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
    $this->setCommentSettings('preview', $mode, format_string('Comment preview @mode_text.', array('@mode_text' => $mode_text)), $field_name);
  }

  /**
   * Sets the value governing whether the comment form is on its own page.
   *
   * @param bool $enabled
   *   TRUE if the comment form should be displayed on the same page as the
   *   comments; FALSE if it should be displayed on its own page.
   * @param string $field_name
   *   (optional) Field name through which the comment should be posted.
   *   Defaults to 'comment'.
   */
  public function setCommentForm($enabled, $field_name = 'comment') {
    $this->setCommentSettings('form_location', ($enabled ? COMMENT_FORM_BELOW : COMMENT_FORM_SEPARATE_PAGE), 'Comment controls ' . ($enabled ? 'enabled' : 'disabled') . '.', $field_name);
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
    $this->setCommentSettings('anonymous', $level, format_string('Anonymous commenting set to level @level.', array('@level' => $level)));
  }

  /**
   * Sets the value specifying the default number of comments per page.
   *
   * @param int $number
   *   Comments per page value.
   * @param string $field_name
   *   (optional) Field name through which the comment should be posted.
   *   Defaults to 'comment'.
   */
  public function setCommentsPerPage($number, $field_name = 'comment') {
    $this->setCommentSettings('per_page', $number, format_string('Number of comments per page set to @number.', array('@number' => $number)), $field_name);
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
   * @param string $field_name
   *   (optional) Field name through which the comment should be posted.
   *   Defaults to 'comment'.
   */
  public function setCommentSettings($name, $value, $message, $field_name = 'comment') {
    $instance = FieldInstanceConfig::loadByName('node', 'article', $field_name);
    $instance->settings[$name] = $value;
    $instance->save();
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
   * @param \Drupal\comment\CommentInterface $comment
   *   Comment to perform operation on.
   * @param string $operation
   *   Operation to perform.
   * @param boolean $aproval
   *   Operation is found on approval page.
   */
  function performCommentOperation(CommentInterface $comment, $operation, $approval = FALSE) {
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
   *   Comment id.
   */
  function getUnapprovedComment($subject) {
    $this->drupalGet('admin/content/comment/approval');
    preg_match('/href="(.*?)#comment-([^"]+)"(.*?)>(' . $subject . ')/', $this->drupalGetContent(), $match);

    return $match[2];
  }

  /**
   * Creates a comment comment type (bundle).
   *
   * @param string $label
   *   The comment type label.
   *
   * @return \Drupal\comment\Entity\CommentType
   *   Created comment type.
   */
  protected function createCommentType($label) {
    $bundle = CommentType::create(array(
      'id' => $label,
      'label' => $label,
      'description' => '',
      'target_entity_type_id' => 'node',
    ));
    $bundle->save();
    return $bundle;
  }

}
