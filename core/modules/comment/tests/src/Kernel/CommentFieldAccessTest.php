<?php

namespace Drupal\Tests\comment\Kernel;

use Drupal\comment\Entity\Comment;
use Drupal\comment\Entity\CommentType;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\simpletest\TestBase;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Tests comment field level access.
 *
 * @group comment
 * @group Access
 */
class CommentFieldAccessTest extends EntityKernelTestBase {

  use CommentTestTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('comment', 'entity_test', 'user');

  /**
   * Fields that only users with administer comments permissions can change.
   *
   * @var array
   */
  protected $administrativeFields = array(
    'uid',
    'status',
    'created',
  );

  /**
   * These fields are automatically managed and can not be changed by any user.
   *
   * @var array
   */
  protected $readOnlyFields = array(
    'changed',
    'hostname',
    'cid',
    'thread',
  );

  /**
   * These fields can be edited on create only.
   *
   * @var array
   */
  protected $createOnlyFields = [
    'uuid',
    'pid',
    'comment_type',
    'entity_id',
    'entity_type',
    'field_name',
  ];

  /**
   * These fields can only be edited by the admin or anonymous users if allowed.
   *
   * @var array
   */
  protected $contactFields = array(
    'name',
    'mail',
    'homepage',
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(array('user', 'comment'));
    $this->installSchema('comment', array('comment_entity_statistics'));
  }

