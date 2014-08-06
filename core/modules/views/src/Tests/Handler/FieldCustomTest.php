<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Handler\FieldCustomTest.
 */

namespace Drupal\views\Tests\Handler;

use Drupal\views\Tests\ViewUnitTestBase;
use Drupal\views\Views;

/**
 * Tests the core Drupal\views\Plugin\views\field\Custom handler.
 *
 * @group views
 */
class FieldCustomTest extends ViewUnitTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view');

  function viewsData() {
    $data = parent::viewsData();
    $data['views_test_data']['name']['field']['id'] = 'custom';
    return $data;
  }

  public function testFieldCustom() {
    $view = Views::getView('test_view');
    $view->setDisplay();

    // Alter the text of the field to a random string.
    $random = $this->randomMachineName();
    $view->displayHandlers->get('default')->overrideOption('fields', array(
      'name' => array(
        'id' => 'name',
        'table' => 'views_test_data',
        'field' => 'name',
        'relationship' => 'none',
        'alter' => array(
          'text' => $random,
        ),
      ),
    ));

    $this->executeView($view);

    $this->assertEqual($random, $view->style_plugin->getField(0, 'name'));
  }

}
