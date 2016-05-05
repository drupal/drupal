<?php

namespace Drupal\forum\Tests;

use Drupal\comment\CommentInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;
use Drupal\simpletest\WebTestBase;
use Drupal\comment\Entity\Comment;
use Drupal\taxonomy\Entity\Term;

/**
 * Tests forum module uninstallation.
 *
 * @group forum
 */
class ForumUninstallTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('forum');

  /**
   * Tests if forum module uninstallation properly deletes the field.
   */
  public function testForumUninstallWithField() {
    $this->drupalLogin($this->drupalCreateUser(['administer taxonomy', 'administer nodes', 'administer modules', 'delete any forum content', 'administer content types']));
    // Ensure that the field exists before uninstallation.
    $field_storage = FieldStorageConfig::loadByName('node', 'taxonomy_forums');
    $this->assertNotNull($field_storage, 'The taxonomy_forums field storage exists.');

    // Create a taxonomy term.
    $term = Term::create([
      'name' => t('A term'),
      'langcode' => \Drupal::languageManager()->getDefaultLanguage()->getId(),
      'description' => '',
      'parent' => array(0),
      'vid' => 'forums',
      'forum_container' => 0,
    ]);
    $term->save();

    // Create a forum node.
    $node = $this->drupalCreateNode(array(
      'title' => 'A forum post',
      'type' => 'forum',
      'taxonomy_forums' => array(array('target_id' => $term->id())),
    ));

    // Create at least one comment against the forum node.
    $comment = Comment::create(array(
      'entity_id' => $node->nid->value,
      'entity_type' => 'node',
      'field_name' => 'comment_forum',
      'pid' => 0,
      'uid' => 0,
      'status' => CommentInterface::PUBLISHED,
      'subject' => $this->randomMachineName(),
      'hostname' => '127.0.0.1',
    ));
    $comment->save();

    // Attempt to uninstall forum.
    $this->drupalGet('admin/modules/uninstall');
    // Assert forum is required.
    $this->assertNoFieldByName('uninstall[forum]');
    $this->assertText('To uninstall Forum, first delete all Forum content');

    // Delete the node.
    $this->drupalPostForm('node/' . $node->id() . '/delete', array(), t('Delete'));

    // Attempt to uninstall forum.
    $this->drupalGet('admin/modules/uninstall');
    // Assert forum is still required.
    $this->assertNoFieldByName('uninstall[forum]');
    $this->assertText('To uninstall Forum, first delete all Forums terms');

    // Delete any forum terms.
    $vid = $this->config('forum.settings')->get('vocabulary');
    $terms = entity_load_multiple_by_properties('taxonomy_term', ['vid' => $vid]);
    foreach ($terms as $term) {
      $term->delete();
    }

    // Ensure that the forum node type can not be deleted.
    $this->drupalGet('admin/structure/types/manage/forum');
    $this->assertNoLink(t('Delete'));

    // Now attempt to uninstall forum.
    $this->drupalGet('admin/modules/uninstall');
    // Assert forum is no longer required.
    $this->assertFieldByName('uninstall[forum]');
    $this->drupalPostForm('admin/modules/uninstall', array(
      'uninstall[forum]' => 1,
    ), t('Uninstall'));
    $this->drupalPostForm(NULL, [], t('Uninstall'));

    // Check that the field is now deleted.
    $field_storage = FieldStorageConfig::loadByName('node', 'taxonomy_forums');
    $this->assertNull($field_storage, 'The taxonomy_forums field storage has been deleted.');

    // Check that a node type with a machine name of forum can be created after
    // uninstalling the forum module and the node type is not locked.
    $edit = array(
      'name' => 'Forum',
      'title_label' => 'title for forum',
      'type' => 'forum',
    );
    $this->drupalPostForm('admin/structure/types/add', $edit, t('Save content type'));
    $this->assertTrue((bool) NodeType::load('forum'), 'Node type with machine forum created.');
    $this->drupalGet('admin/structure/types/manage/forum');
    $this->clickLink(t('Delete'));
    $this->drupalPostForm(NULL, array(), t('Delete'));
    $this->assertResponse(200);
    $this->assertFalse((bool) NodeType::load('forum'), 'Node type with machine forum deleted.');

    // Double check everything by reinstalling the forum module again.
    $this->drupalPostForm('admin/modules', ['modules[Core][forum][enable]' => 1], 'Install');
    $this->assertText('Module Forum has been enabled.');
  }

  /**
   * Tests uninstallation if the field storage has been deleted beforehand.
   */
  public function testForumUninstallWithoutFieldStorage() {
    // Manually delete the taxonomy_forums field before module uninstallation.
    $field_storage = FieldStorageConfig::loadByName('node', 'taxonomy_forums');
    $this->assertNotNull($field_storage, 'The taxonomy_forums field storage exists.');
    $field_storage->delete();

    // Check that the field is now deleted.
    $field_storage = FieldStorageConfig::loadByName('node', 'taxonomy_forums');
    $this->assertNull($field_storage, 'The taxonomy_forums field storage has been deleted.');

    // Delete all terms in the Forums vocabulary. Uninstalling the forum module
    // will fail unless this is done.
    $terms = entity_load_multiple_by_properties('taxonomy_term', array('vid' => 'forums'));
    foreach ($terms as $term) {
      $term->delete();
    }

    // Ensure that uninstallation succeeds even if the field has already been
    // deleted manually beforehand.
    $this->container->get('module_installer')->uninstall(array('forum'));
  }

}
