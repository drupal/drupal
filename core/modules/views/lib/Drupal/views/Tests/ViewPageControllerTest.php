<?php

/**
 * @file
 * Contains \Drupal\views\Tests\ViewPageControllerTest.
 */

namespace Drupal\views\Tests;

use Drupal\views\Routing\ViewPageController;
use Drupal\views\ViewExecutableFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Tests the page controller but not the actualy execution/rendering of a view.
 *
 * @see \Drupal\views\Routing\ViewPageController
 */
class ViewPageControllerTest extends ViewUnitTestBase {

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
  public static $testViews = array('test_page_view');

  /**
   * The page controller of views.
   *
   * @var \Drupal\views\Routing\ViewPageController
   */
  public $pageController;

  public static function getInfo() {
    return array(
      'name' => 'View page controller test',
      'description' => 'Tests views page controller.',
      'group' => 'Views'
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', 'menu_router');
    $this->installSchema('user', 'role_permission');

    $this->pageController = new ViewPageController($this->container->get('plugin.manager.entity')->getStorageController('view'), new ViewExecutableFactory());
  }

  /**
   * Tests the page controller.
   */
  public function testPageController() {
    $this->assertTrue($this->pageController instanceof ViewPageController, 'Ensure the right class is stored in the container');

    // Pass in a non existent view.
    $random_view_id = $this->randomName();

    $request = new Request();
    $request->attributes->set('view_id', $random_view_id);
    $request->attributes->set('display_id', 'default');
    try {
      $this->pageController->handle($request);
      $this->fail('No exception thrown on non-existing view.');
    }

    catch (NotFoundHttpException $e) {
      $this->pass('Exception thrown when view was not found');
    }

    $request->attributes->set('view_id', 'test_page_view');
    $output = $this->pageController->handle($request);
    $this->assertTrue(is_array($output));
    $this->assertEqual($output['#view']->storage->id, 'test_page_view', 'The right view was executed.');

    $request->attributes->set('display_id', 'page_1');
    $output = $this->pageController->handle($request);
    $this->assertTrue($output instanceof Response, 'Ensure the page display returns a response object.');
  }

}
