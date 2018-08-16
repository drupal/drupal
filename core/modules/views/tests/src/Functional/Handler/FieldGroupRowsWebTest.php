<?php

namespace Drupal\Tests\views\Functional\Handler;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\views\Functional\ViewTestBase;

/**
 * Tests the "Display all values in the same row" setting.
 *
 * @group views
 */
class FieldGroupRowsWebTest extends ViewTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_group_rows', 'test_ungroup_rows'];

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node'];

  /**
   * The page node type.
   *
   * @var \Drupal\node\NodeTypeInterface
   */
  protected $nodeType;

  /**
   * The used field name in the test.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * The field storage.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldStorage;

  /**
   * The field config.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    // Create content type with unlimited text field.
    $this->nodeType = $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);

    // Create the unlimited text field.
    $this->fieldName = 'field_views_testing_group_rows';
    $this->fieldStorage = FieldStorageConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => 'node',
      'type' => 'text',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ]);
    $this->fieldStorage->save();

    // Create an instance of the text field on the content type.
    $this->field = FieldConfig::create([
      'field_storage' => $this->fieldStorage,
      'bundle' => $this->nodeType->id(),
    ]);
    $this->field->save();

    $edit = [
      'title' => $this->randomMachineName(),
      $this->fieldName => ['a', 'b', 'c'],
    ];
    $this->drupalCreateNode($edit);
  }

  /**
   * Testing when "Display all values in the same row" is checked.
   */
  public function testGroupRows() {
    $this->drupalGet('test-group-rows');
    $result = $this->cssSelect('div.views-field-field-views-testing-group- div');

    $rendered_value = [];
    foreach ($result as $row) {
      $rendered_value[] = $row->getText();
    }
    $this->assertEqual(['a, b, c'], $rendered_value);
  }

  /**
   * Testing when "Display all values in the same row" is unchecked.
   */
  public function testUngroupedRows() {
    $this->drupalGet('test-ungroup-rows');
    $result = $this->cssSelect('div.views-field-field-views-testing-group- div');
    $rendered_value = [];
    foreach ($result as $row) {
      $rendered_value[] = $row->getText();
    }
    $this->assertEqual(['a', 'b', 'c'], $rendered_value);
  }

}
