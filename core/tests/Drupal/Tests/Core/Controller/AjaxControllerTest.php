<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Controller\AjaxControllerTest.
 */

namespace Drupal\Tests\Core\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Controller\AjaxController;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \Drupal\Core\Controller\AjaxControllerTest
 * @group Ajax
 */
class AjaxControllerTest extends UnitTestCase {

  /**
   * The tested ajax controller.
   *
   * @var \Drupal\Tests\Core\Controller\TestAjaxController
   */
  protected $ajaxController;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $controller_resolver = $this->getMock('Drupal\Core\Controller\ControllerResolverInterface');
    $controller_resolver->expects($this->any())
      ->method('getArguments')
      ->willReturn([]);
    $element_info_manager = $this->getMock('Drupal\Core\Render\ElementInfoManagerInterface');
    $element_info_manager->expects($this->any())
      ->method('getInfo')
      ->with('ajax')
      ->willReturn([
        '#header' => TRUE,
        '#commands' => array(),
        '#error' => NULL,
      ]);
    $this->ajaxController = new TestAjaxController($controller_resolver, $element_info_manager);
  }

  /**
   * Tests the renderMainContent method.
   *
   * @covers \Drupal\Core\Controller\AjaxController::renderContentIntoResponse
   */
  public function testRenderWithFragmentObject() {
    $main_content = ['#markup' => 'example content'];
    $request = new Request();
    $_content = function() use ($main_content) {
      return $main_content;
    };
    /** @var \Drupal\Core\Ajax\AjaxResponse $result */
    $result = $this->ajaxController->content($request, $_content);

    $this->assertInstanceOf('Drupal\Core\Ajax\AjaxResponse', $result);

    $commands = $result->getCommands();
    $this->assertEquals('insert', $commands[0]['command']);
    $this->assertEquals('example content', $commands[0]['data']);

    $this->assertEquals('insert', $commands[1]['command']);
    $this->assertEquals('status_messages', $commands[1]['data']);
  }

  /**
   * Tests the handle method with a Json response object.
   *
   * @covers \Drupal\Core\Controller\AjaxController::handle
   */
  public function testRenderWithResponseObject() {
    $json_response = new JsonResponse(array('foo' => 'bar'));
    $request = new Request();
    $_content = function() use ($json_response) {
      return $json_response;
    };
    $this->assertSame($json_response, $this->ajaxController->content($request, $_content));
  }

  /**
   * Tests the handle method with an Ajax response object.
   *
   * @covers \Drupal\Core\Controller\AjaxController::handle
   */
  public function testRenderWithAjaxResponseObject() {
    $ajax_response = new AjaxResponse(array('foo' => 'bar'));
    $request = new Request();
    $_content = function() use ($ajax_response) {
      return $ajax_response;
    };
    $this->assertSame($ajax_response, $this->ajaxController->content($request, $_content));
  }

}

class TestAjaxController extends AjaxController {

  /**
   * {@inheritdoc}
   */
  protected function drupalRenderRoot(&$elements, $is_root_call = FALSE) {
    if (isset($elements['#markup'])) {
      return $elements['#markup'];
    }
    elseif (isset($elements['#theme'])) {
      return $elements['#theme'];
    }
    else {
      return 'Markup';
    }
  }

}
