<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateCommentTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\comment\Entity\Comment;
use Drupal\Core\Language\LanguageInterface;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Upgrade comments.
 *
 * @group migrate_drupal
 */
class MigrateCommentTest extends MigrateDrupalTestBase {

  static $modules = array('node', 'comment');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    entity_create('node_type', array('type' => 'page'))->save();
    entity_create('node_type', array('type' => 'story'))->save();
    $this->container->get('entity.manager')->getStorage('comment_type')->create(array(
      'id' => 'comment',
      'label' => 'comment',
      'target_entity_type_id' => 'node',
    ))->save();
    $this->container->get('entity.manager')->getStorage('comment_type')->create(array(
      'id' => 'comment_no_subject',
      'label' => 'comment_no_subject',
      'target_entity_type_id' => 'node',
    ))->save();

    $node = entity_create('node', array(
      'type' => 'story',
      'nid' => 1,
    ));
    $node->enforceIsNew();
    $node->save();
    $id_mappings = array(
      'd6_filter_format' => array(array(array(1), array('filtered_html'))),
      'd6_node' => array(array(array(1), array(1))),
      'd6_user' => array(array(array(0), array(0))),
      'd6_comment_type' => array(array(array('comment'), array('comment_no_subject'))),
      'd6_comment_entity_display' => array(array(array('story'), array('node', 'story', 'default', 'comment'))),
      'd6_comment_entity_form_display' => array(array(array('story'), array('node', 'story', 'default', 'comment'))),
    );
    $this->prepareMigrations($id_mappings);

    \Drupal::service('comment.manager')->addDefaultField('node', 'story');
    /** @var \Drupal\migrate\entity\Migration $migration */
    $migration = entity_load('migration', 'd6_comment');

    $dumps = array(
      $this->getDumpDirectory() . '/Drupal6Node.php',
      $this->getDumpDirectory() . '/Drupal6CommentVariable.php',
      $this->getDumpDirectory() . '/Drupal6Comment.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  /**
   * Tests the Drupal 6 to Drupal 8 comment migration.
   */
  public function testComments() {
    /** @var \Drupal\comment\CommentInterface $comment */
    $comment = entity_load('comment', 1);
    $this->assertEqual('The first comment.', $comment->getSubject());
    $this->assertEqual('The first comment body.', $comment->comment_body->value);
    $this->assertEqual('filtered_html', $comment->comment_body->format);
    $this->assertEqual(0, $comment->pid->target_id);
    $this->assertEqual(1, $comment->getCommentedEntityId());
    $this->assertEqual('node', $comment->getCommentedEntityTypeId());
    $this->assertEqual('en', $comment->language()->getId());
    $this->assertEqual('comment_no_subject', $comment->getTypeId());

    $comment = entity_load('comment', 2);
    $this->assertEqual('The response to the second comment.', $comment->subject->value);
    $this->assertEqual(3, $comment->pid->target_id);

    $comment = entity_load('comment', 3);
    $this->assertEqual('The second comment.', $comment->subject->value);
    $this->assertEqual(0, $comment->pid->target_id);
  }
}
