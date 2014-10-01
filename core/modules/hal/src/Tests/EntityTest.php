<?php

/**
 * @file
 * Contains \Drupal\hal\Tests\NormalizeTest.
 */

namespace Drupal\hal\Tests;

/**
 * Tests that nodes and terms are correctly normalized and denormalized.
 *
 * @group hal
 */
class EntityTest extends NormalizerTestBase {

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
    $this->installEntitySchema('node');
    $this->installEntitySchema('comment');
    $this->installEntitySchema('taxonomy_term');
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

    $this->container->get('comment.manager')->addDefaultField('node', 'example_type');

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

    $normalized = $this->serializer->normalize($term, $this->format);

    $denormalized_term = $this->serializer->denormalize($normalized, 'Drupal\taxonomy\Entity\Term', $this->format);

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

    $user = entity_create('user', array('name' => $this->randomMachineName()));
    $user->save();

    // Add comment type.
    $this->container->get('entity.manager')->getStorage('comment_type')->create(array(
      'id' => 'comment',
      'label' => 'comment',
      'target_entity_type_id' => 'node',
    ))->save();

    $this->container->get('comment.manager')->addDefaultField('node', 'example_type');

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
      )
    ));
    $node->save();

    $parent_comment = entity_create('comment', array(
      'uid' => $user->id(),
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
      'uid' => $user->id(),
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
    unset($original_values['cid']);

    $normalized = $this->serializer->normalize($comment, $this->format);
    $denormalized_comment = $this->serializer->denormalize($normalized, 'Drupal\comment\Entity\Comment', $this->format);

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
