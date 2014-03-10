<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Handler\FieldXssTest.
 */

namespace Drupal\views\Tests\Handler;

use Drupal\views\Tests\ViewUnitTestBase;
use Drupal\views\Views;

/**
 * Tests the core Drupal\views\Plugin\views\field\Xss handler.
 *
 * @see CommonXssUnitTest
 */
class FieldXssTest extends ViewUnitTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view');

  public static function getInfo() {
    return array(
      'name' => 'Field: XSS',
      'description' => 'Test the core Drupal\views\Plugin\views\field\Xss handler.',
      'group' => 'Views Handlers',
    );
  }

  function dataHelper() {
    $map = array(
      'John' => 'John',
      "Foo\xC0barbaz" => '',
      'Fooÿñ' => 'Fooÿñ'
    );

    return $map;
  }


  function viewsData() {
    $data = parent::viewsData();
    $data['views_test_data']['name']['field']['id'] = 'xss';

    return $data;
  }

  public function testFieldXss() {
    $view = Views::getView('test_view');
    $view->setDisplay();

    $view->displayHandlers->get('default')->overrideOption('fields', array(
      'name' => array(
        'id' => 'name',
        'table' => 'views_test_data',
        'field' => 'name',
      ),
    ));

    $this->executeView($view);

    $counter = 0;
    foreach ($this->dataHelper() as $input => $expected_result) {
      $view->result[$counter]->views_test_data_name = $input;
      $this->assertEqual($view->field['name']->advancedRender($view->result[$counter]), $expected_result);
      $counter++;
    }
  }

}
