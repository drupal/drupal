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
   * Constructs a new HtmlPageController.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $kernel
   */
  public function __construct(HttpKernelInterface $kernel) {
    $this->httpKernel = $kernel;
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

    // @todo When we have a Generator, we can replace the forward() call with
    // a render() call, which would handle ESI and hInclude as well.  That will
    // require an _internal route.  For examples, see:
    // https://github.com/symfony/symfony/blob/master/src/Symfony/Bundle/FrameworkBundle/Resources/config/routing/internal.xml
    // https://github.com/symfony/symfony/blob/master/src/Symfony/Bundle/FrameworkBundle/Controller/InternalController.php
    $attributes = clone $request->attributes;
    $controller = $_content;

    // We need to clean off the derived information and such so that the
    // subrequest can be processed properly without leaking data through.
    $attributes->remove('_system_path');
    $attributes->remove('_content');

    $response = $this->httpKernel->forward($controller, $attributes->all(), $request->query->all());

    // For successful (HTTP status 200) responses, decorate with blocks.
    if ($response->isOk()) {
      $page_content = $response->getContent();
      $response = new Response(drupal_render_page($page_content));
    }

    return $response;
  }
}
