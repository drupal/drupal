<?php

/**
 * @file
 * Contains \Drupal\views\Tests\Handler\FieldUrlTest.
 */

namespace Drupal\views\Tests\Handler;

use Drupal\Core\Url;
use Drupal\views\Tests\ViewKernelTestBase;
use Drupal\views\Views;

/**
 * Tests the core Drupal\views\Plugin\views\field\Url handler.
 *
 * @group views
 */
class FieldUrlTest extends ViewKernelTestBase {

  public static $modules = array('system');

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view');

  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', 'url_alias');
  }

  function viewsData() {
    $data = parent::viewsData();
    $data['views_test_data']['name']['field']['id'] = 'url';
    return $data;
  }

  public function testFieldUrl() {
    $view = Views::getView('test_view');
    $view->setDisplay();

    $view->displayHandlers->get('default')->overrideOption('fields', array(
      'name' => array(
        'id' => 'name',
        'table' => 'views_test_data',
        'field' => 'name',
        'relationship' => 'none',
        'display_as_link' => FALSE,
      ),
    ));

    $this->executeView($view);

    $this->assertEqual('John', $view->field['name']->advancedRender($view->result[0]));

    // Make the url a link.
    $view->destroy();
    $view->setDisplay();

    $view->displayHandlers->get('default')->overrideOption('fields', array(
      'name' => array(
        'id' => 'name',
        'table' => 'views_test_data',
        'field' => 'name',
        'relationship' => 'none',
      ),
    ));

    $this->executeView($view);

    $this->assertEqual(\Drupal::l('John', Url::fromUri('base:John')), $view->field['name']->advancedRender($view->result[0]));
  }

}
