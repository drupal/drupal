<?php

namespace Drupal\Core\EventSubscriber;

use Drupal\Component\Utility\Html;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Subscribes to filter RSS responses, to make relative URIs absolute.
 */
class RssResponseRelativeUrlFilter implements EventSubscriberInterface {

  /**
   * Converts relative URLs to absolute URLs.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The response event.
   */
  public function onResponse(FilterResponseEvent $event) {
    // Only care about RSS responses.
    if (stripos($event->getResponse()->headers->get('Content-Type'), 'application/rss+xml') === FALSE) {
      return;
    }

    $response = $event->getResponse();
    $response->setContent($this->transformRootRelativeUrlsToAbsolute($response->getContent(), $event->getRequest()));
  }

  /**
   * Converts all root-relative URLs to absolute URLs in RSS markup.
   *
   * Does not change any existing protocol-relative or absolute URLs.
   *
   * @param string $rss_markup
   *   The RSS markup to update.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return string
   *   The updated RSS markup.
   */
  protected function transformRootRelativeUrlsToAbsolute($rss_markup, Request $request) {
    $rss_dom = new \DOMDocument();
    $rss_dom->loadXML($rss_markup);

    // Invoke Html::transformRootRelativeUrlsToAbsolute() on all HTML content
    // embedded in this RSS feed.
    foreach ($rss_dom->getElementsByTagName('description') as $node) {
      $html_markup = $node->nodeValue;
      if (!empty($html_markup)) {
        $node->nodeValue = Html::transformRootRelativeUrlsToAbsolute($html_markup, $request->getSchemeAndHttpHost());
      }
    }

    return $rss_dom->saveXML();
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Should run after any other response subscriber that modifies the markup.
    // @see \Drupal\Core\EventSubscriber\ActiveLinkResponseFilter
    $events[KernelEvents::RESPONSE][] = ['onResponse', -512];

    return $events;
  }

}
