<?php

/**
 * @file
 * Contains \Drupal\big_pipe_test\EventSubscriber\BigPipeTestSubscriber.
 */

namespace Drupal\big_pipe_test\EventSubscriber;

use Drupal\Core\Render\HtmlResponse;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class BigPipeTestSubscriber implements EventSubscriberInterface {

  /**
   * Exposes all BigPipe placeholders (JS and no-JS) via headers for testing.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event to process.
   */
  public function onRespond(FilterResponseEvent $event) {
    $response = $event->getResponse();
    if (!$response instanceof HtmlResponse) {
      return;
    }

    $attachments = $response->getAttachments();

    $response->headers->set('BigPipe-Test-Placeholders', '<none>');
    $response->headers->set('BigPipe-Test-No-Js-Placeholders', '<none>');

    if (!empty($attachments['big_pipe_placeholders'])) {
      $response->headers->set('BigPipe-Test-Placeholders', implode(' ', array_keys($attachments['big_pipe_placeholders'])));
    }

    if (!empty($attachments['big_pipe_nojs_placeholders'])) {
      $response->headers->set('BigPipe-Test-No-Js-Placeholders', implode(' ', array_map('rawurlencode', array_keys($attachments['big_pipe_nojs_placeholders']))));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Run *just* before \Drupal\big_pipe\EventSubscriber\HtmlResponseBigPipeSubscriber::onRespond().
    $events[KernelEvents::RESPONSE][] = ['onRespond', -99999];

    return $events;
  }

}