  /**
   * Test permissions on comment fields.
   */
  public function testAccessToAdministrativeFields() {
    // Create a comment type.
    $comment_type = CommentType::create([
      'id' => 'comment',
      'label' => 'Default comments',
      'description' => 'Default comment field',
      'target_entity_type_id' => 'entity_test',
    ]);
    $comment_type->save();

    // Create a comment against a test entity.
    $host = EntityTest::create();
    $host->save();

    // An administrator user. No user exists yet, ensure that the first user
    // does not have UID 1.
    $comment_admin_user = $this->createUser(['uid' => 2, 'name' => 'admin'], [
      'administer comments',
      'access comments',
    ]);

    // Two comment enabled users, one with edit access.
    $comment_enabled_user = $this->createUser(['name' => 'enabled'], [
      'post comments',
      'skip comment approval',
      'edit own comments',
      'access comments',
    ]);
    $comment_no_edit_user = $this->createUser(['name' => 'no edit'], [
      'post comments',
      'skip comment approval',
      'access comments',
    ]);

    // An unprivileged user.
    $comment_disabled_user = $this->createUser(['name' => 'disabled'], ['access content']);

    $role = Role::load(RoleInterface::ANONYMOUS_ID);
    $role->grantPermission('post comments')
      ->save();

    $anonymous_user = new AnonymousUserSession();

    // Add two fields.
    $this->addDefaultCommentField('entity_test', 'entity_test', 'comment');
    $this->addDefaultCommentField('entity_test', 'entity_test', 'comment_other');

    // Change the second field's anonymous contact setting.
    $instance = FieldConfig::loadByName('entity_test', 'entity_test', 'comment_other');
    // Default is 'May not contact', for this field - they may contact.
    $instance->setSetting('anonymous', COMMENT_ANONYMOUS_MAY_CONTACT);
    $instance->save();

    // Create three "Comments". One is owned by our edit-enabled user.
    $comment1 = Comment::create([
      'entity_type' => 'entity_test',
      'name' => 'Tony',
      'hostname' => 'magic.example.com',
      'mail' => 'tonythemagicalpony@example.com',
      'subject' => 'Bruce the Mesopotamian moose',
      'entity_id' => $host->id(),
      'comment_type' => 'comment',
      'field_name' => 'comment',
      'pid' => 0,
      'uid' => 0,
      'status' => 1,
    ]);
    $comment1->save();
    $comment2 = Comment::create([
      'entity_type' => 'entity_test',
      'hostname' => 'magic.example.com',
      'subject' => 'Brian the messed up lion',
      'entity_id' => $host->id(),
      'comment_type' => 'comment',
      'field_name' => 'comment',
      'status' => 1,
      'pid' => 0,
      'uid' => $comment_enabled_user->id(),
    ]);
    $comment2->save();
    $comment3 = Comment::create([
      'entity_type' => 'entity_test',
      'hostname' => 'magic.example.com',
      // Unpublished.
      'status' => 0,
      'subject' => 'Gail the minky whale',
      'entity_id' => $host->id(),
      'comment_type' => 'comment',
      'field_name' => 'comment_other',
      'pid' => $comment2->id(),
      'uid' => $comment_no_edit_user->id(),
    ]);
    $comment3->save();
    // Note we intentionally don't save this comment so it remains 'new'.
    $comment4 = Comment::create([
      'entity_type' => 'entity_test',
      'hostname' => 'magic.example.com',
      // Unpublished.
      'status' => 0,
      'subject' => 'Daniel the Cocker-Spaniel',
      'entity_id' => $host->id(),
      'comment_type' => 'comment',
      'field_name' => 'comment_other',
      'pid' => 0,
      'uid' => $anonymous_user->id(),
    ]);

    // Generate permutations.
    $combinations = [
      'comment' => [$comment1, $comment2, $comment3, $comment4],
      'user' => [$comment_admin_user, $comment_enabled_user, $comment_no_edit_user, $comment_disabled_user, $anonymous_user]
    ];
    $permutations = TestBase::generatePermutations($combinations);

    // Check access to administrative fields.
    foreach ($this->administrativeFields as $field) {
      foreach ($permutations as $set) {
        $may_view = $set['comment']->{$field}->access('view', $set['user']);
        $may_update = $set['comment']->{$field}->access('edit', $set['user']);
        $this->assertTrue($may_view, SafeMarkup::format('User @user can view field @field on comment @comment', [
          '@user' => $set['user']->getUsername(),
          '@comment' => $set['comment']->getSubject(),
          '@field' => $field,
        ]));
        $this->assertEqual($may_update, $set['user']->hasPermission('administer comments'), SafeMarkup::format('User @user @state update field @field on comment @comment', [
          '@user' => $set['user']->getUsername(),
          '@state' => $may_update ? 'can' : 'cannot',
          '@comment' => $set['comment']->getSubject(),
          '@field' => $field,
        ]));
      }
    }

    // Check access to normal field.
    foreach ($permutations as $set) {
      $may_update = $set['comment']->access('update', $set['user']) && $set['comment']->subject->access('edit', $set['user']);
      $this->assertEqual($may_update, $set['user']->hasPermission('administer comments') || ($set['user']->hasPermission('edit own comments') && $set['user']->id() == $set['comment']->getOwnerId()), SafeMarkup::format('User @user @state update field subject on comment @comment', [
        '@user' => $set['user']->getUsername(),
        '@state' => $may_update ? 'can' : 'cannot',
        '@comment' => $set['comment']->getSubject(),
      ]));
    }

    // Check read-only fields.
    foreach ($this->readOnlyFields as $field) {
      // Check view operation.
      foreach ($permutations as $set) {
        $may_view = $set['comment']->{$field}->access('view', $set['user']);
        $may_update = $set['comment']->{$field}->access('edit', $set['user']);
        // Nobody has access to view the hostname field.
        if ($field === 'hostname') {
          $view_access = FALSE;
          $state = 'cannot';
        }
        else {
          $view_access = TRUE;
          $state = 'can';
        }
        $this->assertEqual($may_view, $view_access, SafeMarkup::format('User @user @state view field @field on comment @comment', [
          '@user' => $set['user']->getUsername(),
          '@comment' => $set['comment']->getSubject(),
          '@field' => $field,
          '@state' => $state,
        ]));
        $this->assertFalse($may_update, SafeMarkup::format('User @user @state update field @field on comment @comment', [
          '@user' => $set['user']->getUsername(),
          '@state' => $may_update ? 'can' : 'cannot',
          '@comment' => $set['comment']->getSubject(),
          '@field' => $field,
        ]));
      }
    }

    // Check create-only fields.
    foreach ($this->createOnlyFields as $field) {
      // Check view operation.
      foreach ($permutations as $set) {
        $may_view = $set['comment']->{$field}->access('view', $set['user']);
        $may_update = $set['comment']->{$field}->access('edit', $set['user']);
        $this->assertEqual($may_view, TRUE, SafeMarkup::format('User @user can view field @field on comment @comment', [
          '@user' => $set['user']->getUsername(),
          '@comment' => $set['comment']->getSubject(),
          '@field' => $field,
        ]));
        $this->assertEqual($may_update, $set['user']->hasPermission('post comments') && $set['comment']->isNew(), SafeMarkup::format('User @user @state update field @field on comment @comment', [
          '@user' => $set['user']->getUsername(),
          '@state' => $may_update ? 'can' : 'cannot',
          '@comment' => $set['comment']->getSubject(),
          '@field' => $field,
        ]));
      }
    }

    // Check contact fields.
    foreach ($this->contactFields as $field) {
      // Check view operation.
      foreach ($permutations as $set) {
        $may_update = $set['comment']->{$field}->access('edit', $set['user']);
        // To edit the 'mail' or 'name' field, either the user has the
        // "administer comments" permissions or the user is anonymous and
        // adding a new comment using a field that allows contact details.
        $this->assertEqual($may_update, $set['user']->hasPermission('administer comments') || (
            $set['user']->isAnonymous() &&
            $set['comment']->isNew() &&
            $set['user']->hasPermission('post comments') &&
            $set['comment']->getFieldName() == 'comment_other'
          ), SafeMarkup::format('User @user @state update field @field on comment @comment', [
          '@user' => $set['user']->getUsername(),
          '@state' => $may_update ? 'can' : 'cannot',
          '@comment' => $set['comment']->getSubject(),
          '@field' => $field,
        ]));
      }
    }
    foreach ($permutations as $set) {
      // Check no view-access to mail field for other than admin.
      $may_view = $set['comment']->mail->access('view', $set['user']);
      $this->assertEqual($may_view, $set['user']->hasPermission('administer comments'));
    }
  }

}
