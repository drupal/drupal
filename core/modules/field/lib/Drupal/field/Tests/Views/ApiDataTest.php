<?php

/**
 * @file
 * Contains \Drupal\field\Tests\Views\ApiDataTest.
 */

namespace Drupal\field\Tests\Views;
use Drupal\Core\Entity\FieldableDatabaseStorageController;

/**
 * Test the produced views_data.
 */
class ApiDataTest extends FieldTestBase {

  /**
   * Stores the fields for this test case.
   */
  var $fields;

  public static function getInfo() {
    return array(
      'name' => 'Field: Views Data',
      'description' => 'Tests the Field Views data.',
      'group' => 'Views module integration',
    );
  }

  function setUp() {
    parent::setUp();

    $field_names = $this->setUpFields(1);

    // Attach the field to nodes only.
    $instance = array(
      'field_name' => $field_names[0],
      'entity_type' => 'node',
      'bundle' => 'page',
    );
    entity_create('field_instance_config', $instance)->save();

    // Now create some example nodes/users for the view result.
    for ($i = 0; $i < 5; $i++) {
      $edit = array(
        $field_names[0] => array((array('value' => $this->randomName()))),
      );
      $nodes[] = $this->drupalCreateNode($edit);
    }

    $this->container->get('views.views_data')->clear();
  }

  /**
   * Unit testing the views data structure.
   *
   * We check data structure for both node and node revision tables.
   */
  function testViewsData() {
    $views_data = $this->container->get('views.views_data');
    $data = array();

    // Check the table and the joins of the first field.
    // Attached to node only.
    $field = $this->fields[0];
    $current_table = FieldableDatabaseStorageController::_fieldTableName($field);
    $revision_table = FieldableDatabaseStorageController::_fieldRevisionTableName($field);
    $data[$current_table] = $views_data->get($current_table);
    $data[$revision_table] = $views_data->get($revision_table);

    $this->assertTrue(isset($data[$current_table]));
    $this->assertTrue(isset($data[$revision_table]));
    // The node field should join against node.
    $this->assertTrue(isset($data[$current_table]['table']['join']['node']));
    $this->assertTrue(isset($data[$revision_table]['table']['join']['node_revision']));

    $expected_join = array(
      'left_field' => 'nid',
      'field' => 'entity_id',
      'extra' => array(
        array('field' => 'deleted', 'value' => 0, 'numeric' => TRUE),
      ),
    );
    $this->assertEqual($expected_join, $data[$current_table]['table']['join']['node']);
    $expected_join = array(
      'left_field' => 'vid',
      'field' => 'revision_id',
      'extra' => array(
        array('field' => 'deleted', 'value' => 0, 'numeric' => TRUE),
      ),
    );
    $this->assertEqual($expected_join, $data[$revision_table]['table']['join']['node_revision']);
  }

}
