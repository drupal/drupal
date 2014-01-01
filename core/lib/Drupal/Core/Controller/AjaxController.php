<?php

/**
 * @file
 * Contains \Drupal\Core\Controller\AjaxController.
 */

namespace Drupal\Core\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InsertCommand;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Page\HtmlFragment;
use Drupal\Core\Page\HtmlPage;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Default controller for ajax requests.
 */
class AjaxController extends ContainerAware {

  /**
   * The controller resolver.
   *
   * @var \Drupal\Core\Controller\ControllerResolverInterface
   */
  protected $controllerResolver;

  /**
   * Constructs a new AjaxController instance.
   *
   * @param \Drupal\Core\Controller\ControllerResolverInterface $controller_resolver
   *   The controller resolver.
   */
  public function __construct(ControllerResolverInterface $controller_resolver) {
    $this->controllerResolver = $controller_resolver;
  }

  /**
   * Controller method for AJAX content.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param callable $_content
   *   The callable that returns the content of the ajax response.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A response object.
   */
  public function content(Request $request, $_content) {
    $content = $this->getContentResult($request, $_content);
    // If there is already an AjaxResponse, then return it without
    // manipulation.
    if ($content instanceof AjaxResponse && $content->isOk()) {
      return $content;
    }

    // Allow controllers to return a HtmlFragment or a Response object directly.
    if ($content instanceof HtmlFragment) {
      $content = $content->getContent();
    }
    if ($content instanceof Response) {
      $content = $content->getContent();
    }

    // Most controllers return a render array, but some return a string.
    if (!is_array($content)) {
      $content = array(
        '#markup' => $content,
      );
    }

    $html = drupal_render($content);

    $response = new AjaxResponse();
    // The selector for the insert command is NULL as the new content will
    // replace the element making the ajax call. The default 'replaceWith'
    // behavior can be changed with #ajax['method'].
    $response->addCommand(new InsertCommand(NULL, $html));
    $status_messages = array('#theme' => 'status_messages');
    $output = drupal_render($status_messages);
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

}
