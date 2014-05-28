<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Plugin\StyleUnformattedTest.
 */

namespace Drupal\views\Tests\Plugin;

use Drupal\views\Views;

/**
 * Tests the default/unformatted row style.
 */
class StyleUnformattedTest extends StyleTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view');

  public static function getInfo() {
    return array(
      'name' => 'Style: Unformatted',
      'description' => 'Test unformatted style functionality.',
      'group' => 'Views Plugins',
    );
  }

  /**
   * Make sure that the default css classes works as expected.
   */
  function testDefaultRowClasses() {
    $view = Views::getView('test_view');
    $view->setDisplay();
    $output = $view->preview();
    $this->storeViewPreview(drupal_render($output));

    $rows = $this->elements->body->div->div->div;
    $count = 0;
    $count_result = count($view->result);
    foreach ($rows as $row) {
      $count++;
      $attributes = $row->attributes();
      $class = (string) $attributes['class'][0];
      $this->assertTrue(strpos($class, 'views-row') !== FALSE, 'Make sure that the views row class is set right.');
    }
  }

}
