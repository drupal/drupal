<?php

/**
 * @file
 * Contains \Drupal\Core\EventSubscriber\LanguageRequestSubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Language\LanguageManager;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Sets the $request property on the language manager.
 */
class LanguageRequestSubscriber implements EventSubscriberInterface {

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

  /**
   * Constructs a LanguageRequestSubscriber object.
   *
   * @param \Drupal\Core\Language\LanguageManager $language_manager
   *   The language manager service.
   *
   */
  public function __construct(LanguageManager $language_manager) {
    $this->languageManager = $language_manager;
  }

  /**
   * Sets the request on the language manager.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   */
  public function onKernelRequestLanguage(GetResponseEvent $event) {
    if ($event->getRequestType() == HttpKernelInterface::MASTER_REQUEST) {
      $this->languageManager->setRequest($event->getRequest());
    }
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('onKernelRequestLanguage', 255);

    return $events;
  }

}
