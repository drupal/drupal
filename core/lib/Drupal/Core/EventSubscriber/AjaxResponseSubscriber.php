<?php

namespace Drupal\Core\EventSubscriber;

use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Symfony\Component\DependencyInjection\Attribute\AutowireServiceClosure;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Response subscriber to handle AJAX responses.
 */
class AjaxResponseSubscriber implements EventSubscriberInterface {

  /**
   * Constructs an AjaxResponseSubscriber object.
   *
   * @param \Closure $processorClosure
   *   The AJAX response attachments processor service, wrapped in a closure.
   */
  public function __construct(
    #[AutowireServiceClosure('ajax_response.attachments_processor')]
    protected \Closure $processorClosure,
  ) {}

  /**
   * Request parameter to indicate that a request is a Drupal Ajax request.
   */
  const AJAX_REQUEST_PARAMETER = '_drupal_ajax';

  /**
   * Sets the AJAX parameter from the current request.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The response event, which contains the current request.
   */
  public function onRequest(RequestEvent $event) {
    // Pass to the Html class that the current request is an Ajax request.
    if ($event->getRequest()->get(static::AJAX_REQUEST_PARAMETER)) {
      Html::setIsAjax(TRUE);
    }
  }

  /**
   * Renders the ajax commands right before preparing the result.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The response event, which contains the possible AjaxResponse object.
   */
  public function onResponse(ResponseEvent $event) {
    $response = $event->getResponse();
    if ($response instanceof AjaxResponse) {
      ($this->processorClosure)()->processAttachments($response);

      // IE 9 does not support XHR 2 (http://caniuse.com/#feat=xhr2), so
      // for that browser, jquery.form submits requests containing a file upload
      // via an IFRAME rather than via XHR. Since the response is being sent to
      // an IFRAME, it must be formatted as HTML. Specifically:
      // - It must use the text/html content type or else the browser will
      //   present a download prompt. Note: This applies to both file uploads
      //   as well as any ajax request in a form with a file upload form.
      // - It must place the JSON data into a textarea to prevent browser
      //   extensions such as Linkification and Skype's Browser Highlighter
      //   from applying HTML transformations such as URL or phone number to
      //   link conversions on the data values.
      //
      // Since this affects the format of the output, it could be argued that
      // this should be implemented as a separate Accept MIME type. However,
      // that would require separate variants for each type of AJAX request
      // (e.g., drupal-ajax, drupal-dialog, drupal-modal), so for expediency,
      // this browser workaround is implemented via a GET or POST parameter.
      //
      // @see http://malsup.com/jquery/form/#file-upload
      // @see https://www.drupal.org/node/1009382
      // @see https://www.drupal.org/node/2339491
      // @see Drupal.ajax.prototype.beforeSend()
      $accept = $event->getRequest()->headers->get('accept', '');

      if (str_contains($accept, 'text/html')) {
        $response->headers->set('Content-Type', 'text/html; charset=utf-8');

        // Browser IFRAMEs expect HTML. Browser extensions, such as
        // Linkification and Skype's Browser Highlighter, convert URLs, phone
        // numbers, etc. into links. This corrupts the JSON response. Protect
        // the integrity of the JSON data by making it the value of a textarea.
        // @see http://malsup.com/jquery/form/#file-upload
        // @see https://www.drupal.org/node/1009382
        $response->setContent('<textarea>' . $response->getContent() . '</textarea>');
      }

      // User-uploaded files cannot set any response headers, so a custom header
      // is used to indicate to ajax.js that this response is safe. Note that
      // most Ajax requests bound using the Form API will be protected by having
      // the URL flagged as trusted in Drupal.settings, so this header is used
      // only for things like custom markup that gets Ajax behaviors attached.
      $response->headers->set('X-Drupal-Ajax-Token', 1);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[KernelEvents::RESPONSE][] = ['onResponse', -100];
    $events[KernelEvents::REQUEST][] = ['onRequest', 50];

    return $events;
  }

}
