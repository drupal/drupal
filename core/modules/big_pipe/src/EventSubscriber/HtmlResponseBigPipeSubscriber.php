<?php

namespace Drupal\big_pipe\EventSubscriber;

use Drupal\Core\Render\HtmlResponse;
use Drupal\big_pipe\Render\BigPipe;
use Drupal\big_pipe\Render\BigPipeResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Response subscriber to replace the HtmlResponse with a BigPipeResponse.
 *
 * @see \Drupal\big_pipe\Render\BigPipe
 *
 * @todo Refactor once https://www.drupal.org/node/2577631 lands.
 */
class HtmlResponseBigPipeSubscriber implements EventSubscriberInterface {

  /**
   * The BigPipe service.
   *
   * @var \Drupal\big_pipe\Render\BigPipe
   */
  protected $bigPipe;

  /**
   * Constructs a HtmlResponseBigPipeSubscriber object.
   *
   * @param \Drupal\big_pipe\Render\BigPipe $big_pipe
   *   The BigPipe service.
   */
  public function __construct(BigPipe $big_pipe) {
    $this->bigPipe = $big_pipe;
  }

  /**
   * Adds markers to the response necessary for the BigPipe render strategy.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The event to process.
   */
  public function onRespondEarly(ResponseEvent $event) {
    $response = $event->getResponse();
    if (!$response instanceof HtmlResponse) {
      return;
    }

    // Wrap the scripts_bottom placeholder with a marker before and after,
    // because \Drupal\big_pipe\Render\BigPipe needs to be able to extract that
    // markup if there are no-JS BigPipe placeholders.
    // @see \Drupal\big_pipe\Render\BigPipe::sendPreBody()
    $attachments = $response->getAttachments();
    if (isset($attachments['html_response_attachment_placeholders']['scripts_bottom'])) {
      $scripts_bottom_placeholder = $attachments['html_response_attachment_placeholders']['scripts_bottom'];
      $content = $response->getContent();
      $content = str_replace($scripts_bottom_placeholder, '<drupal-big-pipe-scripts-bottom-marker>' . $scripts_bottom_placeholder . '<drupal-big-pipe-scripts-bottom-marker>', $content);
      $response->setContent($content);
    }
  }

  /**
   * Transforms a HtmlResponse to a BigPipeResponse.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The event to process.
   */
  public function onRespond(ResponseEvent $event) {
    $response = $event->getResponse();
    if (!$response instanceof HtmlResponse) {
      return;
    }

    $attachments = $response->getAttachments();

    // If there are no no-JS BigPipe placeholders, unwrap the scripts_bottom
    // markup.
    // @see onRespondEarly()
    // @see \Drupal\big_pipe\Render\BigPipe::sendPreBody()
    if (empty($attachments['big_pipe_nojs_placeholders'])) {
      $content = $response->getContent();
      $content = str_replace('<drupal-big-pipe-scripts-bottom-marker>', '', $content);
      $response->setContent($content);
    }

    // If there are neither BigPipe placeholders nor no-JS BigPipe placeholders,
    // there isn't anything dynamic in this response, and we can return early:
    // there is no point in sending this response using BigPipe.
    if (empty($attachments['big_pipe_placeholders']) && empty($attachments['big_pipe_nojs_placeholders'])) {
      return;
    }

    $big_pipe_response = new BigPipeResponse($response);
    $big_pipe_response->setBigPipeService($this->getBigPipeService($event));

    $event->setResponse($big_pipe_response);
  }

  /**
   * Returns the BigPipe service to use to send the current response.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   A response event.
   *
   * @return \Drupal\big_pipe\Render\BigPipe
   *   The BigPipe service.
   */
  protected function getBigPipeService(ResponseEvent $event) {
    return $this->bigPipe;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // Run after HtmlResponsePlaceholderStrategySubscriber (priority 5), i.e.
    // after BigPipeStrategy has been applied, but before normal (priority 0)
    // response subscribers have been applied, because by then it'll be too late
    // to transform it into a BigPipeResponse.
    $events[KernelEvents::RESPONSE][] = ['onRespondEarly', 3];

    // Run as the last possible subscriber.
    $events[KernelEvents::RESPONSE][] = ['onRespond', -10000];

    return $events;
  }

}
