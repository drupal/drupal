<?php


namespace Drupal\Core;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Exception\FlattenException;

use Exception;

/**
 * Description of ExceptionController
 *
 */
class ExceptionController {

  protected $negotiation;

  public function __construct(ContentNegotiation $negotiation) {
    $this->negotiation = $negotiation;
  }

  public function execute(FlattenException $exception, Request $request) {

    $method = 'on' . $exception->getStatusCode() . $this->negotiation->getContentType($request);

    if (method_exists($this, $method)) {
      return $this->$method($exception, $request);
    }

    return new Response('A fatal error occurred: ' . $exception->getMessage(), $exception->getStatusCode());

  }

  /**
   * Processes a MethodNotAllowed exception into an HTTP 405 response.
   *
   * @param GetResponseEvent $event
   *   The Event to process.
   */
  public function on405Html(FlattenException $exception, Request $request) {
    $event->setResponse(new Response('Method Not Allowed', 405));
  }

  /**
   * Processes an AccessDenied exception into an HTTP 403 response.
   *
   * @param GetResponseEvent $event
   *   The Event to process.
   */
  public function on403Html(FlattenException $exception, Request $request) {
    $system_path = $request->attributes->get('system_path');
    $path = drupal_get_normal_path(variable_get('site_403', ''));
    if ($path && $path != $system_path) {
      $destination = ltrim($request->getPathInfo(), '/');
      $request = Request::create('/' . $path, 'GET', array('destination' => $destination));

      $kernel = new DrupalKernel();
      $response = $kernel->handle($request, DrupalKernel::SUB_REQUEST);
      $response->setStatusCode(403, 'Access denied');
    }
    else {
      $response = new Response('Access Denied', 403);

      // @todo Replace this block with something cleaner.
      $return = t('You are not authorized to access this page.');
      drupal_set_title(t('Access denied'));
      drupal_set_page_content($return);
      $page = element_info('page');
      $content = drupal_render_page($page);

      $response->setContent($content);
    }

    return $response;
  }

  public function on404Html(FlattenException $exception, Request $request) {
    watchdog('page not found', check_plain($_GET['q']), NULL, WATCHDOG_WARNING);

    // Check for and return a fast 404 page if configured.
    // @todo Inline this rather than using a function.
    drupal_fast_404();

    $system_path = $request->attributes->get('system_path');

    // Keep old path for reference, and to allow forms to redirect to it.
    if (!isset($_GET['destination'])) {
      $_GET['destination'] = $system_path;
    }

    $path = drupal_get_normal_path(variable_get('site_404', ''));
    if ($path && $path != $system_path) {
      // @TODO: Um, how do I specify an override URL again? Totally not clear.
      // Do that and sub-call the kernel rather than using meah().
      // @TODO: The create() method expects a slash-prefixed path, but we
      // store a normal system path in the site_404 variable.
      $request = Request::create('/' . $path);

      $kernel = new DrupalKernel();
      $response = $kernel->handle($request, HttpKernelInterface::SUB_REQUEST);
      $response->setStatusCode(404, 'Not Found');
    }
    else {
      $response = new Response('Not Found', 404);

      // @todo Replace this block with something cleaner.
      $return = t('The requested page "@path" could not be found.', array('@path' => $request->getPathInfo()));
      drupal_set_title(t('Page not found'));
      drupal_set_page_content($return);
      $page = element_info('page');
      $content = drupal_render_page($page);

      $response->setContent($content);
    }

    return $response;
  }

  protected function createJsonResponse() {
    $response = new Response();
    $response->headers->set('Content-Type', 'application/json; charset=utf-8');

    return $response;
  }

  /**
   * Processes an AccessDenied exception into an HTTP 403 response.
   *
   * @param GetResponseEvent $event
   *   The Event to process.
   */
  public function on403Json(FlattenException $exception, Request $request) {
    $response = $this->createJsonResponse();
    $response->setStatusCode(403, 'Access Denied');
    return $response;
  }

  /**
   * Processes a NotFound exception into an HTTP 404 response.
   *
   * @param GetResponseEvent $event
   *   The Event to process.
   */
  public function on404Json(FlattenException $exception, Request $request) {
    $response = $this->createJsonResponse();
    $response->setStatusCode(404, 'Not Found');
    return $response;
  }

  /**
   * Processes a MethodNotAllowed exception into an HTTP 405 response.
   *
   * @param GetResponseEvent $event
   *   The Event to process.
   */
  public function on405Json(FlattenException $exception, Request $request) {
    $response = $this->createJsonResponse();
    $response->setStatusCode(405, 'Method Not Allowed');
    return $response;
  }

}
