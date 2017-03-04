<?php

namespace Drupal\Tests\image\Kernel\Views;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;

/**
 * Tests image views data.
 *
 * @group image
 */
class ImageViewsDataTest extends ViewsKernelTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['image', 'file', 'views', 'entity_test', 'user', 'field'];

  /**
   * Tests views data generated for image field relationship.
   *
   * @see image_field_views_data()
   * @see image_field_views_data_views_data_alter()
   */
  public function testRelationshipViewsData() {
    // Create image field to entity_test.
    FieldStorageConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'field_base_image',
      'type' => 'image',
    ])->save();
    FieldConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'field_base_image',
      'bundle' => 'entity_test',
    ])->save();
    // Check the generated views data.
    $views_data = Views::viewsData()->get('entity_test__field_base_image');
    $relationship = $views_data['field_base_image_target_id']['relationship'];
    $this->assertEqual($relationship['id'], 'standard');
    $this->assertEqual($relationship['base'], 'file_managed');
    $this->assertEqual($relationship['base field'], 'fid');
    $this->assertEqual($relationship['entity type'], 'file');
    // Check the backwards reference.
    $views_data = Views::viewsData()->get('file_managed');
    $relationship = $views_data['reverse_field_base_image_entity_test']['relationship'];
    $this->assertEqual($relationship['id'], 'entity_reverse');
    $this->assertEqual($relationship['base'], 'entity_test');
    $this->assertEqual($relationship['base field'], 'id');
    $this->assertEqual($relationship['field table'], 'entity_test__field_base_image');
    $this->assertEqual($relationship['field field'], 'field_base_image_target_id');
    $this->assertEqual($relationship['field_name'], 'field_base_image');
    $this->assertEqual($relationship['entity_type'], 'entity_test');
    $this->assertEqual($relationship['join_extra'][0], ['field' => 'deleted', 'value' => 0, 'numeric' => TRUE]);

    // Create image field to entity_test_mul.
    FieldStorageConfig::create([
      'entity_type' => 'entity_test_mul',
      'field_name' => 'field_data_image',
      'type' => 'image',
    ])->save();
    FieldConfig::create([
      'entity_type' => 'entity_test_mul',
      'field_name' => 'field_data_image',
      'bundle' => 'entity_test_mul',
    ])->save();
    // Check the generated views data.
    $views_data = Views::viewsData()->get('entity_test_mul__field_data_image');
    $relationship = $views_data['field_data_image_target_id']['relationship'];
    $this->assertEqual($relationship['id'], 'standard');
    $this->assertEqual($relationship['base'], 'file_managed');
    $this->assertEqual($relationship['base field'], 'fid');
    $this->assertEqual($relationship['entity type'], 'file');
    // Check the backwards reference.
    $views_data = Views::viewsData()->get('file_managed');
    $relationship = $views_data['reverse_field_data_image_entity_test_mul']['relationship'];
    $this->assertEqual($relationship['id'], 'entity_reverse');
    $this->assertEqual($relationship['base'], 'entity_test_mul_property_data');
    $this->assertEqual($relationship['base field'], 'id');
    $this->assertEqual($relationship['field table'], 'entity_test_mul__field_data_image');
    $this->assertEqual($relationship['field field'], 'field_data_image_target_id');
    $this->assertEqual($relationship['field_name'], 'field_data_image');
    $this->assertEqual($relationship['entity_type'], 'entity_test_mul');
    $this->assertEqual($relationship['join_extra'][0], ['field' => 'deleted', 'value' => 0, 'numeric' => TRUE]);
  }

}
