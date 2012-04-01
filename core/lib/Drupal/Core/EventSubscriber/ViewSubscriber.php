<?php

namespace Drupal\Core\EventSubscriber;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Drupal\Core\ContentNegotiation;

/**
 * @file
 *
 * Definition of Drupal\Core\EventSubscriber\ViewSubscriber;
 */

/**
 * Main subscriber for VIEW HTTP responses.
 */
class ViewSubscriber implements EventSubscriberInterface {

  protected $negotiation;

  public function __construct(ContentNegotiation $negotiation) {
    $this->negotiation = $negotiation;
  }

  protected function createAjaxResponse(GetResponseEvent $event) {
    $response = new Response();
    $response->headers->set('Content-Type', 'application/json; charset=utf-8');

    return $response;
  }

  protected function createIframeUploadResponse(GetResponseEvent $event) {
    $response = new Response();

    // Browsers do not allow JavaScript to read the contents of a user's local
    // files. To work around that, the jQuery Form plugin submits forms containing
    // a file input element to an IFRAME, instead of using XHR. Browsers do not
    // normally expect JSON strings as content within an IFRAME, so the response
    // must be customized accordingly.
    // @see http://malsup.com/jquery/form/#file-upload
    // @see Drupal.ajax.prototype.beforeSend()
    $response->headers->set('Content-Type', 'text/html; charset=utf-8');

    return $response;
  }

  /**
   * Processes a successful controller into an HTTP 200 response.
   *
   * Some controllers may not return a response object but simply the body of
   * one.  The VIEW event is called in that case, to allow us to mutate that
   * body into a Response object.  In particular we assume that the return
   * from an JSON-type response is a JSON string, so just wrap it into a
   * Response object.
   *
   * @param GetResponseEvent $event
   *   The Event to process.
   */
  public function onView(GetResponseEvent $event) {

    $request = $event->getRequest();

    $method = 'on' . $this->negotiation->getContentType($request);

    if (method_exists($this, $method)) {
      $event->setResponse($this->$method($event));
    }
    else {
      $event->setResponse(new Response('Unsupported Media Type', 415));
    }
  }

  public function onJson(GetResponseEvent $event) {
    $page_callback_result = $event->getControllerResult();

    //print_r($page_callback_result);

    $response = new JsonResponse();
    $response->setContent($page_callback_result);

    return $response;
  }

  public function onAjax(GetResponseEvent $event) {
    $page_callback_result = $event->getControllerResult();

    // Construct the response content from the page callback result.
    $commands = ajax_prepare_response($page_callback_result);
    $json = ajax_render($commands);

    // Build the actual response object.
    $response = $this->createAjaxResponse($event);
    $response->setContent($json);

    return $response;
  }

  public function onIframeUpload(GetResponseEvent $event) {
    $page_callback_result = $event->getControllerResult();

    // Construct the response content from the page callback result.
    $commands = ajax_prepare_response($page_callback_result);
    $json = ajax_render($commands);

    // Browser IFRAMEs expect HTML. Browser extensions, such as Linkification
    // and Skype's Browser Highlighter, convert URLs, phone numbers, etc. into
    // links. This corrupts the JSON response. Protect the integrity of the
    // JSON data by making it the value of a textarea.
    // @see http://malsup.com/jquery/form/#file-upload
    // @see http://drupal.org/node/1009382
    $json = '<textarea>' . $json . '</textarea>';

    // Build the actual response object.
    $response = $this->createIframeUploadResponse($event);
    $response->setContent($json);

    return $response;
  }

  /**
   * Processes a successful controller into an HTTP 200 response.
   *
   * Some controllers may not return a response object but simply the body of
   * one.  The VIEW event is called in that case, to allow us to mutate that
   * body into a Response object.  In particular we assume that the return
   * from an HTML-type response is a render array from a legacy page callback
   * and render it.
   *
   * @param GetResponseEvent $event
   *   The Event to process.
   */
  public function onHtml(GetResponseEvent $event) {
    $page_callback_result = $event->getControllerResult();
    return new Response(drupal_render_page($page_callback_result));
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::VIEW][] = array('onView');

    return $events;
  }
}
