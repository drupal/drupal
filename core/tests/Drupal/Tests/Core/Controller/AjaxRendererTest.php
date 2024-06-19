<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Controller;

use Drupal\Core\Render\MainContent\AjaxRenderer;
use Drupal\Core\Render\RendererInterface;
use Drupal\Tests\UnitTestCase;
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
   * @var \Drupal\Core\Render\RendererInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

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
  public function testRenderWithFragmentObject(): void {
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

}
