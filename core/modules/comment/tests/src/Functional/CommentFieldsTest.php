<?php

namespace Drupal\Tests\comment\Functional;

use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\comment\Entity\CommentType;

/**
 * Tests fields on comments.
 *
 * @group comment
 */
class CommentFieldsTest extends CommentTestBase {

  /**
   * Install the field UI.
   *
   * @var array
   */
  public static $modules = ['field_ui'];

  /**
   * Tests that the default 'comment_body' field is correctly added.
   */
  public function testCommentDefaultFields() {
    // Do not make assumptions on default node types created by the test
    // installation profile, and create our own.
    $this->drupalCreateContentType(['type' => 'test_node_type']);
    $this->addDefaultCommentField('node', 'test_node_type');

    // Check that the 'comment_body' field is present on the comment bundle.
    $field = FieldConfig::loadByName('comment', 'comment', 'comment_body');
    $this->assertTrue(!empty($field), 'The comment_body field is added when a comment bundle is created');

    $field->delete();

    // Check that the 'comment_body' field is not deleted since it is persisted
    // even if it has no fields.
    $field_storage = FieldStorageConfig::loadByName('comment', 'comment_body');
    $this->assertTrue($field_storage, 'The comment_body field storage was not deleted');

    // Create a new content type.
    $type_name = 'test_node_type_2';
    $this->drupalCreateContentType(['type' => $type_name]);
    $this->addDefaultCommentField('node', $type_name);

    // Check that the 'comment_body' field exists and has an instance on the
    // new comment bundle.
    $field_storage = FieldStorageConfig::loadByName('comment', 'comment_body');
    $this->assertTrue($field_storage, 'The comment_body field exists');
    $field = FieldConfig::loadByName('comment', 'comment', 'comment_body');
    $this->assertTrue(isset($field), format_string('The comment_body field is present for comments on type @type', ['@type' => $type_name]));

    // Test adding a field that defaults to CommentItemInterface::CLOSED.
    $this->addDefaultCommentField('node', 'test_node_type', 'who_likes_ponies', CommentItemInterface::CLOSED, 'who_likes_ponies');
    $field = FieldConfig::load('node.test_node_type.who_likes_ponies');;
    $this->assertEqual($field->getDefaultValueLiteral()[0]['status'], CommentItemInterface::CLOSED);
  }

  /**
   * Tests that you can remove a comment field.
   */
  public function testCommentFieldDelete() {
    $this->drupalCreateContentType(['type' => 'test_node_type']);
    $this->addDefaultCommentField('node', 'test_node_type');
    // We want to test the handling of removing the primary comment field, so we
    // ensure there is at least one other comment field attached to a node type
    // so that comment_entity_load() runs for nodes.
    $this->addDefaultCommentField('node', 'test_node_type', 'comment2');

    // Create a sample node.
    $node = $this->drupalCreateNode([
      'title' => 'Baloney',
      'type' => 'test_node_type',
    ]);

    $this->drupalLogin($this->webUser);

    $this->drupalGet('node/' . $node->nid->value);
    $elements = $this->cssSelect('.field--type-comment');
    $this->assertEqual(2, count($elements), 'There are two comment fields on the node.');

    // Delete the first comment field.
    FieldStorageConfig::loadByName('node', 'comment')->delete();
    $this->drupalGet('node/' . $node->nid->value);
    $elements = $this->cssSelect('.field--type-comment');
    $this->assertEqual(1, count($elements), 'There is one comment field on the node.');
  }

  /**
   * Tests link building with non-default comment field names.
   */
  public function testCommentFieldLinksNonDefaultName() {
    $this->drupalCreateContentType(['type' => 'test_node_type']);
    $this->addDefaultCommentField('node', 'test_node_type', 'comment2');

    $web_user2 = $this->drupalCreateUser([
      'access comments',
      'post comments',
      'create article content',
      'edit own comments',
      'skip comment approval',
      'access content',
    ]);

    // Create a sample node.
    $node = $this->drupalCreateNode([
      'title' => 'Baloney',
      'type' => 'test_node_type',
    ]);

    // Go to the node first so that webuser2 see new comments.
    $this->drupalLogin($web_user2);
    $this->drupalGet($node->toUrl());
    $this->drupalLogout();

    // Test that buildCommentedEntityLinks() does not break when the 'comment'
    // field does not exist. Requires at least one comment.
    $this->drupalLogin($this->webUser);
    $this->postComment($node, 'Here is a comment', '', NULL, 'comment2');
    $this->drupalLogout();

    $this->drupalLogin($web_user2);

    // We want to check the attached drupalSettings of
    // \Drupal\comment\CommentLinkBuilder::buildCommentedEntityLinks. Therefore
    // we need a node listing, let's use views for that.
    $this->container->get('module_installer')->install(['views'], TRUE);
    // We also need a router rebuild, as the router is lazily rebuild in the
    // module installer.
    \Drupal::service('router.builder')->rebuild();
    $this->drupalGet('node');

    $link_info = $this->getDrupalSettings()['comment']['newCommentsLinks']['node']['comment2']['2'];
    $this->assertIdentical($link_info['new_comment_count'], 1);
    $this->assertIdentical($link_info['first_new_comment_link'], $node->toUrl('canonical', ['fragment' => 'new'])->toString());
  }

