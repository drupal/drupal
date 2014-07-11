<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Ajax\AjaxResponseRendererTest.
 */

namespace Drupal\Tests\Core\Ajax;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AjaxResponseRenderer;
use Drupal\Core\Page\HtmlFragment;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @coversDefaultClass \Drupal\Core\Ajax\AjaxResponseRenderer
 * @group Ajax
 */
class AjaxResponseRendererTest extends UnitTestCase {

  /**
   * The tested ajax response renderer.
   *
   * @var \Drupal\Tests\Core\Ajax\TestAjaxResponseRenderer
   */
  protected $ajaxResponseRenderer;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->ajaxResponseRenderer = new TestAjaxResponseRenderer();
  }

  /**
   * Tests the render method with an HtmlFragment object.
   *
   * @covers \Drupal\Core\Ajax\AjaxResponseRenderer::render
   */
  public function testRenderWithFragmentObject() {
    $html_fragment = new HtmlFragment('example content');
    /** @var \Drupal\Core\Ajax\AjaxResponse $result */
    $result = $this->ajaxResponseRenderer->render($html_fragment);

    $this->assertInstanceOf('Drupal\Core\Ajax\AjaxResponse', $result);

    $commands = $result->getCommands();
    $this->assertEquals('insert', $commands[0]['command']);
    $this->assertEquals('example content', $commands[0]['data']);

    $this->assertEquals('insert', $commands[1]['command']);
    $this->assertEquals('status_messages', $commands[1]['data']);
  }

  /**
   * Tests the render method with an HtmlFragment object.
   *
   * @covers \Drupal\Core\Ajax\AjaxResponseRenderer::render
   */
  public function testRenderWithString() {
    $html_fragment = 'example content';
    /** @var \Drupal\Core\Ajax\AjaxResponse $result */
    $result = $this->ajaxResponseRenderer->render($html_fragment);

    $this->assertInstanceOf('Drupal\Core\Ajax\AjaxResponse', $result);

    $commands = $result->getCommands();
    $this->assertEquals('insert', $commands[0]['command']);
    $this->assertEquals('example content', $commands[0]['data']);

    $this->assertEquals('insert', $commands[1]['command']);
    $this->assertEquals('status_messages', $commands[1]['data']);
  }


  /**
   * Tests the render method with a response object.
   *
   * @covers \Drupal\Core\Ajax\AjaxResponseRenderer::render
   */
  public function testRenderWithResponseObject() {
    $json_response = new JsonResponse(array('foo' => 'bar'));
    $this->assertSame($json_response, $this->ajaxResponseRenderer->render($json_response));
  }

  /**
   * Tests the render method with an Ajax response object.
   *
   * @covers \Drupal\Core\Ajax\AjaxResponseRenderer::render
   */
  public function testRenderWithAjaxResponseObject() {
    $ajax_response = new AjaxResponse(array('foo' => 'bar'));
    $this->assertSame($ajax_response, $this->ajaxResponseRenderer->render($ajax_response));
  }

}

class TestAjaxResponseRenderer extends AjaxResponseRenderer {

  /**
   * {@inheritdoc}
   */
  protected function drupalRender(&$elements, $is_recursive_call = FALSE) {
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

  /**
   * {@inheritdoc}
   */
  protected function elementInfo($type) {
    if ($type == 'ajax') {
      return array(
        '#header' => TRUE,
        '#commands' => array(),
        '#error' => NULL,
      );
    }
    else {
      return array();
    }
  }

}
