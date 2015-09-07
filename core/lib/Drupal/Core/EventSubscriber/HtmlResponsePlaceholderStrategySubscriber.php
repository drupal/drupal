<?php

/**
 * @file
 * Contains \Drupal\Core\EventSubscriber\HtmlResponsePlaceholderStrategySubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Render\HtmlResponse;
use Drupal\Core\Render\Placeholder\PlaceholderStrategyInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * HTML response subscriber to allow for different placeholder strategies.
 *
 * This allows core and contrib to coordinate how to render placeholders;
 * e.g. an EsiRenderStrategy could replace the placeholders with ESI tags,
 * while e.g. a BigPipeRenderStrategy could store the placeholders in a
 * BigPipe service and render them after the main content has been sent to
 * the client.
 */
class HtmlResponsePlaceholderStrategySubscriber implements EventSubscriberInterface {

  /**
   * The placeholder strategy to use.
   *
   * @var \Drupal\Core\Render\Placeholder\PlaceholderStrategyInterface
   */
  protected $placeholderStrategy;

  /**
   * Constructs a HtmlResponsePlaceholderStrategySubscriber object.
   *
   * @param \Drupal\Core\Render\Placeholder\PlaceholderStrategyInterface $placeholder_strategy
   *   The placeholder strategy to use.
   */
  public function __construct(PlaceholderStrategyInterface $placeholder_strategy) {
    $this->placeholderStrategy = $placeholder_strategy;
  }

  /**
   * Processes placeholders for HTML responses.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event to process.
   */
  public function onRespond(FilterResponseEvent $event) {
    if (!$event->isMasterRequest()) {
      return;
    }

    $response = $event->getResponse();
    if (!$response instanceof HtmlResponse) {
      return;
    }

    $attachments = $response->getAttachments();
    if (empty($attachments['placeholders'])) {
      return;
    }

    $attachments['placeholders'] = $this->placeholderStrategy->processPlaceholders($attachments['placeholders']);

    $response->setAttachments($attachments);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Run shortly before HtmlResponseSubscriber.
    $events[KernelEvents::RESPONSE][] = ['onRespond', 5];
    return $events;
  }

}
