<?php

/**
 * @file
 * Contains \Drupal\language\EventSubscriber\LanguageRequestSubscriber.
 */

namespace Drupal\language\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\Translator\TranslatorInterface;
use Drupal\language\ConfigurableLanguageManagerInterface;
use Drupal\language\LanguageNegotiatorInterface;
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
   * @var \Drupal\language\ConfigurableLanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The language negotiator.
   *
   * @var \Drupal\language\LanguageNegotiatorInterface
   */
  protected $negotiator;

  /**
   * The translation service.
   *
   * @var \Drupal\Core\Translation\Translator\TranslatorInterface
   */
  protected $translation;

  /**
   * The current active user.
   *
   * @return \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a LanguageRequestSubscriber object.
   *
   * @param \Drupal\language\ConfigurableLanguageManagerInterface $language_manager
   *   The language manager service.
   * @param \Drupal\language\LanguageNegotiatorInterface
   *   The language negotiator.
   * @param \Drupal\Core\Translation\Translator\TranslatorInterface $translation
   *   The translation service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current active user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(ConfigurableLanguageManagerInterface $language_manager, LanguageNegotiatorInterface $negotiator, TranslatorInterface $translation, AccountInterface $current_user, ConfigFactoryInterface $config_factory) {
    $this->languageManager = $language_manager;
    $this->negotiator = $negotiator;
    $this->translation = $translation;
    $this->currentUser = $current_user;
    $this->configFactory = $config_factory;
  }

  /**
   * Sets the request on the language manager.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   */
  public function onKernelRequestLanguage(GetResponseEvent $event) {
    if ($event->getRequestType() == HttpKernelInterface::MASTER_REQUEST) {
      $request = $event->getRequest();
      $this->negotiator->setCurrentUser($this->currentUser);
      $this->negotiator->setRequest($request);
      if ($this->languageManager instanceof ConfigurableLanguageManagerInterface) {
        $this->languageManager->setNegotiator($this->negotiator);
        $this->languageManager->setRequest($request);
        $this->configFactory->setLanguage($this->languageManager->getCurrentLanguage());
      }
      // After the language manager has initialized, set the default langcode
      // for the string translations.
      $langcode = $this->languageManager->getCurrentLanguage()->id;
      $this->translation->setDefaultLangcode($langcode);
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
