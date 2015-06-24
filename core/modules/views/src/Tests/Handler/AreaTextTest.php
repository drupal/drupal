<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Handler\AreaTextTest.
 */

namespace Drupal\views\Tests\Handler;

use Drupal\views\Tests\ViewUnitTestBase;
use Drupal\views\Views;

/**
 * Tests the core views_handler_area_text handler.
 *
 * @group views
 * @see \Drupal\views\Plugin\views\area\Text
 */
class AreaTextTest extends ViewUnitTestBase {

  public static $modules = array('system', 'user', 'filter');

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view');

  protected function setUp() {
    parent::setUp();

    $this->installConfig(array('system', 'filter'));
    $this->installEntitySchema('user');
  }

  public function testAreaText() {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');
    $view = Views::getView('test_view');
    $view->setDisplay();

    // add a text header
    $string = $this->randomMachineName();
    $view->displayHandlers->get('default')->overrideOption('header', array(
      'area' => array(
        'id' => 'area',
        'table' => 'views',
        'field' => 'area',
        'content' => array(
          'value' => $string,
        ),
      ),
    ));

    // Execute the view.
    $this->executeView($view);

    $view->display_handler->handlers['header']['area']->options['content']['format'] = $this->randomString();
    $build = $view->display_handler->handlers['header']['area']->render();
    $this->assertEqual('', $renderer->renderRoot($build), 'Nonexistent format should return empty markup.');

    $view->display_handler->handlers['header']['area']->options['content']['format'] = filter_default_format();
    $build = $view->display_handler->handlers['header']['area']->render();
    $this->assertEqual(check_markup($string), $renderer->renderRoot($build), 'Existent format should return something');

    // Empty results, and it shouldn't be displayed .
    $this->assertEqual(array(), $view->display_handler->handlers['header']['area']->render(TRUE), 'No result should lead to no header');
    // Empty results, and it should be displayed.
    $view->display_handler->handlers['header']['area']->options['empty'] = TRUE;
    $build = $view->display_handler->handlers['header']['area']->render(TRUE);
    $this->assertEqual(check_markup($string), $renderer->renderRoot($build), 'No result, but empty enabled lead to a full header');
  }

}
