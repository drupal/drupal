<?php

namespace Drupal\Tests\views\Kernel\Plugin;

use Drupal\views\Views;
use Drupal\views\ViewExecutable;

/**
 * Tests mapping style functionality.
 *
 * @group views
 */
class StyleMappingTest extends StyleTestBase {

  protected static $modules = ['system'];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_style_mapping'];

  /**
   * Verifies that the fields were mapped correctly.
   */
  public function testMappedOutput() {
    $view = Views::getView('test_style_mapping');
    $output = $this->mappedOutputHelper($view);
    $this->assertStringNotContainsString('job', $output, 'The job field is added to the view but not in the mapping.');
    $view->destroy();

    $view->setDisplay();
    $view->displayHandlers->get('default')->options['style']['options']['mapping']['name_field'] = 'job';
    $output = $this->mappedOutputHelper($view);
    $this->assertStringContainsString('job', $output, 'The job field is added to the view and is in the mapping.');
  }

  /**
   * Tests the mapping of fields.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view to test.
   *
   * @return string
   *   The view rendered as HTML.
   */
  protected function mappedOutputHelper(ViewExecutable $view) {
    $output = $view->preview();
    $rendered_output = \Drupal::service('renderer')->renderRoot($output);
    $this->storeViewPreview($rendered_output);
    $rows = $this->elements->body->div->div;
    $data_set = $this->dataSet();

    $count = 0;
    foreach ($rows as $row) {
      $attributes = $row->attributes();
      $class = (string) $attributes['class'][0];
      $this->assertStringContainsString('views-row-mapping-test', $class, 'Make sure that each row has the correct CSS class.');

      foreach ($row->div as $field) {
        // Split up the field-level class, the first part is the mapping name
        // and the second is the field ID.
        $field_attributes = $field->attributes();
        $name = strtok((string) $field_attributes['class'][0], '-');
        $field_id = strtok('-');

        // The expected result is the mapping name and the field value,
        // separated by ':'.
        $expected_result = $name . ':' . $data_set[$count][$field_id];
        $actual_result = (string) $field;
        $this->assertSame($expected_result, $actual_result, "The fields were mapped successfully: $name => $field_id");
      }

      $count++;
    }

    return $rendered_output;
  }

}
