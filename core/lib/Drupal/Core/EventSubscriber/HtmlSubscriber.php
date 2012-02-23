<?php

namespace Drupal\Core\EventSubscriber;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * @file
 *
 * Description goes here.
 */

/**
 * Description of HtmlSubscriber
 */
class HtmlSubscriber implements EventSubscriberInterface {

  protected function isHtmlRequestEvent(GetResponseEvent $event) {
    return in_array('text/html', $event->getRequest()->getAcceptableContentTypes());
  }

  public function onResourceNotFoundException(GetResponseEvent $event) {
    if ($this->isHtmlRequestEvent($event) && $event->getException() instanceof ResourceNotFoundException) {
      $event->setResponse(new Response('Not Found', 404));
    }
  }

  public function onView(GetResponseEvent $event) {
    if ($this->isHtmlRequestEvent($event)) {
      $page_callback_result = $event->getControllerResult();
      $event->setResponse(new Response(drupal_render_page($page_callback_result)));
    }
  }

  public function onAccessDeniedException(Event $event) {

  }

  static function getSubscribedEvents() {
    $events[KernelEvents::EXCEPTION][] = array('onResourceNotFoundException');
    $events[KernelEvents::EXCEPTION][] = array('onAccessDeniedException');

    $events[KernelEvents::VIEW][] = array('onView');

    return $events;
  }
}
