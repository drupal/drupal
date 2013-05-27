<?php

/**
 * @file
 * Contains \Drupal\field\Tests\Views\ApiDataTest.
 */

namespace Drupal\field\Tests\Views;

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

    $field_names = $this->setUpFields();

    // The first one will be attached to nodes only.
    $instance = array(
      'field_name' => $field_names[0],
      'entity_type' => 'node',
      'bundle' => 'page',
    );
    field_create_instance($instance);

    // The second one will be attached to users only.
    $instance = array(
      'field_name' => $field_names[1],
      'entity_type' => 'user',
      'bundle' => 'user',
    );
    field_create_instance($instance);

    // The third will be attached to both nodes and users.
    $instance = array(
      'field_name' => $field_names[2],
      'entity_type' => 'node',
      'bundle' => 'page',
    );
    field_create_instance($instance);
    $instance = array(
      'field_name' => $field_names[2],
      'entity_type' => 'user',
      'bundle' => 'user',
    );
    field_create_instance($instance);

    // Now create some example nodes/users for the view result.
    for ($i = 0; $i < 5; $i++) {
      $edit = array(
        'field_name_0' => array((array('value' => $this->randomName()))),
        'field_name_2' => array((array('value' => $this->randomName()))),
      );
      $this->nodes[] = $this->drupalCreateNode($edit);
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
    $current_table = _field_sql_storage_tablename($field);
    $revision_table = _field_sql_storage_revision_tablename($field);
    $data[$current_table] = $views_data->get($current_table);
    $data[$revision_table] = $views_data->get($revision_table);

    $this->assertTrue(isset($data[$current_table]));
    $this->assertTrue(isset($data[$revision_table]));
    // The node field should join against node.
    $this->assertTrue(isset($data[$current_table]['table']['join']['node']));
    $this->assertTrue(isset($data[$revision_table]['table']['join']['node_field_revision']));

    $expected_join = array(
      'left_field' => 'nid',
      'field' => 'entity_id',
      'extra' => array(
        array('field' => 'entity_type', 'value' => 'node'),
        array('field' => 'deleted', 'value' => 0, 'numeric' => TRUE),
      ),
    );
    $this->assertEqual($expected_join, $data[$current_table]['table']['join']['node']);
    $expected_join = array(
      'left_field' => 'vid',
      'field' => 'revision_id',
      'extra' => array(
        array('field' => 'entity_type', 'value' => 'node'),
        array('field' => 'deleted', 'value' => 0, 'numeric' => TRUE),
      ),
    );
    $this->assertEqual($expected_join, $data[$revision_table]['table']['join']['node_field_revision']);

    // Check the table and the joins of the second field.
    // Attached to both node and user.
    $field_2 = $this->fields[2];
    $current_table_2 = _field_sql_storage_tablename($field_2);
    $revision_table_2 = _field_sql_storage_revision_tablename($field_2);
    $data[$current_table_2] = $views_data->get($current_table_2);
    $data[$revision_table_2] = $views_data->get($revision_table_2);

    $this->assertTrue(isset($data[$current_table_2]));
    $this->assertTrue(isset($data[$revision_table_2]));
    // The second field should join against both node and users.
    $this->assertTrue(isset($data[$current_table_2]['table']['join']['node']));
    $this->assertTrue(isset($data[$revision_table_2]['table']['join']['node_field_revision']));
    $this->assertTrue(isset($data[$current_table_2]['table']['join']['users']));

    $expected_join = array(
      'left_field' => 'nid',
      'field' => 'entity_id',
      'extra' => array(
        array('field' => 'entity_type', 'value' => 'node'),
        array('field' => 'deleted', 'value' => 0, 'numeric' => TRUE),
      )
    );
    $this->assertEqual($expected_join, $data[$current_table_2]['table']['join']['node']);
    $expected_join = array(
      'left_field' => 'vid',
      'field' => 'revision_id',
      'extra' => array(
        array('field' => 'entity_type', 'value' => 'node'),
        array('field' => 'deleted', 'value' => 0, 'numeric' => TRUE),
      )
    );
    $this->assertEqual($expected_join, $data[$revision_table_2]['table']['join']['node_field_revision']);
    $expected_join = array(
      'left_field' => 'uid',
      'field' => 'entity_id',
      'extra' => array(
        array('field' => 'entity_type', 'value' => 'user'),
        array('field' => 'deleted', 'value' => 0, 'numeric' => TRUE),
      )
    );
    $this->assertEqual($expected_join, $data[$current_table_2]['table']['join']['users']);
  }

}
