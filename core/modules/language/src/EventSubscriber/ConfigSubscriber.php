<?php

namespace Drupal\language\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageDefault;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\language\ConfigurableLanguageManager;
use Drupal\language\HttpKernel\PathProcessorLanguage;
use Drupal\language\LanguageNegotiatorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Deletes the container if default language has changed.
 */
class ConfigSubscriber implements EventSubscriberInterface {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The default language.
   *
   * @var \Drupal\Core\Language\LanguageDefault
   */
  protected $languageDefault;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The language negotiator.
   *
   * @var \Drupal\language\LanguageNegotiatorInterface
   */
  protected $languageNegotiator;

  /**
   * The language path processor.
   *
   * @var \Drupal\language\HttpKernel\PathProcessorLanguage
   */
  protected $pathProcessorLanguage;

  /**
   * Constructs a new class object.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Language\LanguageDefault $language_default
   *   The default language.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\language\LanguageNegotiatorInterface $language_negotiator
   *   The language negotiator.
   */
  public function __construct(LanguageManagerInterface $language_manager, LanguageDefault $language_default, ConfigFactoryInterface $config_factory, LanguageNegotiatorInterface $language_negotiator) {
    $this->languageManager = $language_manager;
    $this->languageDefault = $language_default;
    $this->configFactory = $config_factory;
    $this->languageNegotiator = $language_negotiator;
  }

  /**
   * Causes the container to be rebuilt on the next request.
   *
   * This event subscriber assumes that the new default langcode and old default
   * langcode are valid langcodes. If the schema definition of either
   * system.site:default_langcode or language.negotiation::url.prefixes changes
   * then this event must be changed to work with both the old and new schema
   * definition so this event is update safe.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The configuration event.
   */
  public function onConfigSave(ConfigCrudEvent $event) {
    $saved_config = $event->getConfig();
    if ($saved_config->getName() == 'system.site' && $event->isChanged('default_langcode')) {
      $new_default_langcode = $saved_config->get('default_langcode');
      $default_language = $this->configFactory->get('language.entity.' . $new_default_langcode);
      // During an import the language might not exist yet.
      if (!$default_language->isNew()) {
        $this->languageDefault->set(new Language($default_language->get()));
        $this->languageManager->reset();

        // Directly update language negotiation settings instead of calling
        // language_negotiation_url_prefixes_update() to ensure that the code
        // obeys the hook_update_N() restrictions.
        $negotiation_config = $this->configFactory->getEditable('language.negotiation');
        $negotiation_changed = FALSE;
        $url_prefixes = $negotiation_config->get('url.prefixes');
        $old_default_langcode = $saved_config->getOriginal('default_langcode');
        if (empty($url_prefixes[$old_default_langcode])) {
          $negotiation_config->set('url.prefixes.' . $old_default_langcode, $old_default_langcode);
          $negotiation_changed = TRUE;
        }
        if (empty($url_prefixes[$new_default_langcode])) {
          $negotiation_config->set('url.prefixes.' . $new_default_langcode, '');
          $negotiation_changed = TRUE;
        }
        if ($negotiation_changed) {
          $negotiation_config->save(TRUE);
        }
      }
      // Trigger a container rebuild on the next request by invalidating it.
      ConfigurableLanguageManager::rebuildServices();
    }
    elseif ($saved_config->getName() == 'language.types' && $event->isChanged('negotiation')) {
      // If the negotiation configuration changed the language negotiator and
      // the language path processor have to be reset so that they regenerate
      // the method instances and also sort them accordingly to the new config.
      $this->languageNegotiator->reset();
      if (isset($this->pathProcessorLanguage)) {
        $this->pathProcessorLanguage->reset();
      }
    }
  }

  /**
   * Injects the language path processors on multilingual site configuration.
   *
   * @param \Drupal\language\HttpKernel\PathProcessorLanguage $path_processor_language
   *   The language path processor.
   */
  public function setPathProcessorLanguage(PathProcessorLanguage $path_processor_language) {
    $this->pathProcessorLanguage = $path_processor_language;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[ConfigEvents::SAVE][] = ['onConfigSave', 0];
    return $events;
  }

}
