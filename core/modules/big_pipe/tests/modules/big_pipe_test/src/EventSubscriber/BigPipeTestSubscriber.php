<?php

declare(strict_types=1);

namespace Drupal\big_pipe_test\EventSubscriber;

use Drupal\Core\Render\AttachmentsInterface;
use Drupal\Core\Render\HtmlResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Defines a test BigPipe subscriber that checks whether the session is empty.
 */
class BigPipeTestSubscriber implements EventSubscriberInterface {

  /**
   * @see \Drupal\big_pipe_test\BigPipeTestController::responseException()
   *
   * @var string
   */
  const CONTENT_TRIGGER_EXCEPTION = 'NOPE!NOPE!NOPE!';

  /**
   * Triggers exception for embedded HTML/AJAX responses with certain content.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The event to process.
   *
   * @throws \Exception
   *
   * @see \Drupal\big_pipe_test\BigPipeTestController::responseException()
   */
  public function onRespondTriggerException(ResponseEvent $event) {
    $response = $event->getResponse();

    if (!$response instanceof AttachmentsInterface) {
      return;
    }

    $attachments = $response->getAttachments();
    if (!isset($attachments['big_pipe_placeholders']) && !isset($attachments['big_pipe_nojs_placeholders'])) {
      if (str_contains($response->getContent(), static::CONTENT_TRIGGER_EXCEPTION)) {
        throw new \Exception('Oh noes!');
      }
    }
  }

  /**
   * Exposes all BigPipe placeholders (JS and no-JS) via headers for testing.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The event to process.
   */
  public function onRespondSetBigPipeDebugPlaceholderHeaders(ResponseEvent $event) {
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
  public static function getSubscribedEvents(): array {
    // Run just before \Drupal\big_pipe\EventSubscriber\HtmlResponseBigPipeSubscriber::onRespond().
    $events[KernelEvents::RESPONSE][] = ['onRespondSetBigPipeDebugPlaceholderHeaders', -9999];

    // Run just after \Drupal\big_pipe\EventSubscriber\HtmlResponseBigPipeSubscriber::onRespond().
    $events[KernelEvents::RESPONSE][] = ['onRespondTriggerException', -10001];

    return $events;
  }

}
