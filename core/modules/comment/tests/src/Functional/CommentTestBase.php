<?php

namespace Drupal\Tests\comment\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\comment\Entity\CommentType;
use Drupal\comment\Entity\Comment;
use Drupal\comment\CommentInterface;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\field\Entity\FieldConfig;
use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;

/**
 * Provides setup and helper methods for comment tests.
 */
abstract class CommentTestBase extends BrowserTestBase {

  use CommentTestTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = [
    'block',
    'comment',
    'node',
    'history',
    'field_ui',
    'datetime',
  ];

  /**
   * An administrative user with permission to configure comment settings.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * A normal user with permission to post comments.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * A test node to which comments will be posted.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create an article content type only if it does not yet exist, so that
    // child classes may specify the standard profile.
    $types = NodeType::loadMultiple();
    if (empty($types['article'])) {
      $this->drupalCreateContentType(['type' => 'article', 'name' => t('Article')]);
    }

    // Create two test users.
    $this->adminUser = $this->drupalCreateUser([
      'administer content types',
      'administer comments',
      'administer comment types',
      'administer comment fields',
      'administer comment display',
      'skip comment approval',
      'post comments',
      'access comments',
      // Usernames aren't shown in comment edit form autocomplete unless this
      // permission is granted.
      'access user profiles',
      'access content',
    ]);
    $this->webUser = $this->drupalCreateUser([
      'access comments',
      'post comments',
      'create article content',
      'edit own comments',
      'skip comment approval',
      'access content',
    ]);

    // Create comment field on article.
    $this->addDefaultCommentField('node', 'article');

    // Create a test node authored by the web user.
    $this->node = $this->drupalCreateNode(['type' => 'article', 'promote' => 1, 'uid' => $this->webUser->id()]);
    $this->drupalPlaceBlock('local_tasks_block');
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
    $edit = [];
    $edit['comment_body[0][value]'] = $comment;

    if ($entity !== NULL) {
      $field = FieldConfig::loadByName($entity->getEntityTypeId(), $entity->bundle(), $field_name);
    }
    else {
      $field = FieldConfig::loadByName('node', 'article', $field_name);
    }
    $preview_mode = $field->getSetting('preview');

    // Must get the page before we test for fields.
    if ($entity !== NULL) {
      $this->drupalGet('comment/reply/' . $entity->getEntityTypeId() . '/' . $entity->id() . '/' . $field_name);
    }

    // Determine the visibility of subject form field.
    $display_repository = $this->container->get('entity_display.repository');
    if ($display_repository->getFormDisplay('comment', 'comment')->getComponent('subject')) {
      // Subject input allowed.
      $edit['subject[0][value]'] = $subject;
    }
    else {
      $this->assertSession()->fieldNotExists('subject[0][value]');
    }

    if ($contact !== NULL && is_array($contact)) {
      $edit += $contact;
    }
    switch ($preview_mode) {
      case DRUPAL_REQUIRED:
        // Preview required so no save button should be found.
        $this->assertSession()->buttonNotExists('Save');
        $this->submitForm($edit, 'Preview');
        // Don't break here so that we can test post-preview field presence and
        // function below.
      case DRUPAL_OPTIONAL:
        $this->assertSession()->buttonExists('Preview');
        $this->assertSession()->buttonExists('Save');
        $this->submitForm($edit, 'Save');
        break;

      case DRUPAL_DISABLED:
        $this->assertSession()->buttonNotExists('Preview');
        $this->assertSession()->buttonExists('Save');
        $this->submitForm($edit, 'Save');
        break;
    }
    $match = [];
    // Get comment ID
    preg_match('/#comment-([0-9]+)/', $this->getURL(), $match);

    // Get comment.
    if ($contact !== TRUE) {
      // If true then attempting to find error message.
      if ($subject) {
        $this->assertSession()->pageTextContains($subject);
      }
      $this->assertSession()->pageTextContains($comment);
      // Check the comment ID was extracted.
      $this->assertArrayHasKey(1, $match);
    }

    if (isset($match[1])) {
      \Drupal::entityTypeManager()->getStorage('comment')->resetCache([$match[1]]);
      return Comment::load($match[1]);
    }
  }

  /**
   * Checks current page for specified comment.
   *
   * @param \Drupal\comment\CommentInterface $comment
   *   The comment object.
   * @param bool $reply
   *   Boolean indicating whether the comment is a reply to another comment.
   *
   * @return bool
   *   Boolean indicating whether the comment was found.
   */
  public function commentExists(CommentInterface $comment = NULL, $reply = FALSE) {
    if ($comment) {
      $comment_element = $this->cssSelect(($reply ? '.indented ' : '') . 'article#comment-' . $comment->id());
      if (empty($comment_element)) {
        return FALSE;
      }

      $comment_title = $comment_element[0]->find('xpath', 'div/h3/a');
      if (empty($comment_title) || $comment_title->getText() !== $comment->getSubject()) {
        return FALSE;
      }

      $comment_body = $comment_element[0]->find('xpath', 'div/div/p');
      if (empty($comment_body) || $comment_body->getText() !== $comment->comment_body->value) {
        return FALSE;
      }

      return TRUE;
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
  public function deleteComment(CommentInterface $comment) {
    $this->drupalGet('comment/' . $comment->id() . '/delete');
    $this->submitForm([], 'Delete');
    $this->assertSession()->pageTextContains('The comment and all its replies have been deleted.');
  }

  /**
   * Sets the value governing whether the subject field should be enabled.
   *
   * @param bool $enabled
   *   Boolean specifying whether the subject field should be enabled.
   */
  public function setCommentSubject($enabled) {
    $form_display = $this->container->get('entity_display.repository')
      ->getFormDisplay('comment', 'comment');

    if ($enabled) {
      $form_display->setComponent('subject', [
        'type' => 'string_textfield',
      ]);
    }
    else {
      $form_display->removeComponent('subject');
    }
    $form_display->save();
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
    $this->setCommentSettings('preview', $mode, new FormattableMarkup('Comment preview @mode_text.', ['@mode_text' => $mode_text]), $field_name);
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
    $this->setCommentSettings('form_location', ($enabled ? CommentItemInterface::FORM_BELOW : CommentItemInterface::FORM_SEPARATE_PAGE), 'Comment controls ' . ($enabled ? 'enabled' : 'disabled') . '.', $field_name);
  }

  /**
   * Sets the value governing restrictions on anonymous comments.
   *
   * @param int $level
   *   The level of the contact information allowed for anonymous comments:
   *   - 0: No contact information allowed.
   *   - 1: Contact information allowed but not required.
   *   - 2: Contact information required.
   */
  public function setCommentAnonymous($level) {
    $this->setCommentSettings('anonymous', $level, new FormattableMarkup('Anonymous commenting set to level @level.', ['@level' => $level]));
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
    $this->setCommentSettings('per_page', $number, new FormattableMarkup('Number of comments per page set to @number.', ['@number' => $number]), $field_name);
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
    $field = FieldConfig::loadByName('node', 'article', $field_name);
    $field->setSetting($name, $value);
    $field->save();
  }

  /**
   * Checks whether the commenter's contact information is displayed.
   *
   * @return bool
   *   Contact info is available.
   */
  public function commentContactInfoAvailable() {
    return (bool) preg_match('/(input).*?(name="name").*?(input).*?(name="mail").*?(input).*?(name="homepage")/s', $this->getSession()->getPage()->getContent());
  }

  /**
   * Performs the specified operation on the specified comment.
   *
   * @param \Drupal\comment\CommentInterface $comment
   *   Comment to perform operation on.
   * @param string $operation
   *   Operation to perform.
   * @param bool $approval
   *   Operation is found on approval page.
   */
  public function performCommentOperation(CommentInterface $comment, $operation, $approval = FALSE) {
    $edit = [];
    $edit['operation'] = $operation;
    $edit['comments[' . $comment->id() . ']'] = TRUE;
    $this->drupalGet('admin/content/comment' . ($approval ? '/approval' : ''));
    $this->submitForm($edit, 'Update');

    if ($operation == 'delete') {
      $this->submitForm([], 'Delete');
      $this->assertSession()->pageTextContains('Deleted 1 comment.');
    }
    else {
      $this->assertSession()->pageTextContains('The update has been performed.');
    }
  }

  /**
   * Gets the comment ID for an unapproved comment.
   *
   * @param string $subject
   *   Comment subject to find.
   *
   * @return int
   *   Comment id.
   */
  public function getUnapprovedComment($subject) {
    $this->drupalGet('admin/content/comment/approval');
    preg_match('/href="(.*?)#comment-([^"]+)"(.*?)>(' . $subject . ')/', $this->getSession()->getPage()->getContent(), $match);

    return $match[2];
  }

  /**
   * Creates a comment type (bundle).
   *
   * @param string $label
   *   The comment type label.
   *
   * @return \Drupal\comment\Entity\CommentType
   *   Created comment type.
   */
  protected function createCommentType($label) {
    $bundle = CommentType::create([
      'id' => $label,
      'label' => $label,
      'description' => '',
      'target_entity_type_id' => 'node',
    ]);
    $bundle->save();
    return $bundle;
  }

}
