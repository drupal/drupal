<?php

namespace Drupal\views\Tests;

use Drupal\Component\Render\MarkupInterface;
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
      'label' => 'GiraffeA" label'
    );
    FieldConfig::create($field)->save();

    // Attach the same field to a different bundle with a different label.
    $this->drupalCreateContentType(['type' => 'article']);
    FieldConfig::create([
      'field_name' => $field_names[0],
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => 'GiraffeB" label'
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
    $table_mapping = \Drupal::entityManager()->getStorage('node')->getTableMapping();
    $field_storage = $this->fieldStorages[0];
    $current_table = $table_mapping->getDedicatedDataTableName($field_storage);
    $revision_table = $table_mapping->getDedicatedRevisionTableName($field_storage);
    $data = $this->getViewsData();

    $this->assertTrue(isset($data[$current_table]));
    $this->assertTrue(isset($data[$revision_table]));
    // The node field should join against node_field_data.
    $this->assertTrue(isset($data[$current_table]['table']['join']['node_field_data']));
    $this->assertTrue(isset($data[$revision_table]['table']['join']['node_field_revision']));

    $expected_join = array(
      'table' => $current_table,
      'left_field' => 'nid',
      'field' => 'entity_id',
      'extra' => array(
        array('field' => 'deleted', 'value' => 0, 'numeric' => TRUE),
        array('left_field' => 'langcode', 'field' => 'langcode'),
      ),
    );
    $this->assertEqual($expected_join, $data[$current_table]['table']['join']['node_field_data']);
    $expected_join = array(
      'table' => $revision_table,
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

    $this->assertTrue($data[$current_table][$field_storage->getName()]['help'] instanceof MarkupInterface);
    $this->assertEqual($data[$current_table][$field_storage->getName()]['help'], 'Appears in: page, article. Also known as: Content: GiraffeB&quot; label');

    $this->assertTrue($data[$current_table][$field_storage->getName() . '_value']['help'] instanceof MarkupInterface);
    $this->assertEqual($data[$current_table][$field_storage->getName() . '_value']['help'], 'Appears in: page, article. Also known as: Content: GiraffeA&quot; label (field_name_0)');

    // Since each label is only used once, views_entity_field_label() will
    // return a label using alphabetical sorting.
    $this->assertEqual('GiraffeA&quot; label (field_name_0)', $data[$current_table][$field_storage->getName() . '_value']['title']);

    // Attach the same field to a different bundle with a different label.
    $this->drupalCreateContentType(['type' => 'news']);
    FieldConfig::create([
      'field_name' => $this->fieldStorages[0]->getName(),
      'entity_type' => 'node',
      'bundle' => 'news',
      'label' => 'GiraffeB" label'
    ])->save();
    $this->container->get('views.views_data')->clear();
    $data = $this->getViewsData();

    // Now the 'GiraffeB&quot; label' is used twice and therefore will be
    // selected by views_entity_field_label().
    $this->assertEqual('GiraffeB&quot; label (field_name_0)', $data[$current_table][$field_storage->getName() . '_value']['title']);
    $this->assertTrue($data[$current_table][$field_storage->getName()]['help'] instanceof MarkupInterface);
    $this->assertEqual($data[$current_table][$field_storage->getName()]['help'], 'Appears in: page, article, news. Also known as: Content: GiraffeA&quot; label');
  }

  /**
   * Gets the views data for the field created in setUp().
   *
   * @return array
   */
  protected function getViewsData() {
    $views_data = $this->container->get('views.views_data');
    $data = array();

    // Check the table and the joins of the first field.
    // Attached to node only.
    /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
    $table_mapping = \Drupal::entityManager()->getStorage('node')->getTableMapping();
    $current_table = $table_mapping->getDedicatedDataTableName($this->fieldStorages[0]);
    $revision_table = $table_mapping->getDedicatedRevisionTableName($this->fieldStorages[0]);
    $data[$current_table] = $views_data->get($current_table);
    $data[$revision_table] = $views_data->get($revision_table);
    return $data;
  }

}
