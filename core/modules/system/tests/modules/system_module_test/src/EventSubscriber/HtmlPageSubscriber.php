<?php

/**
 * @file
 * Contains \Drupal\system_module_test\EventSubscriber\HtmlPageSubscriber.
 */

namespace Drupal\system_module_test\EventSubscriber;
use Drupal\Core\Page\HtmlPage;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Defines an event subscriber to alter some metatags.
 */
class HtmlPageSubscriber implements EventSubscriberInterface {

  /**
   * Adds some metatags to the HTML page object.
   */
  public function onHtmlPage(GetResponseForControllerResultEvent $event) {
    if (($page = $event->getControllerResult()) && $page instanceof HtmlPage) {
      $metatags =& $page->getMetaElements();
      foreach ($metatags as $key => $tag) {
        // Remove the HTML5 mobile meta-tags.
        if (in_array($tag->getName(), array('MobileOptimized', 'HandheldFriendly', 'viewport', 'cleartype'))) {
          unset($metatags[$key]);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Execute between
    // \Drupal\Core\EventSubscriber\HtmlViewSubscriber::onHtmlFragment and
    // \Drupal\Core\EventSubscriber\HtmlViewSubscriber::onHtmlPage and
    $events[KernelEvents::VIEW][] = array('onHtmlPage', 60);
    return $events;
  }

}
