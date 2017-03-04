<?php

namespace Drupal\views\Tests\Handler;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\field\Entity\FieldConfig;
use Drupal\views\Views;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests the "Display all values in the same row" setting.
 *
 * @see \Drupal\views\Plugin\views\field\EntityField
 *
 * @group views
 */
class FieldGroupRowsTest extends HandlerTestBase {

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
  public static $modules = ['node', 'field_test'];

  /**
   * Field that will be created to test the group/ungroup rows functionality
   *
   * @var string
   */
  private $fieldName = 'field_group_rows';

  protected function setUp() {
    parent::setUp();

    // Create content type with unlimited text field.
    $node_type = $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);

    // Create the unlimited text field.
    $field_storage = FieldStorageConfig::create([
        'field_name' => $this->fieldName,
        'entity_type' => 'node',
        'type' => 'text',
        'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      ]);
    $field_storage->save();

    // Create an instance of the text field on the content type.
    $field = [
      'field_storage' => $field_storage,
      'bundle' => $node_type->id(),
    ];
    FieldConfig::create($field)->save();
  }

  /**
   * Testing the "Grouped rows" functionality.
   */
  public function testGroupRows() {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');

    $edit = [
      'title' => $this->randomMachineName(),
      $this->fieldName => ['a', 'b', 'c'],
    ];
    $this->drupalCreateNode($edit);

    $view = Views::getView('test_group_rows');

    // Test grouped rows.
    $this->executeView($view);
    $output = $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field[$this->fieldName]->advancedRender($view->result[0]);
    });
    $this->assertEqual($output, 'a, b, c');

    // Change the group_rows checkbox to false.
    $view = Views::getView('test_group_rows');
    $view->setHandlerOption('default', 'field', $this->fieldName, 'group_rows', FALSE);

    // Test ungrouped rows.
    $this->executeView($view);
    $view->render();

    $view->row_index = 0;
    $output = $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field[$this->fieldName]->advancedRender($view->result[0]);
    });
    $this->assertEqual($output, 'a');
    $view->row_index = 1;
    $output = $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field[$this->fieldName]->advancedRender($view->result[1]);
    });
    $this->assertEqual($output, 'b');
    $view->row_index = 2;
    $output = $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field[$this->fieldName]->advancedRender($view->result[2]);
    });
    $this->assertEqual($output, 'c');
  }

}
