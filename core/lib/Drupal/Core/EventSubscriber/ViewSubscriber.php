<?php

/**
 * @file
 * Definition of Drupal\Core\EventSubscriber\ViewSubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Controller\TitleResolverInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Drupal\Core\ContentNegotiation;

/**
 * Main subscriber for VIEW HTTP responses.
 *
 * @todo This needs to get refactored to be extensible so that we can handle
 *   more than just Html and Drupal-specific JSON requests. See
 *   http://drupal.org/node/1594870
 */
class ViewSubscriber implements EventSubscriberInterface {

  /**
   * The content negotiation.
   *
   * @var \Drupal\Core\ContentNegotiation
   */
  protected $negotiation;

  /**
   * The title resolver.
   *
   * @var \Drupal\Core\Controller\TitleResolverInterface
   */
  protected $titleResolver;


  /**
   * Constructs a new ViewSubscriber.
   *
   * @param \Drupal\Core\ContentNegotiation $negotiation
   *   The content negotiation.
   * @param \Drupal\Core\Controller\TitleResolverInterface $title_resolver
   *   The title resolver.
   */
  public function __construct(ContentNegotiation $negotiation, TitleResolverInterface $title_resolver) {
    $this->negotiation = $negotiation;
    $this->titleResolver = $title_resolver;
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
   * @param Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent $event
   *   The Event to process.
   */
  public function onView(GetResponseForControllerResultEvent $event) {
    $request = $event->getRequest();

    if ($event->getRequestType() == HttpKernelInterface::MASTER_REQUEST) {
      $method = 'on' . $this->negotiation->getContentType($request);

      if (method_exists($this, $method)) {
        $event->setResponse($this->$method($event));
      }
      else {
        $event->setResponse(new Response('Not Acceptable', 406));
      }
    }
  }

  public function onJson(GetResponseForControllerResultEvent $event) {
    $page_callback_result = $event->getControllerResult();

    $response = new JsonResponse();
    $response->setData($page_callback_result);

    return $response;
  }

  public function onIframeUpload(GetResponseForControllerResultEvent $event) {
    $response = $event->getResponse();

    // Browser IFRAMEs expect HTML. Browser extensions, such as Linkification
    // and Skype's Browser Highlighter, convert URLs, phone numbers, etc. into
    // links. This corrupts the JSON response. Protect the integrity of the
    // JSON data by making it the value of a textarea.
    // @see http://malsup.com/jquery/form/#file-upload
    // @see http://drupal.org/node/1009382
    $html = '<textarea>' . $response->getContent() . '</textarea>';

    return new Response($html);
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
