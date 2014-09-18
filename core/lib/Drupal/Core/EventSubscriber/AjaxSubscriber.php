<?php

/**
 * @file
 * Contains \Drupal\Core\EventSubscriber\AjaxSubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Subscribes to set AJAX HTML IDs and prepare AJAX responses.
 */
class AjaxSubscriber implements EventSubscriberInterface {

  /**
   * Sets the AJAX HTML IDs from the current request.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The response event, which contains the current request.
   */
  public function onRequest(GetResponseEvent $event) {
    Html::setAjaxHtmlIds($event->getRequest()->request->get('ajax_html_ids', ''));
  }

  /**
   * Renders the ajax commands right before preparing the result.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The response event, which contains the possible AjaxResponse object.
   */
  public function onResponse(FilterResponseEvent $event) {
    $response = $event->getResponse();
    if ($response instanceof AjaxResponse) {
      $response->prepareResponse($event->getRequest());
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = array('onResponse', -100);
    $events[KernelEvents::REQUEST][] = array('onRequest', 50);

    return $events;
  }

}
