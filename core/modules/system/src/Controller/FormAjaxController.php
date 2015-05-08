<?php

/**
 * @file
 * Contains \Drupal\system\Controller\FormAjaxController.
 */

namespace Drupal\system\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\UpdateBuildIdCommand;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Render\MainContent\MainContentRendererInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\system\FileAjaxForm;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Defines a controller to respond to form Ajax requests.
 */
class FormAjaxController implements ContainerInjectionInterface {

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface|\Drupal\Core\Form\FormCacheInterface
   */
  protected $formBuilder;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

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
   * Constructs a FormAjaxController object.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Render\MainContent\MainContentRendererInterface $ajax_renderer
   *   The main content to AJAX Response renderer.
   * @param \Drupal\Core\Routing\RouteMatchInterface
   *   The current route match.
   */
  public function __construct(LoggerInterface $logger, FormBuilderInterface $form_builder, RendererInterface $renderer, MainContentRendererInterface $ajax_renderer, RouteMatchInterface $route_match) {
    $this->logger = $logger;
    $this->formBuilder = $form_builder;
    $this->renderer = $renderer;
    $this->ajaxRenderer = $ajax_renderer;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('logger.factory')->get('ajax'),
      $container->get('form_builder'),
      $container->get('renderer'),
      $container->get('main_content_renderer.ajax'),
      $container->get('current_route_match')
    );
  }

  /**
   * Processes an Ajax form submission.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return mixed
   *   Whatever is returned by the triggering element's #ajax['callback']
   *   function. One of:
   *   - A render array containing the new or updated content to return to the
   *     browser. This is commonly an element within the rebuilt form.
   *   - A \Drupal\Core\Ajax\AjaxResponse object containing commands for the
   *     browser to process.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
   */
  public function content(Request $request) {
    $ajax_form = $this->getForm($request);
    $form = $ajax_form->getForm();
    $form_state = $ajax_form->getFormState();
    $commands = $ajax_form->getCommands();

    $this->formBuilder->processForm($form['#form_id'], $form, $form_state);

    // We need to return the part of the form (or some other content) that needs
    // to be re-rendered so the browser can update the page with changed content.
    // Since this is the generic menu callback used by many Ajax elements, it is
    // up to the #ajax['callback'] function of the element (may or may not be a
    // button) that triggered the Ajax request to determine what needs to be
    // rendered.
    $callback = NULL;
    if ($triggering_element = $form_state->getTriggeringElement()) {
      $callback = $triggering_element['#ajax']['callback'];
    }
    $callback = $form_state->prepareCallback($callback);
    if (empty($callback) || !is_callable($callback)) {
      throw new HttpException(500, 'The specified #ajax callback is empty or not callable.');
    }
    $result = call_user_func_array($callback, [&$form, &$form_state]);

    // If the callback is an #ajax callback, the result is a render array, and
    // we need to turn it into an AJAX response, so that we can add any commands
    // we got earlier; typically the UpdateBuildIdCommand when handling an AJAX
    // submit from a cached page.
    if ($result instanceof AjaxResponse) {
      $response = $result;
    }
    else {
      /** @var \Drupal\Core\Ajax\AjaxResponse $response */
      $response = $this->ajaxRenderer->renderResponse($result, $request, $this->routeMatch);
    }

    foreach ($commands as $command) {
      $response->addCommand($command, TRUE);
    }
    return $response;
  }

  /**
   * Gets a form submitted via #ajax during an Ajax callback.
   *
   * This will load a form from the form cache used during Ajax operations. It
   * pulls the form info from the request body.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return \Drupal\system\FileAjaxForm
   *   A wrapper object containing the $form, $form_state, $form_id,
   *   $form_build_id and an initial list of Ajax $commands.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
   */
  protected function getForm(Request $request) {
    $form_state = new FormState();
    $form_build_id = $request->request->get('form_build_id');

    // Get the form from the cache.
    $form = $this->formBuilder->getCache($form_build_id, $form_state);
    if (!$form) {
      // If $form cannot be loaded from the cache, the form_build_id must be
      // invalid, which means that someone performed a POST request onto
      // system/ajax without actually viewing the concerned form in the browser.
      // This is likely a hacking attempt as it never happens under normal
      // circumstances.
      $this->logger->warning('Invalid form POST data.');
      throw new BadRequestHttpException();
    }

    // When a page level cache is enabled, the form-build id might have been
    // replaced from within \Drupal::formBuilder()->getCache(). If this is the
    // case, it is also necessary to update it in the browser by issuing an
    // appropriate Ajax command.
    $commands = [];
    if (isset($form['#build_id_old']) && $form['#build_id_old'] != $form['#build_id']) {
      // If the form build ID has changed, issue an Ajax command to update it.
      $commands[] = new UpdateBuildIdCommand($form['#build_id_old'], $form['#build_id']);
      $form_build_id = $form['#build_id'];
    }

    // Since some of the submit handlers are run, redirects need to be disabled.
    $form_state->disableRedirect();

    // When a form is rebuilt after Ajax processing, its #build_id and #action
    // should not change.
    // @see \Drupal\Core\Form\FormBuilderInterface::rebuildForm()
    $form_state->addRebuildInfo('copy', [
      '#build_id' => TRUE,
      '#action' => TRUE,
    ]);

    // The form needs to be processed; prepare for that by setting a few
    // internal variables.
    $form_state->setUserInput($request->request->all());
    $form_id = $form['#form_id'];

    return new FileAjaxForm($form, $form_state, $form_id, $form_build_id, $commands);
  }

}
