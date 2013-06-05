<?php

/**
 * @file
 * Contains \Drupal\Core\Controller\AjaxController.
 */

namespace Drupal\Core\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InsertCommand;
use Drupal\Core\Ajax\PrependCommand;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\Request;

/**
 * Default controller for ajax requests.
 */
class AjaxController extends ContainerAware {

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

    // @todo When we have a Generator, we can replace the forward() call with
    // a render() call, which would handle ESI and hInclude as well.  That will
    // require an _internal route.  For examples, see:
    // https://github.com/symfony/symfony/blob/master/src/Symfony/Bundle/FrameworkBundle/Resources/config/routing/internal.xml
    // https://github.com/symfony/symfony/blob/master/src/Symfony/Bundle/FrameworkBundle/Controller/InternalController.php
    $attributes = clone $request->attributes;
    $controller = $_content;

    // We need to clean up the derived information and such so that the
    // subrequest can be processed properly without leaking data through.
    $attributes->remove('system_path');
    $attributes->remove('_content');
    $attributes->remove('_legacy');

    // Remove the accept header so the subrequest does not end up back in this
    // controller.
    $request->headers->remove('accept');
    // Remove the header in order to let the subrequest not think that it's an
    // ajax request, see \Drupal\Core\ContentNegotiation.
    $request->headers->remove('x-requested-with');

    $response = $this->container->get('http_kernel')->forward($controller, $attributes->all(), $request->query->all());
    // For successful (HTTP status 200) responses.
    if ($response->isOk()) {
      // If there is already an AjaxResponse, then return it without
      // manipulation.
      if (!($response instanceof AjaxResponse)) {
        // Pull the content out of the response.
        $content = $response->getContent();
        // A page callback could return a render array or a string.
        $html = is_string($content) ? $content : drupal_render($content);
        $response = new AjaxResponse();
        // The selector for the insert command is NULL as the new content will
        // replace the element making the ajax call. The default 'replaceWith'
        // behavior can be changed with #ajax['method'].
        $response->addCommand(new InsertCommand(NULL, $html));
        $status_messages = theme('status_messages');
        if (!empty($status_messages)) {
          $response->addCommand(new PrependCommand(NULL, $status_messages));
        }
      }
    }
    return $response;
  }
}


