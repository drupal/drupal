<?php
/**
 * @file
 * Contains \Drupal\language\LanguageConfigSubscriber.
 */

namespace Drupal\language;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\StorageDispatcher;
use Drupal\Core\Language\LanguageManager;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Language event subscriber to set language on configuration factory service.
 */
class LanguageConfigSubscriber implements EventSubscriberInterface {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Constructs a LanguageConfigSubscriber object.
   *
   * @param \Drupal\Core\Language\LanguageManager $language_manager
   *   The language manager service.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The configuration object factory.
   */
  public function __construct(LanguageManager $language_manager, ConfigFactory $config_factory) {
    $this->languageManager = $language_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * Sets the negotiated interface language on the configuration factory.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   Kernel event to respond to.
   */
  public function onKernelRequestSetDefaultConfigLanguage(GetResponseEvent $event) {
    if ($this->languageManager->isMultiLingual()) {
      $this->configFactory->setLanguage($this->languageManager->getLanguage());
    }
  }

  /**
   * Implements EventSubscriberInterface::getSubscribedEvents().
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('onKernelRequestSetDefaultConfigLanguage', 48);
    return $events;
  }
}

