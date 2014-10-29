<?php

/**
 * @file
 * Contains \Drupal\Core\Controller\AjaxController.
 */

namespace Drupal\Core\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AlertCommand;
use Drupal\Core\Ajax\InsertCommand;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Page\HtmlFragment;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Default controller for Ajax requests.
 */
class AjaxController implements ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * The controller resolver.
   *
   * @var \Drupal\Core\Controller\ControllerResolverInterface
   */
  protected $controllerResolver;

  /**
   * The element info manager.
   *
   * @var \Drupal\Core\Render\ElementInfoManagerInterface
   */
  protected $elementInfoManager;

  /**
   * Constructs a new AjaxController instance.
   *
   * @param \Drupal\Core\Controller\ControllerResolverInterface $controller_resolver
   *   The controller resolver.
   * @param \Drupal\Core\Render\ElementInfoManagerInterface $element_info_manager
   *   The element info manager.
   */
  public function __construct(ControllerResolverInterface $controller_resolver, ElementInfoManagerInterface $element_info_manager) {
    $this->controllerResolver = $controller_resolver;
    $this->elementInfoManager = $element_info_manager;
  }

  /**
   * Controller method for Ajax content.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param callable $_content
   *   The callable that returns the content of the Ajax response.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   A response object.
   */
  public function content(Request $request, $_content) {
    $content = $this->getContentResult($request, $_content);

    // If there is already a Response object, return it without manipulation.
    if ($content instanceof Response && $content->isOk()) {
      return $content;
    }

    // Allow controllers to return an HtmlFragment directly.
    if ($content instanceof HtmlFragment) {
      $content = $content->getContent();
    }
    // Most controllers return a render array, but some return a string.
    if (!is_array($content)) {
      $content = array(
        '#markup' => $content,
      );
    }

    $response = new AjaxResponse();

    if (isset($content['#type']) && ($content['#type'] == 'ajax')) {
      // Complex Ajax callbacks can return a result that contains an error
      // message or a specific set of commands to send to the browser.
      $content += $this->elementInfoManager->getInfo('ajax');
      $error = $content['#error'];
      if (!empty($error)) {
        // Fall back to some default message otherwise use the specific one.
        if (!is_string($error)) {
          $error = 'An error occurred while handling the request: The server received invalid input.';
        }
        $response->addCommand(new AlertCommand($error));
      }
    }

    $html = $this->drupalRenderRoot($content);

    // The selector for the insert command is NULL as the new content will
    // replace the element making the Ajax call. The default 'replaceWith'
    // behavior can be changed with #ajax['method'].
    $response->addCommand(new InsertCommand(NULL, $html));
    $status_messages = array('#theme' => 'status_messages');
    $output = $this->drupalRenderRoot($status_messages);
    if (!empty($output)) {
      $response->addCommand(new PrependCommand(NULL, $output));
    }
    return $response;
  }

  /**
   * Returns the result of invoking the sub-controller.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param mixed $controller_definition
   *   A controller definition string, or a callable object/closure.
   *
   * @return mixed
   *   The result of invoking the controller. Render arrays, strings, HtmlPage,
   *   and HtmlFragment objects are possible.
   */
  public function getContentResult(Request $request, $controller_definition) {
    if ($controller_definition instanceof \Closure) {
      $callable = $controller_definition;
    }
    else {
      $callable = $this->controllerResolver->getControllerFromDefinition($controller_definition);
    }
    $arguments = $this->controllerResolver->getArguments($request, $callable);
    $page_content = call_user_func_array($callable, $arguments);

    return $page_content;
  }

  /**
   * Wraps drupal_render_root().
   *
   * @todo: Remove as part of https://drupal.org/node/2182149
   */
  protected function drupalRenderRoot(&$elements) {
    $output = drupal_render_root($elements);
    drupal_process_attached($elements);
    return $output;
  }

}
