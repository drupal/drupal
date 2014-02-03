<?php

/**
 * @file
 * Contains \Drupal\Core\Controller\AjaxController.
 */

namespace Drupal\Core\Controller;

use Drupal\Core\Ajax\AjaxResponseRenderer;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\Request;

/**
 * Default controller for Ajax requests.
 */
class AjaxController extends ContainerAware {

  /**
   * The controller resolver.
   *
   * @var \Drupal\Core\Controller\ControllerResolverInterface
   */
  protected $controllerResolver;

  /**
   * The Ajax response renderer.
   *
   * @var \Drupal\Core\Ajax\AjaxResponseRenderer
   */
  protected $ajaxRenderer;

  /**
   * Constructs a new AjaxController instance.
   *
   * @param \Drupal\Core\Controller\ControllerResolverInterface $controller_resolver
   *   The controller resolver.
   * @param \Drupal\Core\Ajax\AjaxResponseRenderer $ajax_renderer
   *   The Ajax response renderer.
   */
  public function __construct(ControllerResolverInterface $controller_resolver, AjaxResponseRenderer $ajax_renderer) {
    $this->controllerResolver = $controller_resolver;
    $this->ajaxRenderer = $ajax_renderer;
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
    return $this->ajaxRenderer->render($content);
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
