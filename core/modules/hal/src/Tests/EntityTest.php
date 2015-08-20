<?php

/**
 * @file
 * Contains \Drupal\hal\Tests\EntityTest.
 */

namespace Drupal\hal\Tests;

use Drupal\comment\Tests\CommentTestTrait;

/**
 * Tests that nodes and terms are correctly normalized and denormalized.
 *
 * @group hal
 */
class EntityTest extends NormalizerTestBase {

  use CommentTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'taxonomy', 'comment');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    \Drupal::service('router.builder')->rebuild();
    $this->installSchema('system', array('sequences'));
    $this->installSchema('comment', array('comment_entity_statistics'));
    $this->installEntitySchema('taxonomy_term');
    $this->installConfig(['node', 'comment']);
  }

  /**
   * Tests the normalization of nodes.
   */
  public function testNode() {
    $node_type = entity_create('node_type', array('type' => 'example_type'));
    $node_type->save();

    $user = entity_create('user', array('name' => $this->randomMachineName()));
    $user->save();

    // Add comment type.
    $this->container->get('entity.manager')->getStorage('comment_type')->create(array(
      'id' => 'comment',
      'label' => 'comment',
      'target_entity_type_id' => 'node',
    ))->save();

    $this->addDefaultCommentField('node', 'example_type');

    $node = entity_create('node', array(
      'title' => $this->randomMachineName(),
      'uid' => $user->id(),
      'type' => $node_type->id(),
      'status' => NODE_PUBLISHED,
      'promote' => 1,
      'sticky' => 0,
      'body' => array(
        'value' => $this->randomMachineName(),
        'format' => $this->randomMachineName(),
      ),
      'revision_log' => $this->randomString(),
    ));
    $node->save();

    $original_values = $node->toArray();
    unset($original_values['nid']);
    unset($original_values['vid']);

    $normalized = $this->serializer->normalize($node, $this->format);

    $denormalized_node = $this->serializer->denormalize($normalized, 'Drupal\node\Entity\Node', $this->format);

    // Verify that the ID and revision ID were skipped by the normalizer.
    $this->assertEqual(NULL, $denormalized_node->id());
    $this->assertEqual(NULL, $denormalized_node->getRevisionId());

    // Loop over the remaining fields and verify that they are identical.
    foreach ($original_values as $field_name => $field_values) {
      $this->assertEqual($field_values, $denormalized_node->get($field_name)->getValue());
    }
  }

  /**
   * Tests the normalization of terms.
   */
  public function testTerm() {
    $vocabulary = entity_create('taxonomy_vocabulary', array('vid' => 'example_vocabulary'));
    $vocabulary->save();

    $account = entity_create('user', array('name' => $this->randomMachineName()));
    $account->save();

    // @todo Until https://www.drupal.org/node/2327935 is fixed, if no parent is
    // set, the test fails because target_id => 0 is reserialized to NULL.
    $term_parent = entity_create('taxonomy_term', array(
      'name' => $this->randomMachineName(),
      'vid' => $vocabulary->id(),
    ));
    $term_parent->save();
    $term = entity_create('taxonomy_term', array(
      'name' => $this->randomMachineName(),
      'vid' => $vocabulary->id(),
      'description' => array(
        'value' => $this->randomMachineName(),
        'format' => $this->randomMachineName(),
      ),
      'parent' => $term_parent->id(),
    ));
    $term->save();

    $original_values = $term->toArray();
    unset($original_values['tid']);

    $normalized = $this->serializer->normalize($term, $this->format, ['account' => $account]);

    $denormalized_term = $this->serializer->denormalize($normalized, 'Drupal\taxonomy\Entity\Term', $this->format, ['account' => $account]);

    // Verify that the ID and revision ID were skipped by the normalizer.
    $this->assertEqual(NULL, $denormalized_term->id());

    // Loop over the remaining fields and verify that they are identical.
    foreach ($original_values as $field_name => $field_values) {
      $this->assertEqual($field_values, $denormalized_term->get($field_name)->getValue());
    }
  }

  /**
   * Tests the normalization of comments.
   */
  public function testComment() {
    $node_type = entity_create('node_type', array('type' => 'example_type'));
    $node_type->save();

    $account = entity_create('user', array('name' => $this->randomMachineName()));
    $account->save();

    // Add comment type.
    $this->container->get('entity.manager')->getStorage('comment_type')->create(array(
      'id' => 'comment',
      'label' => 'comment',
      'target_entity_type_id' => 'node',
    ))->save();

    $this->addDefaultCommentField('node', 'example_type');

    $node = entity_create('node', array(
      'title' => $this->randomMachineName(),
      'uid' => $account->id(),
      'type' => $node_type->id(),
      'status' => NODE_PUBLISHED,
      'promote' => 1,
      'sticky' => 0,
      'body' => array(
        'value' => $this->randomMachineName(),
        'format' => $this->randomMachineName(),
      )
    ));
    $node->save();

    $parent_comment = entity_create('comment', array(
      'uid' => $account->id(),
      'subject' => $this->randomMachineName(),
      'comment_body' => [
        'value' => $this->randomMachineName(),
        'format' => NULL,
      ],
      'entity_id' => $node->id(),
      'entity_type' => 'node',
      'field_name' => 'comment',
    ));
    $parent_comment->save();

    $comment = entity_create('comment', array(
      'uid' => $account->id(),
      'subject' => $this->randomMachineName(),
      'comment_body' => [
        'value' => $this->randomMachineName(),
        'format' => NULL,
      ],
      'entity_id' => $node->id(),
      'entity_type' => 'node',
      'field_name' => 'comment',
      'pid' => $parent_comment->id(),
      'mail' => 'dries@drupal.org',
      'homepage' => 'http://buytaert.net',
    ));
    $comment->save();

    $original_values = $comment->toArray();
    // cid will not exist and hostname will always be denied view access.
    // No value will exist for name as this is only for anonymous users.
    unset($original_values['cid'], $original_values['hostname'], $original_values['name']);

    $normalized = $this->serializer->normalize($comment, $this->format, ['account' => $account]);

    // Assert that the hostname field does not appear at all in the normalized
    // data.
    $this->assertFalse(array_key_exists('hostname', $normalized), 'Hostname was not found in normalized comment data.');

    $denormalized_comment = $this->serializer->denormalize($normalized, 'Drupal\comment\Entity\Comment', $this->format, ['account' => $account]);

    // Verify that the ID and revision ID were skipped by the normalizer.
    $this->assertEqual(NULL, $denormalized_comment->id());

    // Loop over the remaining fields and verify that they are identical.
    foreach ($original_values as $field_name => $field_values) {
      // The target field comes with revision id which is not set.
      if (array_key_exists('revision_id', $field_values[0])) {
        unset($field_values[0]['revision_id']);
      }
      $this->assertEqual($field_values, $denormalized_comment->get($field_name)->getValue());
    }
  }

}