  /**
   * Tests creating a comment field through the interface.
   */
  public function testCommentFieldCreate() {
    // Create user who can administer user fields.
    $user = $this->drupalCreateUser([
      'administer user fields',
    ]);
    $this->drupalLogin($user);

    // Create comment field in account settings.
    $edit = [
      'new_storage_type' => 'comment',
      'label' => 'User comment',
      'field_name' => 'user_comment',
    ];
    $this->drupalPostForm('admin/config/people/accounts/fields/add-field', $edit, 'Save and continue');

    // Try to save the comment field without selecting a comment type.
    $edit = [];
    $this->drupalPostForm('admin/config/people/accounts/fields/user.user.field_user_comment/storage', $edit, t('Save field settings'));
    // We should get an error message.
    $this->assertText(t('An illegal choice has been detected. Please contact the site administrator.'));

    // Create a comment type for users.
    $bundle = CommentType::create([
      'id' => 'user_comment_type',
      'label' => 'user_comment_type',
      'description' => '',
      'target_entity_type_id' => 'user',
    ]);
    $bundle->save();

    // Select a comment type and try to save again.
    $edit = [
      'settings[comment_type]' => 'user_comment_type',
    ];
    $this->drupalPostForm('admin/config/people/accounts/fields/user.user.field_user_comment/storage', $edit, t('Save field settings'));
    // We shouldn't get an error message.
    $this->assertNoText(t('An illegal choice has been detected. Please contact the site administrator.'));
  }

  /**
   * Tests that comment module works when installed after a content module.
   */
  public function testCommentInstallAfterContentModule() {
    // Create a user to do module administration.
    $this->adminUser = $this->drupalCreateUser(['access administration pages', 'administer modules']);
    $this->drupalLogin($this->adminUser);

    // Drop default comment field added in CommentTestBase::setup().
    FieldStorageConfig::loadByName('node', 'comment')->delete();
    if ($field_storage = FieldStorageConfig::loadByName('node', 'comment_forum')) {
      $field_storage->delete();
    }

    // Purge field data now to allow comment module to be uninstalled once the
    // field has been deleted.
    field_purge_batch(10);

    // Uninstall the comment module.
    $edit = [];
    $edit['uninstall[comment]'] = TRUE;
    $this->drupalPostForm('admin/modules/uninstall', $edit, t('Uninstall'));
    $this->drupalPostForm(NULL, [], t('Uninstall'));
    $this->rebuildContainer();
    $this->assertFalse($this->container->get('module_handler')->moduleExists('comment'), 'Comment module uninstalled.');

    // Install core content type module (book).
    $edit = [];
    $edit['modules[book][enable]'] = 'book';
    $this->drupalPostForm('admin/modules', $edit, t('Install'));

    // Now install the comment module.
    $edit = [];
    $edit['modules[comment][enable]'] = 'comment';
    $this->drupalPostForm('admin/modules', $edit, t('Install'));
    $this->rebuildContainer();
    $this->assertTrue($this->container->get('module_handler')->moduleExists('comment'), 'Comment module enabled.');

    // Create nodes of each type.
    $this->addDefaultCommentField('node', 'book');
    $book_node = $this->drupalCreateNode(['type' => 'book']);

    $this->drupalLogout();

    // Try to post a comment on each node. A failure will be triggered if the
    // comment body is missing on one of these forms, due to postComment()
    // asserting that the body is actually posted correctly.
    $this->webUser = $this->drupalCreateUser(['access content', 'access comments', 'post comments', 'skip comment approval']);
    $this->drupalLogin($this->webUser);
    $this->postComment($book_node, $this->randomMachineName(), $this->randomMachineName());
  }

}
