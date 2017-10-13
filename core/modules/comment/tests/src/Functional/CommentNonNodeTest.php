<?php

namespace Drupal\Tests\comment\Functional;

use Drupal\comment\CommentInterface;
use Drupal\comment\Entity\Comment;
use Drupal\comment\Entity\CommentType;
use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field_ui\Tests\FieldUiTestTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\user\RoleInterface;

/**
 * Tests commenting on a test entity.
 *
 * @group comment
 */
class CommentNonNodeTest extends BrowserTestBase {

  use FieldUiTestTrait;
  use CommentTestTrait;

  public static $modules = ['comment', 'user', 'field_ui', 'entity_test', 'block'];

  /**
   * An administrative user with permission to configure comment settings.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * The entity to use within tests.
   *
   * @var \Drupal\entity_test\Entity\EntityTest
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('system_breadcrumb_block');
    $this->drupalPlaceBlock('page_title_block');

    // Create a bundle for entity_test.
    entity_test_create_bundle('entity_test', 'Entity Test', 'entity_test');
    CommentType::create([
      'id' => 'comment',
      'label' => 'Comment settings',
      'description' => 'Comment settings',
      'target_entity_type_id' => 'entity_test',
    ])->save();
    // Create comment field on entity_test bundle.
    $this->addDefaultCommentField('entity_test', 'entity_test');

    // Verify that bundles are defined correctly.
    $bundles = \Drupal::entityManager()->getBundleInfo('comment');
    $this->assertEqual($bundles['comment']['label'], 'Comment settings');

    // Create test user.
    $this->adminUser = $this->drupalCreateUser([
      'administer comments',
      'skip comment approval',
      'post comments',
      'access comments',
      'view test entity',
      'administer entity_test content',
    ]);

    // Enable anonymous and authenticated user comments.
    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, [
      'access comments',
      'post comments',
      'skip comment approval',
    ]);
    user_role_grant_permissions(RoleInterface::AUTHENTICATED_ID, [
      'access comments',
      'post comments',
      'skip comment approval',
    ]);

    // Create a test entity.
    $random_label = $this->randomMachineName();
    $data = ['type' => 'entity_test', 'name' => $random_label];
    $this->entity = EntityTest::create($data);
    $this->entity->save();
  }

  /**
   * Posts a comment.
   *
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   Entity to post comment on or NULL to post to the previously loaded page.
   * @param string $comment
   *   Comment body.
   * @param string $subject
   *   Comment subject.
   * @param mixed $contact
   *   Set to NULL for no contact info, TRUE to ignore success checking, and
   *   array of values to set contact info.
   *
   * @return \Drupal\comment\CommentInterface
   *   The new comment entity.
   */
  public function postComment(EntityInterface $entity, $comment, $subject = '', $contact = NULL) {
    $edit = [];
    $edit['comment_body[0][value]'] = $comment;

    $field = FieldConfig::loadByName('entity_test', 'entity_test', 'comment');
    $preview_mode = $field->getSetting('preview');

    // Must get the page before we test for fields.
    if ($entity !== NULL) {
      $this->drupalGet('comment/reply/entity_test/' . $entity->id() . '/comment');
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
    $match = [];
    // Get comment ID
    preg_match('/#comment-([0-9]+)/', $this->getURL(), $match);

    // Get comment.
    if ($contact !== TRUE) {
      // If true then attempting to find error message.
      if ($subject) {
        $this->assertText($subject, 'Comment subject posted.');
      }
      $this->assertText($comment, 'Comment body posted.');
      $this->assertTrue((!empty($match) && !empty($match[1])), 'Comment ID found.');
    }

    if (isset($match[1])) {
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
      $regex = '/' . ($reply ? '<div class="indented">(.*?)' : '');
      $regex .= '<a id="comment-' . $comment->id() . '"(.*?)';
      $regex .= $comment->getSubject() . '(.*?)';
      $regex .= $comment->comment_body->value . '(.*?)';
      $regex .= '/s';

      return (boolean) preg_match($regex, $this->getRawContent());
    }
    else {
      return FALSE;
    }
  }

  /**
   * Checks whether the commenter's contact information is displayed.
   *
   * @return bool
   *   Contact info is available.
   */
  public function commentContactInfoAvailable() {
    return preg_match('/(input).*?(name="name").*?(input).*?(name="mail").*?(input).*?(name="homepage")/s', $this->getRawContent());
  }

  /**
   * Performs the specified operation on the specified comment.
   *
   * @param object $comment
   *   Comment to perform operation on.
   * @param string $operation
   *   Operation to perform.
   * @param bool $approval
   *   Operation is found on approval page.
   */
  public function performCommentOperation($comment, $operation, $approval = FALSE) {
    $edit = [];
    $edit['operation'] = $operation;
    $edit['comments[' . $comment->id() . ']'] = TRUE;
    $this->drupalPostForm('admin/content/comment' . ($approval ? '/approval' : ''), $edit, t('Update'));

    if ($operation == 'delete') {
      $this->drupalPostForm(NULL, [], t('Delete'));
      $this->assertRaw(\Drupal::translation()->formatPlural(1, 'Deleted 1 comment.', 'Deleted @count comments.'), format_string('Operation "@operation" was performed on comment.', ['@operation' => $operation]));
    }
    else {
      $this->assertText(t('The update has been performed.'), format_string('Operation "@operation" was performed on comment.', ['@operation' => $operation]));
    }
  }

  /**
   * Gets the comment ID for an unapproved comment.
   *
   * @param string $subject
   *   Comment subject to find.
   *
   * @return int
   *   Comment ID.
   */
  public function getUnapprovedComment($subject) {
    $this->drupalGet('admin/content/comment/approval');
    preg_match('/href="(.*?)#comment-([^"]+)"(.*?)>(' . $subject . ')/', $this->getRawContent(), $match);

    return $match[2];
  }

  /**
   * Tests anonymous comment functionality.
   */
  public function testCommentFunctionality() {
    $limited_user = $this->drupalCreateUser([
      'administer entity_test fields'
    ]);
    $this->drupalLogin($limited_user);
    // Test that default field exists.
    $this->drupalGet('entity_test/structure/entity_test/fields');
    $this->assertText(t('Comments'));
    $this->assertLinkByHref('entity_test/structure/entity_test/fields/entity_test.entity_test.comment');
    // Test widget hidden option is not visible when there's no comments.
    $this->drupalGet('entity_test/structure/entity_test/fields/entity_test.entity_test.comment');
    $this->assertResponse(200);
    $this->assertNoField('edit-default-value-input-comment-und-0-status-0');
    // Test that field to change cardinality is not available.
    $this->drupalGet('entity_test/structure/entity_test/fields/entity_test.entity_test.comment/storage');
    $this->assertResponse(200);
    $this->assertNoField('cardinality_number');
    $this->assertNoField('cardinality');

    $this->drupalLogin($this->adminUser);

    // Test breadcrumb on comment add page.
    $this->drupalGet('comment/reply/entity_test/' . $this->entity->id() . '/comment');
    $xpath = '//nav[@class="breadcrumb"]/ol/li[last()]/a';
    $this->assertEqual(current($this->xpath($xpath))->getText(), $this->entity->label(), 'Last breadcrumb item is equal to node title on comment reply page.');

    // Post a comment.
    /** @var \Drupal\comment\CommentInterface $comment1 */
    $comment1 = $this->postComment($this->entity, $this->randomMachineName(), $this->randomMachineName());
    $this->assertTrue($this->commentExists($comment1), 'Comment on test entity exists.');

    // Test breadcrumb on comment reply page.
    $this->drupalGet('comment/reply/entity_test/' . $this->entity->id() . '/comment/' . $comment1->id());
    $xpath = '//nav[@class="breadcrumb"]/ol/li[last()]/a';
    $this->assertEqual(current($this->xpath($xpath))->getText(), $comment1->getSubject(), 'Last breadcrumb item is equal to comment title on comment reply page.');

    // Test breadcrumb on comment edit page.
    $this->drupalGet('comment/' . $comment1->id() . '/edit');
    $xpath = '//nav[@class="breadcrumb"]/ol/li[last()]/a';
    $this->assertEqual(current($this->xpath($xpath))->getText(), $comment1->getSubject(), 'Last breadcrumb item is equal to comment subject on edit page.');

    // Test breadcrumb on comment delete page.
    $this->drupalGet('comment/' . $comment1->id() . '/delete');
    $xpath = '//nav[@class="breadcrumb"]/ol/li[last()]/a';
    $this->assertEqual(current($this->xpath($xpath))->getText(), $comment1->getSubject(), 'Last breadcrumb item is equal to comment subject on delete confirm page.');

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
    $comment1 = $this->postComment($this->entity, $this->randomMachineName(), $this->randomMachineName());
    $this->assertTrue($this->commentExists($comment1), 'Comment on test entity exists.');

    // Check that the comment was found.
    $this->drupalGet('admin/content/comment');
    $this->assertRaw('comments[' . $comment1->id() . ']', 'Comment was published.');

    // Check that entity access applies to administrative page.
    $this->assertText($this->entity->label(), 'Name of commented account found.');
    $limited_user = $this->drupalCreateUser([
      'administer comments',
    ]);
    $this->drupalLogin($limited_user);
    $this->drupalGet('admin/content/comment');
    $this->assertNoText($this->entity->label(), 'No commented account name found.');

    $this->drupalLogout();

    // Deny anonymous users access to comments.
    user_role_change_permissions(RoleInterface::ANONYMOUS_ID, [
      'access comments' => FALSE,
      'post comments' => FALSE,
      'skip comment approval' => FALSE,
      'view test entity' => TRUE,
    ]);

    // Attempt to view comments while disallowed.
    $this->drupalGet('entity-test/' . $this->entity->id());
    $this->assertNoPattern('@<h2[^>]*>Comments</h2>@', 'Comments were not displayed.');
    $this->assertNoLink('Add new comment', 'Link to add comment was found.');

    // Attempt to view test entity comment form while disallowed.
    $this->drupalGet('comment/reply/entity_test/' . $this->entity->id() . '/comment');
    $this->assertResponse(403);
    $this->assertNoFieldByName('subject[0][value]', '', 'Subject field not found.');
    $this->assertNoFieldByName('comment_body[0][value]', '', 'Comment field not found.');

    user_role_change_permissions(RoleInterface::ANONYMOUS_ID, [
      'access comments' => TRUE,
      'post comments' => FALSE,
      'view test entity' => TRUE,
      'skip comment approval' => FALSE,
    ]);
    $this->drupalGet('entity_test/' . $this->entity->id());
    $this->assertPattern('@<h2[^>]*>Comments</h2>@', 'Comments were displayed.');
    $this->assertLink('Log in', 0, 'Link to login was found.');
    $this->assertLink('register', 0, 'Link to register was found.');
    $this->assertNoFieldByName('subject[0][value]', '', 'Subject field not found.');
    $this->assertNoFieldByName('comment_body[0][value]', '', 'Comment field not found.');

    // Test the combination of anonymous users being able to post, but not view
    // comments, to ensure that access to post comments doesn't grant access to
    // view them.
    user_role_change_permissions(RoleInterface::ANONYMOUS_ID, [
      'access comments' => FALSE,
      'post comments' => TRUE,
      'skip comment approval' => TRUE,
      'view test entity' => TRUE,
    ]);
    $this->drupalGet('entity_test/' . $this->entity->id());
    $this->assertNoPattern('@<h2[^>]*>Comments</h2>@', 'Comments were not displayed.');
    $this->assertFieldByName('subject[0][value]', '', 'Subject field found.');
    $this->assertFieldByName('comment_body[0][value]', '', 'Comment field found.');

    $this->drupalGet('comment/reply/entity_test/' . $this->entity->id() . '/comment/' . $comment1->id());
    $this->assertResponse(403);
    $this->assertNoText($comment1->getSubject(), 'Comment not displayed.');

    // Test comment field widget changes.
    $limited_user = $this->drupalCreateUser([
      'administer entity_test fields',
      'view test entity',
      'administer entity_test content',
      'administer comments',
    ]);
    $this->drupalLogin($limited_user);
    $this->drupalGet('entity_test/structure/entity_test/fields/entity_test.entity_test.comment');
    $this->assertNoFieldChecked('edit-default-value-input-comment-0-status-0');
    $this->assertNoFieldChecked('edit-default-value-input-comment-0-status-1');
    $this->assertFieldChecked('edit-default-value-input-comment-0-status-2');
    // Test comment option change in field settings.
    $edit = [
      'default_value_input[comment][0][status]' => CommentItemInterface::CLOSED,
      'settings[anonymous]' => COMMENT_ANONYMOUS_MAY_CONTACT,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save settings'));
    $this->drupalGet('entity_test/structure/entity_test/fields/entity_test.entity_test.comment');
    $this->assertNoFieldChecked('edit-default-value-input-comment-0-status-0');
    $this->assertFieldChecked('edit-default-value-input-comment-0-status-1');
    $this->assertNoFieldChecked('edit-default-value-input-comment-0-status-2');
    $this->assertFieldByName('settings[anonymous]', COMMENT_ANONYMOUS_MAY_CONTACT);

    // Add a new comment-type.
    $bundle = CommentType::create([
      'id' => 'foobar',
      'label' => 'Foobar',
      'description' => '',
      'target_entity_type_id' => 'entity_test',
    ]);
    $bundle->save();

    // Add a new comment field.
    $storage_edit = [
      'settings[comment_type]' => 'foobar',
    ];
    $this->fieldUIAddNewField('entity_test/structure/entity_test', 'foobar', 'Foobar', 'comment', $storage_edit);

    // Add a third comment field.
    $this->fieldUIAddNewField('entity_test/structure/entity_test', 'barfoo', 'BarFoo', 'comment', $storage_edit);

    // Check the field contains the correct comment type.
    $field_storage = FieldStorageConfig::load('entity_test.field_barfoo');
    $this->assertTrue($field_storage);
    $this->assertEqual($field_storage->getSetting('comment_type'), 'foobar');
    $this->assertEqual($field_storage->getCardinality(), 1);

    // Test the new entity commenting inherits default.
    $random_label = $this->randomMachineName();
    $data = ['bundle' => 'entity_test', 'name' => $random_label];
    $new_entity = EntityTest::create($data);
    $new_entity->save();
    $this->drupalGet('entity_test/manage/' . $new_entity->id() . '/edit');
    $this->assertNoFieldChecked('edit-field-foobar-0-status-1');
    $this->assertFieldChecked('edit-field-foobar-0-status-2');
    $this->assertNoField('edit-field-foobar-0-status-0');

    // @todo Check proper url and form https://www.drupal.org/node/2458323
    $this->drupalGet('comment/reply/entity_test/comment/' . $new_entity->id());
    $this->assertNoFieldByName('subject[0][value]', '', 'Subject field found.');
    $this->assertNoFieldByName('comment_body[0][value]', '', 'Comment field found.');

    // Test removal of comment_body field.
    $limited_user = $this->drupalCreateUser([
      'administer entity_test fields',
      'post comments',
      'administer comment fields',
      'administer comment types',
    ]);
    $this->drupalLogin($limited_user);

    $this->drupalGet('comment/reply/entity_test/' . $this->entity->id() . '/comment');
    $this->assertFieldByName('comment_body[0][value]', '', 'Comment body field found.');
    $this->fieldUIDeleteField('admin/structure/comment/manage/comment', 'comment.comment.comment_body', 'Comment', 'Comment settings');
    $this->drupalGet('comment/reply/entity_test/' . $this->entity->id() . '/comment');
    $this->assertNoFieldByName('comment_body[0][value]', '', 'Comment body field not found.');
    // Set subject field to autogenerate it.
    $edit = ['subject[0][value]' => ''];
    $this->drupalPostForm(NULL, $edit, t('Save'));
  }

  /**
   * Tests comment fields cannot be added to entity types without integer IDs.
   */
  public function testsNonIntegerIdEntities() {
    // Create a bundle for entity_test_string_id.
    entity_test_create_bundle('entity_test', 'Entity Test', 'entity_test_string_id');
    $limited_user = $this->drupalCreateUser([
      'administer entity_test_string_id fields',
    ]);
    $this->drupalLogin($limited_user);
    // Visit the Field UI field add page.
    $this->drupalGet('entity_test_string_id/structure/entity_test/fields/add-field');
    // Ensure field isn't shown for string IDs.
    $this->assertNoOption('edit-new-storage-type', 'comment');
    // Ensure a core field type shown.
    $this->assertOption('edit-new-storage-type', 'boolean');

    // Create a bundle for entity_test_no_id.
    entity_test_create_bundle('entity_test', 'Entity Test', 'entity_test_no_id');
    $this->drupalLogin($this->drupalCreateUser([
      'administer entity_test_no_id fields',
    ]));
    // Visit the Field UI field add page.
    $this->drupalGet('entity_test_no_id/structure/entity_test/fields/add-field');
    // Ensure field isn't shown for empty IDs.
    $this->assertNoOption('edit-new-storage-type', 'comment');
    // Ensure a core field type shown.
    $this->assertOption('edit-new-storage-type', 'boolean');
  }

}
