<?php

/**
 * @file
 * Contains \Drupal\entity_reference\Tests\EntityReferenceItemTest.
 */

namespace Drupal\entity_reference\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\entity_reference\Type\EntityReferenceItem;
use Drupal\Core\Entity\Field\FieldItemInterface;
use Drupal\Core\Entity\Field\FieldInterface;

/**
 * Tests the new entity API for the entity reference field type.
 */
class EntityReferenceItemTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('field', 'field_sql_storage', 'entity_test', 'options', 'entity_reference');

  public static function getInfo() {
    return array(
      'name' => 'Entity Reference field API',
      'description' => 'Tests using entity fields of the entity reference field type.',
      'group' => 'Entity Reference',
    );
  }

  public function setUp() {
    parent::setUp();

    $field = array(
      'translatable' => FALSE,
      'entity_types' => array(),
      'settings' => array(
        'target_type' => 'node',
      ),
      'field_name' => 'field_test',
      'type' => 'entity_reference',
      'cardinality' => FIELD_CARDINALITY_UNLIMITED,
    );

    field_create_field($field);

    $instance = array(
      'entity_type' => 'entity_test',
      'field_name' => 'field_test',
      'bundle' => 'entity_test',
      'widget' => array(
        'type' => 'options_select',
      ),
      'settings' => array(
        'handler' => 'default',
        'handler_settings' => array(),
      ),
    );
    field_create_instance($instance);
  }

  /**
   * Tests using entity fields of the taxonomy term reference field type.
   */
  public function testEntityReferenceItem() {
    // Create a node.
    $node1 = $this->drupalCreateNode();
    $nid = $node1->id();

    // Just being able to create the entity like this verifies a lot of code.
    $entity = entity_create('entity_test', array('name' => 'foo'));
    $entity->field_test->target_id = $nid;
    $entity->save();

    $this->assertTrue($entity->field_test instanceof FieldInterface, 'Field implements interface.');
    $this->assertTrue($entity->field_test[0] instanceof FieldItemInterface, 'Field item implements interface.');
    $this->assertEqual($entity->field_test->target_id, $nid);
    $this->assertEqual($entity->field_test->entity->title, $node1->label());
    $this->assertEqual($entity->field_test->entity->id(), $nid);
    $this->assertEqual($entity->field_test->entity->uuid(), $node1->uuid());

    // Change the name of the term via the reference.
    $new_name = $this->randomName();
    $entity->field_test->entity->title = $new_name;
    $entity->field_test->entity->save();

    // Verify it is the correct name.
    $node = node_load($nid);
    $this->assertEqual($node->label(), $new_name);

    // Make sure the computed node reflects updates to the node id.
    $node2 = $this->drupalCreateNode();

    $entity->field_test->target_id = $node2->nid;
    $this->assertEqual($entity->field_test->entity->id(), $node2->id());
    $this->assertEqual($entity->field_test->entity->title, $node2->label());
  }
}
