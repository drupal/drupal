<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Kernel\Handler;

use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;

/**
 * Tests the core Drupal\views\Plugin\views\field\FileSize handler.
 *
 * @group views
 * @see CommonXssUnitTest
 */
class FieldFileSizeTest extends ViewsKernelTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_view'];

  public function dataSet() {
    $data = parent::dataSet();
    $data[0]['age'] = 0;
    $data[1]['age'] = 10;
    $data[2]['age'] = 1000;
    $data[3]['age'] = 10000;

    return $data;
  }

  public function viewsData() {
    $data = parent::viewsData();
    $data['views_test_data']['age']['field']['id'] = 'file_size';

    return $data;
  }

  public function testFieldFileSize(): void {
    $view = Views::getView('test_view');
    $view->setDisplay();

    $view->displayHandlers->get('default')->overrideOption('fields', [
      'age' => [
        'id' => 'age',
        'table' => 'views_test_data',
        'field' => 'age',
      ],
    ]);

    $this->executeView($view);

    // Test with the formatted option.
    $this->assertEquals('', $view->field['age']->advancedRender($view->result[0]));
    $this->assertEquals('10 bytes', $view->field['age']->advancedRender($view->result[1]));
    $this->assertEquals('1000 bytes', $view->field['age']->advancedRender($view->result[2]));
    $this->assertEquals('9.77 KB', $view->field['age']->advancedRender($view->result[3]));
    // Test with the bytes option.
    $view->field['age']->options['file_size_display'] = 'bytes';
    $this->assertEquals('', $view->field['age']->advancedRender($view->result[0]));
    $this->assertEquals('10', $view->field['age']->advancedRender($view->result[1]));
    $this->assertEquals('1000', $view->field['age']->advancedRender($view->result[2]));
    $this->assertEquals('10000', $view->field['age']->advancedRender($view->result[3]));
  }

}
