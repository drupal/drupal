<?php

/**
 * @file
 * Definition of Drupal\forum\Tests\ForumUninstallTest.
 */

namespace Drupal\forum\Tests;

use Drupal\comment\CommentInterface;
use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\simpletest\WebTestBase;

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
  function testForumUninstallWithField() {
    // Ensure that the field exists before uninstallation.
    $field_storage = FieldStorageConfig::loadByName('node', 'taxonomy_forums');
    $this->assertNotNull($field_storage, 'The taxonomy_forums field storage exists.');

    // Create a taxonomy term.
    $term = entity_create('taxonomy_term', array(
      'name' => t('A term'),
      'langcode' => \Drupal::languageManager()->getDefaultLanguage()->getId(),
      'description' => '',
      'parent' => array(0),
      'vid' => 'forums',
      'forum_container' => 0,
    ));
    $term->save();

    // Create a forum node.
    $node = $this->drupalCreateNode(array(
      'title' => 'A forum post',
      'type' => 'forum',
      'taxonomy_forums' => array(array('target_id' => $term->id())),
    ));

    // Create at least one comment against the forum node.
    $comment = entity_create('comment', array(
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

    // Uninstall the forum module which should trigger field deletion.
    $this->container->get('module_handler')->uninstall(array('forum'));

    // We want to test the handling of removing the forum comment field, so we
    // ensure there is at least one other comment field attached to a node type
    // so that comment_entity_load() runs for nodes.
    \Drupal::service('comment.manager')->addDefaultField('node', 'forum', 'another_comment_field', CommentItemInterface::OPEN, 'another_comment_field');

    $this->drupalGet('node/' . $node->nid->value);
    $this->assertResponse(200);

    // Check that the field is now deleted.
    $field_storage = FieldStorageConfig::loadByName('node', 'taxonomy_forums');
    $this->assertNull($field_storage, 'The taxonomy_forums field storage has been deleted.');
  }


  /**
   * Tests uninstallation if the field storage has been deleted beforehand.
   */
  function testForumUninstallWithoutFieldStorage() {
    // Manually delete the taxonomy_forums field before module uninstallation.
    $field_storage = FieldStorageConfig::loadByName('node', 'taxonomy_forums');
    $this->assertNotNull($field_storage, 'The taxonomy_forums field storage exists.');
    $field_storage->delete();

    // Check that the field is now deleted.
    $field_storage = FieldStorageConfig::loadByName('node', 'taxonomy_forums');
    $this->assertNull($field_storage, 'The taxonomy_forums field storage has been deleted.');

    // Ensure that uninstallation succeeds even if the field has already been
    // deleted manually beforehand.
    $this->container->get('module_handler')->uninstall(array('forum'));
  }

}
