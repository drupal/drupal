<?php

/**
 * @file
 * Contains \Drupal\Core\Form\FormAjaxResponseBuilder.
 */

namespace Drupal\Core\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\UpdateBuildIdCommand;
use Drupal\Core\Render\MainContent\MainContentRendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Builds an AJAX form response.
 *
 * Given the current request, a form render array, its form state, and any AJAX
 * commands to apply to the form, build a response object.
 */
class FormAjaxResponseBuilder implements FormAjaxResponseBuilderInterface {

  /**
   * The main content to AJAX Response renderer.
   *
   * @var \Drupal\Core\Render\MainContent\MainContentRendererInterface
   */
  protected $ajaxRenderer;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new FormAjaxResponseBuilder.
   *
   * @param \Drupal\Core\Render\MainContent\MainContentRendererInterface $ajax_renderer
   *   The ajax renderer.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(MainContentRendererInterface $ajax_renderer, RouteMatchInterface $route_match) {
    $this->ajaxRenderer = $ajax_renderer;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public function buildResponse(Request $request, array $form, FormStateInterface $form_state, array $commands) {
    // If the form build ID has changed, issue an Ajax command to update it.
    if (isset($form['#build_id_old']) && $form['#build_id_old'] !== $form['#build_id']) {
      $commands[] = new UpdateBuildIdCommand($form['#build_id_old'], $form['#build_id']);
    }

    // We need to return the part of the form (or some other content) that needs
    // to be re-rendered so the browser can update the page with changed
    // content. It is up to the #ajax['callback'] function of the element (may
    // or may not be a button) that triggered the Ajax request to determine what
    // needs to be rendered.
    $callback = NULL;
    if (($triggering_element = $form_state->getTriggeringElement()) && isset($triggering_element['#ajax']['callback'])) {
      $callback = $triggering_element['#ajax']['callback'];
    }
    $callback = $form_state->prepareCallback($callback);
    if (empty($callback) || !is_callable($callback)) {
      throw new HttpException(500, 'The specified #ajax callback is empty or not callable.');
    }
    $result = call_user_func_array($callback, [&$form, &$form_state, $request]);

    // If the callback is an #ajax callback, the result is a render array, and
    // we need to turn it into an AJAX response, so that we can add any commands
    // we got earlier; typically the UpdateBuildIdCommand when handling an AJAX
    // submit from a cached page.
    if ($result instanceof AjaxResponse) {
      $response = $result;
    }
    else {
      // At this point we know callback returned a render element. If the
      // element is part of the group (#group is set on it) it won't be rendered
      // unless we remove #group from it. This is caused by
      // \Drupal\Core\Render\Element\RenderElement::preRenderGroup(), which
      // prevents all members of groups from being rendered directly.
      if (!empty($result['#group'])) {
        unset($result['#group']);
      }

      /** @var \Drupal\Core\Ajax\AjaxResponse $response */
      $response = $this->ajaxRenderer->renderResponse($result, $request, $this->routeMatch);
    }

    foreach ($commands as $command) {
      $response->addCommand($command, TRUE);
    }
    return $response;
  }

}
