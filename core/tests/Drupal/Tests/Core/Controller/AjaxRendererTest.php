<?php

namespace Drupal\Tests\Core\Controller;

use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\Render\MainContent\AjaxRenderer;
use Drupal\Core\Render\RendererInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

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
    $element_info_manager = $this->createMock('Drupal\Core\Render\ElementInfoManagerInterface');
    $element_info_manager->expects($this->any())
      ->method('getInfo')
      ->with('ajax')
      ->willReturn([
        '#header' => TRUE,
        '#commands' => [],
        '#error' => NULL,
      ]);
    $renderer = $this->createMock(RendererInterface::class);
    $renderer->expects($this->any())
      ->method('renderRoot')
      ->willReturnCallback(function (&$elements, $is_root_call = FALSE) {
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
      });

    $this->ajaxRenderer = new AjaxRenderer($element_info_manager, $renderer);
  }

  /**
   * Tests the content method.
   *
   * @covers ::renderResponse
   */
  public function testRenderWithFragmentObject() {
    $main_content = ['#markup' => 'example content'];
    $request = new Request();
    $route_match = $this->createMock('Drupal\Core\Routing\RouteMatchInterface');
    /** @var \Drupal\Core\Ajax\AjaxResponse $result */
    $result = $this->ajaxRenderer->renderResponse($main_content, $request, $route_match);

    $this->assertInstanceOf('Drupal\Core\Ajax\AjaxResponse', $result);

    $commands = $result->getCommands();
    $this->assertEquals('insert', $commands[0]['command']);
    $this->assertEquals('example content', $commands[0]['data']);

    $this->assertEquals('insert', $commands[1]['command']);
    $this->assertEquals('status_messages', $commands[1]['data']);
  }

  /**
   * @group legacy
   * @expectedDeprecation The renderer service must be passed to Drupal\Core\Render\MainContent\AjaxRenderer::__construct and will be required before Drupal 9.0.0. See https://www.drupal.org/node/3009400
   */
  public function testConstructorRendererArgument() {
    $element_info_manager = $this->createMock(ElementInfoManagerInterface::class);
    $container = $this->createMock(ContainerInterface::class);
    $container->expects($this->once())
      ->method('get')
      ->with('renderer')
      ->willReturn(NULL);
    \Drupal::setContainer($container);
    new AjaxRenderer($element_info_manager);
  }

}
