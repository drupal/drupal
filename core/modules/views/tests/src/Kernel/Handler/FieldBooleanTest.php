<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Kernel\Handler;

use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;

/**
 * Tests the core Drupal\views\Plugin\views\field\Boolean handler.
 *
 * @group views
 */
class FieldBooleanTest extends ViewsKernelTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_view'];

  /**
   * Modifies the default dataset by removing the age for specific entries.
   */
  public function dataSet() {
    // Use default dataset but remove the age from john and paul
    $data = parent::dataSet();
    $data[0]['age'] = 0;
    $data[3]['age'] = 0;
    return $data;
  }

  /**
   * Provides Views data definition for the 'age' field as a boolean.
   */
  public function viewsData() {
    $data = parent::viewsData();
    $data['views_test_data']['age']['field']['id'] = 'boolean';
    return $data;
  }

  /**
   * Tests different display formats for a boolean field in Views.
   */
  public function testFieldBoolean(): void {
    $view = Views::getView('test_view');
    $view->setDisplay();

    $view->displayHandlers->get('default')->overrideOption('fields', [
      'age' => [
        'id' => 'age',
        'table' => 'views_test_data',
        'field' => 'age',
        'relationship' => 'none',
      ],
    ]);

    $this->executeView($view);

    // This is john, which has no age, there are no custom formats defined, yet.
    $this->assertEquals('No', $view->field['age']->advancedRender($view->result[0]));
    $this->assertEquals('Yes', $view->field['age']->advancedRender($view->result[1]));

    // Reverse the output.
    $view->field['age']->options['not'] = TRUE;
    $this->assertEquals('Yes', $view->field['age']->advancedRender($view->result[0]));
    $this->assertEquals('No', $view->field['age']->advancedRender($view->result[1]));

    unset($view->field['age']->options['not']);

    // Use another output format.
    $view->field['age']->options['type'] = 'true-false';
    $this->assertEquals('False', $view->field['age']->advancedRender($view->result[0]));
    $this->assertEquals('True', $view->field['age']->advancedRender($view->result[1]));

    // Test awesome unicode.
    $view->field['age']->options['type'] = 'unicode-yes-no';
    $this->assertEquals('✖', $view->field['age']->advancedRender($view->result[0]));
    $this->assertEquals('✔', $view->field['age']->advancedRender($view->result[1]));

    // Set a custom output format.
    $view->field['age']->formats['test'] = ['Test-True', 'Test-False'];
    $view->field['age']->options['type'] = 'test';
    $this->assertEquals('Test-False', $view->field['age']->advancedRender($view->result[0]));
    $this->assertEquals('Test-True', $view->field['age']->advancedRender($view->result[1]));
  }

}
