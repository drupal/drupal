<?php

/**
 * @file
 * Contains \Drupal\comment\Tests\CommentDefaultFormatterCacheTagsTest.
 */

namespace Drupal\comment\Tests;

use Drupal\comment\CommentInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Tests CommentDefaultFormatter's cache tag bubbling.
 */
class CommentDefaultFormatterCacheTagsTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('entity_test', 'comment');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Comment list cache tags',
      'description' => 'Tests the bubbling up of comment cache tags when using the Comment list formatter on an entity.',
      'group' => 'Comment',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->drupalLogin($this->drupalCreateUser(array(
      'access comments',
    )));

    // Set up a field, so that the entity that'll be referenced bubbles up a
    // cache tag when rendering it entirely.
    entity_create('field_config', array(
      'name' => 'comment',
      'entity_type' => 'entity_test',
      'type' => 'comment',
      'settings' => array(),
    ))->save();
    entity_create('field_instance_config', array(
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'field_name' => 'comment',
      'label' => 'Comment',
      'settings' => array(),
    ))->save();
    entity_get_display('entity_test', 'entity_test', 'default')
      ->setComponent('comment', array(
        'type' => 'comment_default',
        'settings' => array(),
      ))
      ->save();
  }

  /**
   * Tests the bubbling of cache tags.
   */
  public function testCacheTags() {
    // Create the entity that will be commented upon.
    $commented_entity = entity_create('entity_test', array('name' => $this->randomName()));
    $commented_entity->save();

    // Verify cache tags on the rendered entity before it has comments.
    $build = \Drupal::entityManager()
      ->getViewBuilder('entity_test')
      ->view($commented_entity);
    drupal_render($build);
    $expected_cache_tags = array(
      'entity_test_view' => TRUE,
      'entity_test' => array(1 => $commented_entity->id()),
    );
    $this->assertEqual($build['#cache']['tags'], $expected_cache_tags, 'The test entity has the expected cache tags before it has comments.');

    // Create a comment on that entity..
    $comment = entity_create('comment', array(
      'subject' => 'Llama',
      'comment_body' => array(
        'value' => 'Llamas are cool!',
        'format' => 'plain_text',
      ),
      'entity_id' => $commented_entity->id(),
      'entity_type' => 'entity_test',
      'field_name' => 'comment',
      'status' => CommentInterface::PUBLISHED,
    ));
    $comment->save();

    // Load commented entity so comment_count gets computed.
    // @todo remove the $reset = TRUE parameter after
    //   https://drupal.org/node/597236 lands, it's a temporary work-around.
    $commented_entity = entity_load('entity_test', $commented_entity->id(), TRUE);

    // Verify cache tags on the rendered entity before it has comments.
    $build = \Drupal::entityManager()
      ->getViewBuilder('entity_test')
      ->view($commented_entity);
    drupal_render($build);
    $expected_cache_tags = array(
      'entity_test_view' => TRUE,
      'entity_test' => array(1 => $commented_entity->id()),
      'comment_view' => TRUE,
      'comment' => array(1 => $comment->id()),
      'filter_format' => array(
        'plain_text' => 'plain_text',
      ),
    );
    $this->assertEqual($build['#cache']['tags'], $expected_cache_tags, 'The test entity has the expected cache tags when it has comments.');
  }

}
