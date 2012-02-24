<?php

namespace Drupal\Core\EventSubscriber;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

  public function onNotFoundHttpException(GetResponseEvent $event) {
    if ($this->isHtmlRequestEvent($event) && $event->getException() instanceof NotFoundHttpException) {
      $event->setResponse(new Response('Not Found', 404));
    }
  }

  public function onMethodAllowedException(GetResponseEvent $event) {
    if ($this->isHtmlRequestEvent($event) && $event->getException() instanceof MethodNotAllowedException) {
      $event->setResponse(new Response('Method Not Allowed', 405));
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
    $events[KernelEvents::EXCEPTION][] = array('onNotFoundHttpException');
    $events[KernelEvents::EXCEPTION][] = array('onAccessDeniedException');
    $events[KernelEvents::EXCEPTION][] = array('onMethodAllowedException');

    $events[KernelEvents::VIEW][] = array('onView');

    return $events;
  }
}
