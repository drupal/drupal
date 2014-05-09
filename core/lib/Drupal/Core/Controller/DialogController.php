<?php

/**
 * @file
 * Contains \Drupal\Core\Controller\DialogController.
 */

namespace Drupal\Core\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenDialogCommand;
use Drupal\Core\Page\HtmlPage;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defines a default controller for dialog requests.
 */
class DialogController {

  /**
   * The controller resolver service.
   *
   * @var \Drupal\Core\Controller\ControllerResolverInterface
   */
  protected $controllerResolver;

  /**
   * The title resolver.
   *
   * @var \Drupal\Core\Controller\TitleResolver
   */
  protected $titleResolver;

  /**
   * Constructs a new DialogController.
   *
   * @param \Drupal\Core\Controller\ControllerResolverInterface $controller_resolver
   *   The controller resolver service.
   * @param \Drupal\Core\Controller\TitleResolverInterface $title_resolver
   *   The title resolver.
   */
  public function __construct(ControllerResolverInterface $controller_resolver, TitleResolverInterface $title_resolver) {
    $this->controllerResolver = $controller_resolver;
    $this->titleResolver = $title_resolver;
  }

  /**
   * Displays content in a modal dialog.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param mixed $_content
   *   A controller definition string, or a callable object/closure.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   AjaxResponse to return the content wrapper in a modal dialog.
   */
  public function modal(Request $request, $_content) {
    return $this->dialog($request, $_content, TRUE);
  }

  /**
   * Displays content in a dialog.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param mixed $_content
   *   A controller definition string, or a callable object/closure.
   * @param bool $modal
   *   (optional) TRUE to render a modal dialog. Defaults to FALSE.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   AjaxResponse to return the content wrapper in a dialog.
   */
  public function dialog(Request $request, $_content, $modal = FALSE) {
    $page_content = $this->getContentResult($request, $_content);

    // Allow controllers to return a HtmlPage or a Response object directly.
    if ($page_content instanceof HtmlPage) {
      $page_content = $page_content->getContent();
    }
    if ($page_content instanceof Response) {
      $page_content = $page_content->getContent();
    }

    // Most controllers return a render array, but some return a string.
    if (!is_array($page_content)) {
      $page_content = array(
        '#markup' => $page_content,
      );
    }

    $content = drupal_render($page_content);
    $title = isset($page_content['#title']) ? $page_content['#title'] : $this->titleResolver->getTitle($request, $request->attributes->get(RouteObjectInterface::ROUTE_OBJECT));
    $response = new AjaxResponse();
    // Fetch any modal options passed in from data-dialog-options.
    $options = $request->request->get('dialogOptions', array());
    // Set modal flag and re-use the modal ID.
    if ($modal) {
      $options['modal'] = TRUE;
      $target = '#drupal-modal';
    }
    else {
      // Generate the target wrapper for the dialog.
      if (isset($options['target'])) {
        // If the target was nominated in the incoming options, use that.
        $target = $options['target'];
        // Ensure the target includes the #.
        if (substr($target, 0, 1) != '#') {
          $target = '#' . $target;
        }
        // This shouldn't be passed on to jQuery.ui.dialog.
        unset($options['target']);
      }
      else {
        // Generate a target based on the route id.
        $route_name = $request->attributes->get(RouteObjectInterface::ROUTE_NAME);
        $target = '#' . drupal_html_id("drupal-dialog-$route_name");
      }
    }
    $response->addCommand(new OpenDialogCommand($target, $title, $content, $options));
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
