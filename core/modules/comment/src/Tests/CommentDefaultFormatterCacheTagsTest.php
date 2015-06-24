<?php

/**
 * @file
 * Contains \Drupal\comment\Tests\CommentDefaultFormatterCacheTagsTest.
 */

namespace Drupal\comment\Tests;

use Drupal\Core\Session\UserSession;
use Drupal\comment\CommentInterface;
use Drupal\system\Tests\Entity\EntityUnitTestBase;

/**
 * Tests the bubbling up of comment cache tags when using the Comment list
 * formatter on an entity.
 *
 * @group comment
 */
class CommentDefaultFormatterCacheTagsTest extends EntityUnitTestBase {

  use CommentTestTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('entity_test', 'comment');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Set the current user to one that can access comments. Specifically, this
    // user does not have access to the 'administer comments' permission, to
    // ensure only published comments are visible to the end user.
    $current_user = $this->container->get('current_user');
    $current_user->setAccount($this->createUser(array(), array('access comments')));

    // Install tables and config needed to render comments.
    $this->installSchema('comment', array('comment_entity_statistics'));
    $this->installConfig(array('system', 'filter', 'comment'));

    // Comment rendering generates links, so build the router.
    $this->installSchema('system', array('router'));
    $this->container->get('router.builder')->rebuild();

    // Set up a field, so that the entity that'll be referenced bubbles up a
    // cache tag when rendering it entirely.
    $this->addDefaultCommentField('entity_test', 'entity_test');
  }

  /**
   * Tests the bubbling of cache tags.
   */
  public function testCacheTags() {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');

    // Create the entity that will be commented upon.
    $commented_entity = entity_create('entity_test', array('name' => $this->randomMachineName()));
    $commented_entity->save();

    // Verify cache tags on the rendered entity before it has comments.
    $build = \Drupal::entityManager()
      ->getViewBuilder('entity_test')
      ->view($commented_entity);
    $renderer->renderRoot($build);
    $expected_cache_tags = array(
      'entity_test_view',
      'entity_test:'  . $commented_entity->id(),
      'comment_list',
      'config:core.entity_form_display.comment.comment.default',
      'config:field.field.comment.comment.comment_body',
      'config:field.field.entity_test.entity_test.comment',
      'config:field.storage.comment.comment_body',
      'config:user.settings',
    );
    sort($expected_cache_tags);
    $this->assertEqual($build['#cache']['tags'], $expected_cache_tags, 'The test entity has the expected cache tags before it has comments.');

    // Create a comment on that entity. Comment loading requires that the uid
    // also exists in the {users} table.
    $user = $this->createUser();
    $user->save();
    $comment = entity_create('comment', array(
      'subject' => 'Llama',
      'comment_body' => array(
        'value' => 'Llamas are cool!',
        'format' => 'plain_text',
      ),
      'entity_id' => $commented_entity->id(),
      'entity_type' => 'entity_test',
      'field_name' => 'comment',
      'comment_type' => 'comment',
      'status' => CommentInterface::PUBLISHED,
      'uid' => $user->id(),
    ));
    $comment->save();

    // Load commented entity so comment_count gets computed.
    // @todo Remove the $reset = TRUE parameter after
    //   https://www.drupal.org/node/597236 lands. It's a temporary work-around.
    $commented_entity = entity_load('entity_test', $commented_entity->id(), TRUE);

    // Verify cache tags on the rendered entity when it has comments.
    $build = \Drupal::entityManager()
      ->getViewBuilder('entity_test')
      ->view($commented_entity);
    $renderer->renderRoot($build);
    $expected_cache_tags = array(
      'entity_test_view',
      'entity_test:' . $commented_entity->id(),
      'comment_list',
      'comment_view',
      'comment:' . $comment->id(),
      'config:filter.format.plain_text',
      'user_view',
      'user:2',
      'config:core.entity_form_display.comment.comment.default',
      'config:field.field.comment.comment.comment_body',
      'config:field.field.entity_test.entity_test.comment',
      'config:field.storage.comment.comment_body',
      'config:user.settings',
    );
    sort($expected_cache_tags);
    $this->assertEqual($build['#cache']['tags'], $expected_cache_tags, 'The test entity has the expected cache tags when it has comments.');
  }

}
