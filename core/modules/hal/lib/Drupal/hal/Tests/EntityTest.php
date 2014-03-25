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
  public static $modules = array('node', 'taxonomy');

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

}
