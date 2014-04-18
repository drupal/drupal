<?php

/**
 * @file
 * Contains \Drupal\hal\Tests\NormalizeTest.
 */

namespace Drupal\hal\Tests;

/**
 * Test the HAL normalizer on various entities
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
  public static function getInfo() {
    return array(
      'name' => 'Entity normalizer Test',
      'description' => 'Test that nodes and terms are correctly normalized and denormalized.',
      'group' => 'HAL',
    );
  }

  /**
   * {@inheritdoc}
   */
  function setUp() {
    parent::setUp();

    \Drupal::service('router.builder')->rebuild();
    $this->installSchema('system', array('sequences'));
    $this->installSchema('node', array('node', 'node_field_data', 'node_revision', 'node_field_revision'));
    $this->installSchema('comment', array('comment', 'comment_entity_statistics'));
    $this->installSchema('user', array('users_roles'));
    $this->installSchema('taxonomy', array('taxonomy_term_data', 'taxonomy_term_hierarchy'));
  }

  /**
   * Tests the normalization of nodes.
   */
  public function testNode() {
    $node_type = entity_create('node_type', array('type' => 'example_type'));
    $node_type->save();

    $user = entity_create('user', array('name' => $this->randomName()));
    $user->save();

    $node = entity_create('node', array(
      'title' => $this->randomName(),
      'uid' => $user->id(),
      'type' => $node_type->id(),
      'status' => NODE_PUBLISHED,
      'promote' => 1,
      'sticky' => 0,
      'body' => array(
        'value' => $this->randomName(),
        'format' => $this->randomName(),
      )
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

    $term = entity_create('taxonomy_term', array(
      'name' => $this->randomName(),
      'vid' => $vocabulary->id(),
      'description' => array(
        'value' => $this->randomName(),
        'format' => $this->randomName(),
      )
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

    $user = entity_create('user', array('name' => $this->randomName()));
    $user->save();

    $node = entity_create('node', array(
      'title' => $this->randomName(),
      'uid' => $user->id(),
      'type' => $node_type->id(),
      'status' => NODE_PUBLISHED,
      'promote' => 1,
      'sticky' => 0,
      'body' => array(
        'value' => $this->randomName(),
        'format' => $this->randomName(),
      )
    ));
    $node->save();

    $this->container->get('comment.manager')->addDefaultField('node', 'example_type');

    $comment = entity_create('comment', array(
      'uid' => $user->id(),
      'subject' => $this->randomName(),
      'comment_body' => $this->randomName(),
      'entity_id' => $node->id(),
      'entity_type' => 'node',
      'field_name' => 'comment'
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
