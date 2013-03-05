<?php
/**
 * @file
 * Definition of \Drupal\locale\LocaleConfigSubscriber.
 */

namespace Drupal\locale;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\Context\ConfigContext;
use Drupal\Core\Config\Context\ContextInterface;
use Drupal\Core\Config\ConfigEvent;
use Drupal\Core\Config\StorageDispatcher;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManager;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


/**
 * Locale Config helper
 *
 * $config is always a DrupalConfig object.
 */
class LocaleConfigSubscriber implements EventSubscriberInterface {
  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

  protected $defaultConfigContext;

  /**
   * Constructs a LocaleConfigSubscriber object.
   *
   * @param \Drupal\Core\Config\Context\ConfigContext $config_context
   *   The config context service.
   * @param \Drupal\Core\Language\LanguageManager $language_manager
   *   The language manager service.
   */
  public function __construct(LanguageManager $language_manager, ContextInterface $config_context) {
    $this->languageManager = $language_manager;
    $this->defaultConfigContext = $config_context;
  }

  /**
   * Initialize configuration context with language.
   *
   * @param \Drupal\Core\Config\ConfigEvent $event
   *   The Event to process.
   */
  public function configContext(ConfigEvent $event) {
    $context = $event->getContext();

    // Add user's language for user context.
    if ($account = $context->get('user.account')) {
      $context->set('locale.language', language_load(user_preferred_langcode($account)));
    }
    elseif ($language = $this->languageManager->getLanguage(LANGUAGE_TYPE_INTERFACE)) {
      $context->set('locale.language', $language);
    }
  }

  /**
   * Override configuration values with localized data.
   *
   * @param \Drupal\Core\Config\ConfigEvent $event
   *   The Event to process.
   */
  public function configLoad(ConfigEvent $event) {
    $context = $event->getContext();
    if ($language = $context->get('locale.language')) {
      $config = $event->getConfig();
      $locale_name = $this->getLocaleConfigName($config->getName(), $language);
      // Check to see if the config storage has an appropriately named file
      // containing override data.
      if ($override = $event->getConfig()->getStorage()->read($locale_name)) {
        $config->setOverride($override);
      }
    }
  }

  public function onKernelRequestSetDefaultConfigContextLocale(GetResponseEvent $event) {
    if ($language = $this->languageManager->getLanguage(LANGUAGE_TYPE_INTERFACE)) {
      $this->defaultConfigContext->set('locale.language', $language);
    }
  }

  /**
   * Get configuration name for this language.
   *
   * It will be the same name with a prefix depending on language code:
   * locale.config.LANGCODE.NAME
   *
   * @param string $name
   *   The name of the config object.
   * @param \Drupal\Core\Language\Language $language
   *   The language object.
   *
   * @return string
   *   The localised config name.
   */
  public function getLocaleConfigName($name, Language $language) {
    return 'locale.config.' . $language->langcode . '.' . $name;
  }

  /**
   * Implements EventSubscriberInterface::getSubscribedEvents().
   */
  static function getSubscribedEvents() {
    $events['config.context'][] = array('configContext', 20);
    $events['config.load'][] = array('configLoad', 20);
    $events[KernelEvents::REQUEST][] = array('onKernelRequestSetDefaultConfigContextLocale', 20);
    return $events;
  }
}
