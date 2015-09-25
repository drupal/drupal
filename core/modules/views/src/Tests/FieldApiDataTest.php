<?php

/**
 * @file
 * Contains \Drupal\views\Tests\FieldApiDataTest.
 */

namespace Drupal\views\Tests;

use Drupal\Component\Utility\SafeStringInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Tests\Views\FieldTestBase;

/**
 * Tests the Field Views data.
 *
 * @group views
 */
class FieldApiDataTest extends FieldTestBase {

  protected function setUp() {
    parent::setUp();

    $field_names = $this->setUpFieldStorages(1);

    // Attach the field to nodes only.
    $field = array(
      'field_name' => $field_names[0],
      'entity_type' => 'node',
      'bundle' => 'page',
      'label' => 'The giraffe" label'
    );
    entity_create('field_config', $field)->save();

    // Attach the same field to a different bundle with a different label.
    $this->drupalCreateContentType(['type' => 'article']);
    FieldConfig::create([
      'field_name' => $field_names[0],
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => 'The giraffe2" label'
    ])->save();

    // Now create some example nodes/users for the view result.
    for ($i = 0; $i < 5; $i++) {
      $edit = array(
        $field_names[0] => array((array('value' => $this->randomMachineName()))),
      );
      $nodes[] = $this->drupalCreateNode($edit);
    }
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
    $field_storage = $this->fieldStorages[0];
    /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
    $table_mapping = \Drupal::entityManager()->getStorage('node')->getTableMapping();
    $current_table = $table_mapping->getDedicatedDataTableName($field_storage);
    $revision_table = $table_mapping->getDedicatedRevisionTableName($field_storage);
    $data[$current_table] = $views_data->get($current_table);
    $data[$revision_table] = $views_data->get($revision_table);

    $this->assertTrue(isset($data[$current_table]));
    $this->assertTrue(isset($data[$revision_table]));
    // The node field should join against node_field_data.
    $this->assertTrue(isset($data[$current_table]['table']['join']['node_field_data']));
    $this->assertTrue(isset($data[$revision_table]['table']['join']['node_field_revision']));

    $expected_join = array(
      'left_field' => 'nid',
      'field' => 'entity_id',
      'extra' => array(
        array('field' => 'deleted', 'value' => 0, 'numeric' => TRUE),
        array('left_field' => 'langcode', 'field' => 'langcode'),
      ),
    );
    $this->assertEqual($expected_join, $data[$current_table]['table']['join']['node_field_data']);
    $expected_join = array(
      'left_field' => 'vid',
      'field' => 'revision_id',
      'extra' => array(
        array('field' => 'deleted', 'value' => 0, 'numeric' => TRUE),
        array('left_field' => 'langcode', 'field' => 'langcode'),
      ),
    );
    $this->assertEqual($expected_join, $data[$revision_table]['table']['join']['node_field_revision']);

    // Test click sortable.
    $this->assertTrue($data[$current_table][$field_storage->getName()]['field']['click sortable'], 'String field is click sortable.');
    // Click sort should only be on the primary field.
    $this->assertTrue(empty($data[$revision_table][$field_storage->getName()]['field']['click sortable']), 'Non-primary fields are not click sortable');

    $this->assertTrue($data[$current_table][$field_storage->getName()]['help'] instanceof SafeStringInterface);
    $this->assertEqual($data[$current_table][$field_storage->getName()]['help'], 'Appears in: page, article. Also known as: Content: The giraffe2&quot; label');

    $this->assertTrue($data[$current_table][$field_storage->getName() . '_value']['help'] instanceof SafeStringInterface);
    $this->assertEqual($data[$current_table][$field_storage->getName() . '_value']['help'], 'Appears in: page, article. Also known as: Content: The giraffe&quot; label (field_name_0)');
  }

}
