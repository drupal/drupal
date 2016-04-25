<?php

namespace Drupal\Tests\views\Kernel\Handler;

use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;

/**
 * Tests the Drupal\views\Plugin\views\field\Counter handler.
 *
 * @group views
 */
class FieldCounterTest extends ViewsKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('user');

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view');

  function testSimple() {
    $view = Views::getView('test_view');
    $view->setDisplay();
    $view->displayHandlers->get('default')->overrideOption('fields', array(
      'counter' => array(
        'id' => 'counter',
        'table' => 'views',
        'field' => 'counter',
        'relationship' => 'none',
      ),
      'name' => array(
        'id' => 'name',
        'table' => 'views_test_data',
        'field' => 'name',
        'relationship' => 'none',
      ),
    ));
    $view->preview();

    $counter = $view->style_plugin->getField(0, 'counter');
    $this->assertEqual($counter, '1', format_string('Make sure the expected number (@expected) patches with the rendered number (@counter)', array('@expected' => 1, '@counter' => $counter)));
    $counter = $view->style_plugin->getField(1, 'counter');
    $this->assertEqual($counter, '2', format_string('Make sure the expected number (@expected) patches with the rendered number (@counter)', array('@expected' => 2, '@counter' => $counter)));
    $counter = $view->style_plugin->getField(2, 'counter');
    $this->assertEqual($counter, '3', format_string('Make sure the expected number (@expected) patches with the rendered number (@counter)', array('@expected' => 3, '@counter' => $counter)));
    $view->destroy();
    $view->storage->invalidateCaches();

    $view->setDisplay();
    $rand_start = rand(5, 10);
    $view->displayHandlers->get('default')->overrideOption('fields', array(
      'counter' => array(
        'id' => 'counter',
        'table' => 'views',
        'field' => 'counter',
        'relationship' => 'none',
        'counter_start' => $rand_start
      ),
      'name' => array(
        'id' => 'name',
        'table' => 'views_test_data',
        'field' => 'name',
        'relationship' => 'none',
      ),
    ));
    $view->preview();

    $counter = $view->style_plugin->getField(0, 'counter');
    $expected_number = 0 + $rand_start;
    $this->assertEqual($counter, (string) $expected_number, format_string('Make sure the expected number (@expected) patches with the rendered number (@counter)', array('@expected' => $expected_number, '@counter' => $counter)));
    $counter = $view->style_plugin->getField(1, 'counter');
    $expected_number = 1 + $rand_start;
    $this->assertEqual($counter, (string) $expected_number, format_string('Make sure the expected number (@expected) patches with the rendered number (@counter)', array('@expected' => $expected_number, '@counter' => $counter)));
    $counter = $view->style_plugin->getField(2, 'counter');
    $expected_number = 2 + $rand_start;
    $this->assertEqual($counter, (string) $expected_number, format_string('Make sure the expected number (@expected) patches with the rendered number (@counter)', array('@expected' => $expected_number, '@counter' => $counter)));
  }

  // @TODO: Write tests for pager.
  function testPager() {
  }

}
