<?php

namespace Drupal\Tests\forum\Functional;

use Drupal\comment\CommentInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;
use Drupal\comment\Entity\Comment;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests forum module uninstallation.
 *
 * @group forum
 */
class ForumUninstallTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['forum'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests if forum module uninstallation properly deletes the field.
   */
  public function testForumUninstallWithField() {
    $this->drupalLogin($this->drupalCreateUser([
      'administer taxonomy',
      'administer nodes',
      'administer modules',
      'delete any forum content',
      'administer content types',
    ]));
    // Ensure that the field exists before uninstallation.
    $field_storage = FieldStorageConfig::loadByName('node', 'taxonomy_forums');
    $this->assertNotNull($field_storage, 'The taxonomy_forums field storage exists.');

    // Create a taxonomy term.
    $term = Term::create([
      'name' => 'A term',
      'langcode' => \Drupal::languageManager()->getDefaultLanguage()->getId(),
      'description' => '',
      'parent' => [0],
      'vid' => 'forums',
      'forum_container' => 0,
    ]);
    $term->save();

    // Create a forum node.
    $node = $this->drupalCreateNode([
      'title' => 'A forum post',
      'type' => 'forum',
      'taxonomy_forums' => [['target_id' => $term->id()]],
    ]);

    // Create at least one comment against the forum node.
    $comment = Comment::create([
      'entity_id' => $node->nid->value,
      'entity_type' => 'node',
      'field_name' => 'comment_forum',
      'pid' => 0,
      'uid' => 0,
      'status' => CommentInterface::PUBLISHED,
      'subject' => $this->randomMachineName(),
      'hostname' => '127.0.0.1',
    ]);
    $comment->save();

    // Attempt to uninstall forum.
    $this->drupalGet('admin/modules/uninstall');
    // Assert forum is required.
    $this->assertSession()->fieldDisabled('uninstall[forum]');
    $this->assertSession()->pageTextContains('To uninstall Forum, first delete all Forum content');

    // Delete the node.
    $this->drupalGet('node/' . $node->id() . '/delete');
    $this->submitForm([], 'Delete');

    // Attempt to uninstall forum.
    $this->drupalGet('admin/modules/uninstall');
    // Assert forum is still required.
    $this->assertSession()->fieldDisabled('uninstall[forum]');
    $this->assertSession()->pageTextContains('To uninstall Forum, first delete all Forums terms');

    // Delete any forum terms.
    $vid = $this->config('forum.settings')->get('vocabulary');
    $storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $terms = $storage->loadByProperties(['vid' => $vid]);
    $storage->delete($terms);

    // Ensure that the forum node type can not be deleted.
    $this->drupalGet('admin/structure/types/manage/forum');
    $this->assertSession()->linkNotExists('Delete');

    // Now attempt to uninstall forum.
    $this->drupalGet('admin/modules/uninstall');
    // Assert forum is no longer required.
    $this->assertSession()->fieldExists('uninstall[forum]');
    $this->drupalGet('admin/modules/uninstall');
    $this->submitForm(['uninstall[forum]' => 1], 'Uninstall');
    $this->submitForm([], 'Uninstall');

    // Check that the field is now deleted.
    $field_storage = FieldStorageConfig::loadByName('node', 'taxonomy_forums');
    $this->assertNull($field_storage, 'The taxonomy_forums field storage has been deleted.');

    // Check that a node type with a machine name of forum can be created after
    // uninstalling the forum module and the node type is not locked.
    $edit = [
      'name' => 'Forum',
      'title_label' => 'title for forum',
      'type' => 'forum',
    ];
    $this->drupalGet('admin/structure/types/add');
    $this->submitForm($edit, 'Save content type');
    $this->assertTrue((bool) NodeType::load('forum'), 'Node type with machine forum created.');
    $this->drupalGet('admin/structure/types/manage/forum');
    $this->clickLink('Delete');
    $this->submitForm([], 'Delete');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertFalse((bool) NodeType::load('forum'), 'Node type with machine forum deleted.');

    // Double check everything by reinstalling the forum module again.
    $this->drupalGet('admin/modules');
    $this->submitForm(['modules[forum][enable]' => 1], 'Install');
    $this->assertSession()->pageTextContains('Module Forum has been enabled.');
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
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['vid' => 'forums']);
    foreach ($terms as $term) {
      $term->delete();
    }

    // Ensure that uninstallation succeeds even if the field has already been
    // deleted manually beforehand.
    $this->container->get('module_installer')->uninstall(['forum']);
  }

}
