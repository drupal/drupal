<?php

/**
 * @file
 * Contains \Drupal\Core\Controller\HtmlPageController.
 */

namespace Drupal\Core\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Default controller for most HTML pages.
 */
class HtmlPageController {

  /**
   * The HttpKernel object to use for subrequests.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * The controller resolver.
   *
   * @var \Drupal\Core\Controller\ControllerResolverInterface
   */
  protected $controllerResolver;

  /**
   * Constructs a new HtmlPageController.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $kernel
   * @param \Drupal\Core\Controller\ControllerResolverInterface $controller_resolver
   *   The controller resolver.
   */
  public function __construct(HttpKernelInterface $kernel, ControllerResolverInterface $controller_resolver) {
    $this->httpKernel = $kernel;
    $this->controllerResolver = $controller_resolver;
  }

  /**
   * Controller method for generic HTML pages.
   *
   * @param Request $request
   *   The request object.
   * @param callable $_content
   *   The body content callable that contains the body region of this page.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A response object.
   */
  public function content(Request $request, $_content) {
    $callable = $this->controllerResolver->getControllerFromDefinition($_content);
    $arguments = $this->controllerResolver->getArguments($request, $callable);
    $page_content = call_user_func_array($callable, $arguments);
    if ($page_content instanceof Response) {
      return $page_content;
    }
    if (!is_array($page_content)) {
      $page_content = array(
        '#markup' => $page_content,
      );
    }
    // If no title was returned fall back to one defined in the route.
    if (!isset($page_content['#title']) && $request->attributes->has('_title')) {
      $page_content['#title'] = $request->attributes->get('_title');
    }

    $response = new Response(drupal_render_page($page_content));
    return $response;
  }

}
