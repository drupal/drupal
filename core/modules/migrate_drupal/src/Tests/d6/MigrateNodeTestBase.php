<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateNodeTestBase.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Base class for Node migration tests.
 */
abstract class MigrateNodeTestBase extends MigrateDrupalTestBase {

  static $modules = array('node');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $node_type = entity_create('node_type', array('type' => 'story'));
    $node_type->save();
    node_add_body_field($node_type);

    $id_mappings = array(
      'd6_node_type' => array(
        array(array('test_story'), array('story')),
      ),
      'd6_filter_format' => array(
        array(array(1), array('filtered_html')),
        array(array(2), array('full_html')),
      ),
    );
    $this->prepareIdMappings($id_mappings);

    // Create a test node.
    $node = entity_create('node', array(
      'type' => 'story',
      'nid' => 1,
      'vid' => 1,
    ));
    $node->enforceIsNew();
    $node->save();

    // Load dumps.
    $dumps = array(
      $this->getDumpDirectory() . '/Drupal6Node.php',
      $this->getDumpDirectory() . '/Drupal6NodeType.php',
      $this->getDumpDirectory() . '/Drupal6FieldInstance.php',
    );
    $this->loadDumps($dumps);
  }

}
