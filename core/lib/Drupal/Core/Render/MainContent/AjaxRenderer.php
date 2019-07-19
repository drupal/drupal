<?php

namespace Drupal\Core\Render\MainContent;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AlertCommand;
use Drupal\Core\Ajax\InsertCommand;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Default main content renderer for Ajax requests.
 */
class AjaxRenderer implements MainContentRendererInterface {

  /**
   * The element info manager.
   *
   * @var \Drupal\Core\Render\ElementInfoManagerInterface
   */
  protected $elementInfoManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new AjaxRenderer instance.
   *
   * @param \Drupal\Core\Render\ElementInfoManagerInterface $element_info_manager
   *   The element info manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(ElementInfoManagerInterface $element_info_manager, RendererInterface $renderer = NULL) {
    $this->elementInfoManager = $element_info_manager;
    if ($renderer === NULL) {
      @trigger_error('The renderer service must be passed to ' . __METHOD__ . ' and will be required before Drupal 9.0.0. See https://www.drupal.org/node/3009400', E_USER_DEPRECATED);
      $renderer = \Drupal::service('renderer');
    }
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public function renderResponse(array $main_content, Request $request, RouteMatchInterface $route_match) {
    $response = new AjaxResponse();

    if (isset($main_content['#type']) && ($main_content['#type'] == 'ajax')) {
      // Complex Ajax callbacks can return a result that contains an error
      // message or a specific set of commands to send to the browser.
      $main_content += $this->elementInfoManager->getInfo('ajax');
      $error = $main_content['#error'];
      if (!empty($error)) {
        // Fall back to some default message otherwise use the specific one.
        if (!is_string($error)) {
          $error = 'An error occurred while handling the request: The server received invalid input.';
        }
        $response->addCommand(new AlertCommand($error));
      }
    }

    $html = $this->renderer->renderRoot($main_content);
    $response->setAttachments($main_content['#attached']);

    // The selector for the insert command is NULL as the new content will
    // replace the element making the Ajax call. The default 'replaceWith'
    // behavior can be changed with #ajax['method'].
    $response->addCommand(new InsertCommand(NULL, $html));
    $status_messages = ['#type' => 'status_messages'];
    $output = $this->renderer->renderRoot($status_messages);
    if (!empty($output)) {
      $response->addCommand(new PrependCommand(NULL, $output));
    }
    return $response;
  }

  /**
   * Wraps \Drupal\Core\Render\RendererInterface::renderRoot().
   *
   * @deprecated in Drupal 8.7.x and will be removed before Drupal 9.0.0. Use
   *   $this->renderer->renderRoot() instead.
   *
   * @see https://www.drupal.org/node/2912696
   */
  protected function drupalRenderRoot(&$elements) {
    @trigger_error('\Drupal\Core\Render\MainContent\AjaxRenderer::drupalRenderRoot() is deprecated in Drupal 8.7.x and will be removed before Drupal 9.0.0. Use $this->renderer->renderRoot() instead. See https://www.drupal.org/node/2912696', E_USER_DEPRECATED);
    return $this->renderer->renderRoot($elements);
  }

}
