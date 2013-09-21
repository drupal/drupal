<?php
/**
 * @file
 * Contains \Drupal\locale\LocaleConfigSubscriber.
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

  /**
   * Default configuration context.
   *
   * @var \Drupal\Core\Config\Context\ContextInterface
   */
  protected $defaultConfigContext;

  /**
   * Constructs a LocaleConfigSubscriber object.
   *
   * @param \Drupal\Core\Language\LanguageManager $language_manager
   *   The language manager service.
   * @param \Drupal\Core\Config\Context\ConfigContext $config_context
   *   The configuration context service.
   */
  public function __construct(LanguageManager $language_manager, ContextInterface $config_context) {
    $this->languageManager = $language_manager;
    $this->defaultConfigContext = $config_context;
  }

  /**
   * Initializes configuration context with language.
   *
   * @param \Drupal\Core\Config\ConfigEvent $event
   *   The Event to process.
   */
  public function configContext(ConfigEvent $event) {
    $context = $event->getContext();

    // If there is a language set explicitly in the current context, use it.
    // Otherwise check if there is a user set in the current context,
    // to set the language based on the preferred language of the user.
    // Otherwise set it based on the negotiated interface language.
    if ($language = $context->get('language')) {
      $context->set('locale.language', $language);
    }
    elseif ($account = $context->get('user.account')) {
      $context->set('locale.language', language_load($account->getPreferredLangcode()));
    }
    elseif ($language = $this->languageManager->getLanguage(Language::TYPE_INTERFACE)) {
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

  /**
   * Sets the negotiated interface language on the default configuration context.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   Kernel event to respond to.
   */
  public function onKernelRequestSetDefaultConfigContextLocale(GetResponseEvent $event) {
    // Re-initialize the default configuration context to ensure any cached
    // configuration object are reset and can be translated. This will invoke
    // the config context event which will retrieve the negotiated language
    // from the language manager in configContext().
    $this->defaultConfigContext->init();
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
   *   The localized config name.
   */
  public function getLocaleConfigName($name, Language $language) {
    return 'locale.config.' . $language->id . '.' . $name;
  }

  /**
   * Implements EventSubscriberInterface::getSubscribedEvents().
   */
  static function getSubscribedEvents() {
    $events['config.context'][] = array('configContext', 20);
    $events['config.load'][] = array('configLoad', 20);
    // Set the priority above the one from the RouteListener (priority 32)
    // so ensure that the context is cleared before the routing system steps in.
    $events[KernelEvents::REQUEST][] = array('onKernelRequestSetDefaultConfigContextLocale', 48);
    return $events;
  }
}
