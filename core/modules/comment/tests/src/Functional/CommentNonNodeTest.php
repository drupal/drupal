<?php

declare(strict_types=1);

namespace Drupal\Tests\comment\Functional;

use Drupal\comment\CommentInterface;
use Drupal\comment\Entity\Comment;
use Drupal\comment\Entity\CommentType;
use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\entity_test\EntityTestHelper;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;
use Drupal\user\RoleInterface;

/**
 * Tests commenting on a test entity.
 *
 * @group comment
 */
class CommentNonNodeTest extends BrowserTestBase {

  use FieldUiTestTrait;
  use CommentTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'comment',
    'user',
    'field_ui',
    'entity_test',
    'block',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

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
  protected function setUp(): void {
    parent::setUp();
    $this->drupalPlaceBlock('system_breadcrumb_block');
    $this->drupalPlaceBlock('page_title_block');

    // Create a bundle for entity_test.
    EntityTestHelper::createBundle('entity_test', 'Entity Test', 'entity_test');
    CommentType::create([
      'id' => 'comment',
      'label' => 'Comment settings',
      'description' => 'Comment settings',
      'target_entity_type_id' => 'entity_test',
    ])->save();
    // Create comment field on entity_test bundle.
    $this->addDefaultCommentField('entity_test', 'entity_test');

    // Verify that bundles are defined correctly.
    $bundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo('comment');
    $this->assertEquals('Comment settings', $bundles['comment']['label']);

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
  public function postComment(?EntityInterface $entity, $comment, $subject = '', $contact = NULL) {
    $edit = [];
    $edit['comment_body[0][value]'] = $comment;

    $field = FieldConfig::loadByName('entity_test', 'entity_test', 'comment');
    $preview_mode = $field->getSetting('preview');

    // Must get the page before we test for fields.
    if ($entity !== NULL) {
      $this->drupalGet('comment/reply/entity_test/' . $entity->id() . '/comment');
    }

    // Determine the visibility of subject form field.
    $display_repository = $this->container->get('entity_display.repository');
    if ($display_repository->getFormDisplay('comment', 'comment')->getComponent('subject')) {
      // Subject input allowed.
      $edit['subject[0][value]'] = $subject;
    }
    else {
      $this->assertSession()->fieldValueNotEquals('subject[0][value]', '');
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

    return Comment::load($match[1]);
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
  public function commentExists(?CommentInterface $comment = NULL, $reply = FALSE) {
    if ($comment) {
      $regex = '/' . ($reply ? '<div class="indented">(.*?)' : '');
      $regex .= '<article(.*?)id="comment-' . $comment->id() . '"(.*?)';
      $regex .= $comment->getSubject() . '(.*?)';
      $regex .= $comment->comment_body->value . '(.*?)';
      $regex .= '/s';

      return (boolean) preg_match($regex, $this->getSession()->getPage()->getContent());
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
    return (bool) preg_match('/(input).*?(name="name").*?(input).*?(name="mail").*?(input).*?(name="homepage")/s', $this->getSession()->getPage()->getContent());
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
  public function performCommentOperation($comment, $operation, $approval = FALSE): void {
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
   *   Comment ID.
   */
  public function getUnapprovedComment($subject) {
    $this->drupalGet('admin/content/comment/approval');
    preg_match('/href="(.*?)#comment-([^"]+)"(.*?)>(' . $subject . ')/', $this->getSession()->getPage()->getContent(), $match);

    return $match[2];
  }

  /**
   * Tests anonymous comment functionality.
   */
  public function testCommentFunctionality(): void {
    $limited_user = $this->drupalCreateUser([
      'administer entity_test fields',
    ]);
    $this->drupalLogin($limited_user);
    // Test that default field exists.
    $this->drupalGet('entity_test/structure/entity_test/fields');
    $this->assertSession()->pageTextContains('Comments');
    $this->assertSession()->linkByHrefExists('entity_test/structure/entity_test/fields/entity_test.entity_test.comment');
    // Test widget hidden option is not visible when there's no comments.
    $this->drupalGet('entity_test/structure/entity_test/fields/entity_test.entity_test.comment');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldNotExists('edit-default-value-input-comment-und-0-status-0');
    // Test that field to change cardinality is not available.
    $this->assertSession()->fieldNotExists('cardinality_number');
    $this->assertSession()->fieldNotExists('cardinality');

    $this->drupalLogin($this->adminUser);

    // Test breadcrumb on comment add page.
    $this->drupalGet('comment/reply/entity_test/' . $this->entity->id() . '/comment');
    $this->assertSession()->elementTextEquals('xpath', '//nav[@aria-labelledby="system-breadcrumb"]/ol/li[last()]/a', $this->entity->label());

    // Post a comment.
    /** @var \Drupal\comment\CommentInterface $comment1 */
    $comment1 = $this->postComment($this->entity, $this->randomMachineName(), $this->randomMachineName());
    $this->assertTrue($this->commentExists($comment1), 'Comment on test entity exists.');

    // Test breadcrumb on comment reply page.
    $this->drupalGet('comment/reply/entity_test/' . $this->entity->id() . '/comment/' . $comment1->id());
    $this->assertSession()->elementTextEquals('xpath', '//nav[@aria-labelledby="system-breadcrumb"]/ol/li[last()]/a', $comment1->getSubject());

    // Test breadcrumb on comment edit page.
    $this->drupalGet('comment/' . $comment1->id() . '/edit');
    $this->assertSession()->elementTextEquals('xpath', '//nav[@aria-labelledby="system-breadcrumb"]/ol/li[last()]/a', $comment1->getSubject());

    // Test breadcrumb on comment delete page.
    $this->drupalGet('comment/' . $comment1->id() . '/delete');
    $this->assertSession()->elementTextEquals('xpath', '//nav[@aria-labelledby="system-breadcrumb"]/ol/li[last()]/a', $comment1->getSubject());

    // Test threading replying to comment #1 creating comment #1_2.
    $this->drupalGet('comment/reply/entity_test/' . $this->entity->id() . '/comment/' . $comment1->id());
    $comment1_2 = $this->postComment(NULL, $this->randomMachineName(), $this->randomMachineName());
    $this->assertTrue($this->commentExists($comment1_2, TRUE), 'Comment #1_2. Reply found.');
    $this->assertEquals('01.00/', $comment1_2->getThread());

    // Test nested threading replying to comment #1_2 creating comment #1_2_3.
    $this->drupalGet('comment/reply/entity_test/' . $this->entity->id() . '/comment/' . $comment1_2->id());
    $comment1_2_3 = $this->postComment(NULL, $this->randomMachineName(), $this->randomMachineName());
    $this->assertTrue($this->commentExists($comment1_2_3, TRUE), 'Comment #1_2_3. Reply found.');
    $this->assertEquals('01.00.00/', $comment1_2_3->getThread());

    // Unpublish the comment.
    $this->performCommentOperation($comment1, 'unpublish');
    $this->drupalGet('admin/content/comment/approval');
    $this->assertSession()->responseContains('comments[' . $comment1->id() . ']');

    // Publish the comment.
    $this->performCommentOperation($comment1, 'publish', TRUE);
    $this->drupalGet('admin/content/comment');
    $this->assertSession()->responseContains('comments[' . $comment1->id() . ']');

    // Delete the comment.
    $this->performCommentOperation($comment1, 'delete');
    $this->drupalGet('admin/content/comment');
    $this->assertSession()->responseNotContains('comments[' . $comment1->id() . ']');

    // Post another comment.
    $comment1 = $this->postComment($this->entity, $this->randomMachineName(), $this->randomMachineName());
    $this->assertTrue($this->commentExists($comment1), 'Comment on test entity exists.');

    // Check that the comment was found.
    $this->drupalGet('admin/content/comment');
    $this->assertSession()->responseContains('comments[' . $comment1->id() . ']');

    // Check that entity access applies to administrative page.
    $this->assertSession()->pageTextContains($this->entity->label());
    $limited_user = $this->drupalCreateUser([
      'administer comments',
    ]);
    $this->drupalLogin($limited_user);
    $this->drupalGet('admin/content/comment');
    $this->assertSession()->pageTextNotContains($this->entity->label());

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
    // Verify that comments were not displayed.
    $this->assertSession()->responseNotMatches('@<h2[^>]*>Comments</h2>@');
    $this->assertSession()->linkNotExists('Add new comment', 'Link to add comment was found.');

    // Attempt to view test entity comment form while disallowed.
    $this->drupalGet('comment/reply/entity_test/' . $this->entity->id() . '/comment');
    $this->assertSession()->statusCodeEquals(403);
    $this->assertSession()->fieldNotExists('subject[0][value]');
    $this->assertSession()->fieldNotExists('comment_body[0][value]');

    user_role_change_permissions(RoleInterface::ANONYMOUS_ID, [
      'access comments' => TRUE,
      'post comments' => FALSE,
      'view test entity' => TRUE,
      'skip comment approval' => FALSE,
    ]);
    $this->drupalGet('entity_test/' . $this->entity->id());
    // Verify that the comment field title is displayed.
    $this->assertSession()->responseMatches('@<h2[^>]*>Comments</h2>@');
    $this->assertSession()->linkExists('Log in', 0, 'Link to login was found.');
    $this->assertSession()->linkExists('register', 0, 'Link to register was found.');
    $this->assertSession()->fieldNotExists('subject[0][value]');
    $this->assertSession()->fieldNotExists('comment_body[0][value]');

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
    // Verify that comments were not displayed.
    $this->assertSession()->responseNotMatches('@<h2[^>]*>Comments</h2>@');
    $this->assertSession()->fieldValueEquals('subject[0][value]', '');
    $this->assertSession()->fieldValueEquals('comment_body[0][value]', '');

    $this->drupalGet('comment/reply/entity_test/' . $this->entity->id() . '/comment/' . $comment1->id());
    $this->assertSession()->statusCodeEquals(403);
    $this->assertSession()->pageTextNotContains($comment1->getSubject());

    // Test comment field widget changes.
    $limited_user = $this->drupalCreateUser([
      'administer entity_test fields',
      'view test entity',
      'administer entity_test content',
      'administer comments',
    ]);
    $this->drupalLogin($limited_user);
    $this->drupalGet('entity_test/structure/entity_test/fields/entity_test.entity_test.comment');
    $this->assertSession()->checkboxNotChecked('edit-default-value-input-comment-0-status-0');
    $this->assertSession()->checkboxNotChecked('edit-default-value-input-comment-0-status-1');
    $this->assertSession()->checkboxChecked('edit-default-value-input-comment-0-status-2');
    // Test comment option change in field settings.
    $edit = [
      'default_value_input[comment][0][status]' => CommentItemInterface::CLOSED,
      'settings[anonymous]' => CommentInterface::ANONYMOUS_MAY_CONTACT,
    ];
    $this->submitForm($edit, 'Save settings');
    $this->drupalGet('entity_test/structure/entity_test/fields/entity_test.entity_test.comment');
    $this->assertSession()->checkboxNotChecked('edit-default-value-input-comment-0-status-0');
    $this->assertSession()->checkboxChecked('edit-default-value-input-comment-0-status-1');
    $this->assertSession()->checkboxNotChecked('edit-default-value-input-comment-0-status-2');
    $this->assertSession()->fieldValueEquals('settings[anonymous]', CommentInterface::ANONYMOUS_MAY_CONTACT);

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
    $this->fieldUIAddNewField('entity_test/structure/entity_test', 'bar_foo', 'Bar_Foo', 'comment', $storage_edit);

    // Check the field contains the correct comment type.
    $field_storage = FieldStorageConfig::load('entity_test.field_bar_foo');
    $this->assertInstanceOf(FieldStorageConfig::class, $field_storage);
    $this->assertEquals('foobar', $field_storage->getSetting('comment_type'));
    $this->assertEquals(1, $field_storage->getCardinality());

    // Test the new entity commenting inherits default.
    $random_label = $this->randomMachineName();
    $data = ['bundle' => 'entity_test', 'name' => $random_label];
    $new_entity = EntityTest::create($data);
    $new_entity->save();
    $this->drupalGet('entity_test/manage/' . $new_entity->id() . '/edit');
    $this->assertSession()->checkboxChecked('edit-field-foobar-0-status-2');
    $this->assertSession()->checkboxNotChecked('edit-field-foobar-0-status-0');
    $this->assertSession()->fieldNotExists('edit-field-foobar-0-status-1');

    // @todo Check proper URL and form https://www.drupal.org/node/2458323
    $this->drupalGet('comment/reply/entity_test/comment/' . $new_entity->id());
    $this->assertSession()->fieldNotExists('subject[0][value]');
    $this->assertSession()->fieldNotExists('comment_body[0][value]');

    // Test removal of comment_body field.
    $limited_user = $this->drupalCreateUser([
      'administer entity_test fields',
      'post comments',
      'administer comment fields',
      'administer comment types',
      'view test entity',
    ]);
    $this->drupalLogin($limited_user);

    $this->drupalGet('comment/reply/entity_test/' . $this->entity->id() . '/comment');
    $this->assertSession()->fieldValueEquals('comment_body[0][value]', '');
    $this->fieldUIDeleteField('admin/structure/comment/manage/comment', 'comment.comment.comment_body', 'Comment', 'Comment settings', 'comment type');
    $this->drupalGet('comment/reply/entity_test/' . $this->entity->id() . '/comment');
    $this->assertSession()->fieldNotExists('comment_body[0][value]');
    // Set subject field to autogenerate it.
    $edit = ['subject[0][value]' => ''];
    $this->submitForm($edit, 'Save');
  }

  /**
   * Tests comment fields cannot be added to entity types without integer IDs.
   */
  public function testsNonIntegerIdEntities(): void {
    // Create a bundle for entity_test_string_id.
    EntityTestHelper::createBundle('entity_test', 'Entity Test', 'entity_test_string_id');
    $limited_user = $this->drupalCreateUser([
      'administer entity_test_string_id fields',
      'administer comment types',
    ]);
    $this->drupalLogin($limited_user);
    // Visit the Field UI field add page.
    $this->drupalGet('entity_test_string_id/structure/entity_test/fields/add-field');
    // Ensure field isn't shown for string IDs.
    $this->assertSession()->elementNotExists('xpath', "//a//span[text()='Comments']");
    // Ensure a core field type shown.
    $this->assertSession()->elementExists('xpath', "//a//span[text()='Boolean']");

    // Attempt to add a comment-type referencing this entity-type.
    $this->drupalGet('admin/structure/comment/types/add');
    $this->assertSession()->optionNotExists('edit-target-entity-type-id', 'entity_test_string_id');
    $this->assertSession()->responseNotContains('Test entity with string_id');

    // Create a bundle for entity_test_no_id.
    EntityTestHelper::createBundle('entity_test', 'Entity Test', 'entity_test_no_id');
    $this->drupalLogin($this->drupalCreateUser([
      'administer entity_test_no_id fields',
    ]));
    // Visit the Field UI field add page.
    $this->drupalGet('entity_test_no_id/structure/entity_test/fields/add-field');
    // Ensure field isn't shown for empty IDs.
    $this->assertSession()->elementNotExists('xpath', "//a//span[text()='Comments']");
    // Ensure a core field type shown.
    $this->assertSession()->elementExists('xpath', "//a//span[text()='Boolean']");
  }

  /**
   * Ensures that comment settings are not required.
   */
  public function testCommentSettingsNotRequired(): void {
    $limited_user = $this->drupalCreateUser([
      'administer entity_test fields',
    ]);
    $this->drupalLogin($limited_user);
    $this->drupalGet('entity_test/structure/entity_test/fields/entity_test.entity_test.comment');

    // Change the comments to be displayed as hidden by default.
    $edit = [
      'default_value_input[comment][0][status]' => CommentItemInterface::HIDDEN,
      'settings[anonymous]' => CommentInterface::ANONYMOUS_MAY_CONTACT,
    ];
    $this->submitForm($edit, 'Save settings');

    // Ensure that the comment settings field is not required and can be saved
    // with the default value.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/entity_test/add');
    $this->assertSession()->checkboxChecked('edit-comment-0-status-0');
    $edit = [
      "name[0][value]" => 'Comment test',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('entity_test 2 has been created.');
  }

}
