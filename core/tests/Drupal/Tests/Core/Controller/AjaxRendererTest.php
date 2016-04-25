<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Controller\AjaxRendererTest.
 */

namespace Drupal\Tests\Core\Controller;

use Drupal\Core\Render\MainContent\AjaxRenderer;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @coversDefaultClass \Drupal\Core\Render\MainContent\AjaxRenderer
 * @group Ajax
 */
class AjaxRendererTest extends UnitTestCase {

  /**
   * The tested ajax controller.
   *
   * @var \Drupal\Core\Render\MainContent\AjaxRenderer
   */
  protected $ajaxRenderer;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $element_info_manager = $this->getMock('Drupal\Core\Render\ElementInfoManagerInterface');
    $element_info_manager->expects($this->any())
      ->method('getInfo')
      ->with('ajax')
      ->willReturn([
        '#header' => TRUE,
        '#commands' => array(),
        '#error' => NULL,
      ]);
    $this->ajaxRenderer = new TestAjaxRenderer($element_info_manager);

    $this->renderer = $this->getMockBuilder('Drupal\Core\Render\Renderer')
      ->disableOriginalConstructor()
      ->setMethods(NULL)
      ->getMock();
    $container = new ContainerBuilder();
    $container->set('renderer', $this->renderer);
    \Drupal::setContainer($container);
  }

  /**
   * Tests the content method.
   *
   * @covers ::renderResponse
   */
  public function testRenderWithFragmentObject() {
    $main_content = ['#markup' => 'example content'];
    $request = new Request();
    $route_match = $this->getMock('Drupal\Core\Routing\RouteMatchInterface');
    /** @var \Drupal\Core\Ajax\AjaxResponse $result */
    $result = $this->ajaxRenderer->renderResponse($main_content, $request, $route_match);

    $this->assertInstanceOf('Drupal\Core\Ajax\AjaxResponse', $result);

    $commands = $result->getCommands();
    $this->assertEquals('insert', $commands[0]['command']);
    $this->assertEquals('example content', $commands[0]['data']);

    $this->assertEquals('insert', $commands[1]['command']);
    $this->assertEquals('status_messages', $commands[1]['data']);
  }

}

class TestAjaxRenderer extends AjaxRenderer {

  /**
   * {@inheritdoc}
   */
  protected function drupalRenderRoot(&$elements, $is_root_call = FALSE) {
    $elements += ['#attached' => []];
    if (isset($elements['#markup'])) {
      return $elements['#markup'];
    }
    elseif (isset($elements['#type'])) {
      return $elements['#type'];
    }
    else {
      return 'Markup';
    }
  }

}
