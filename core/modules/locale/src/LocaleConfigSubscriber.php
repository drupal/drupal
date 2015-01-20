<?php
/**
 * @file
 * Contains \Drupal\locale\LocaleConfigSubscriber.
 */

namespace Drupal\locale;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\TypedData\TraversableTypedDataInterface;
use Drupal\language\Config\LanguageConfigOverride;
use Drupal\language\Config\LanguageConfigOverrideCrudEvent;
use Drupal\language\Config\LanguageConfigOverrideEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Updates corresponding string translation when language overrides change.
 *
 * This reacts to the updating or deleting of configuration language overrides.
 * It checks whether there are string translations associated with the
 * configuration that is being saved and, if so, updates those string
 * translations with the new configuration values and marks them as customized.
 * That way manual updates to configuration will not be inadvertently reverted
 * when updated translations from https://localize.drupal.org are being
 * imported.
 */
class LocaleConfigSubscriber implements EventSubscriberInterface {

  /**
   * The string storage.
   *
   * @var \Drupal\locale\StringStorageInterface;
   */
  protected $stringStorage;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The typed configuration manager.
   *
   * @var \Drupal\locale\LocaleConfigManager
   */
  protected $localeConfigManager;

  /**
   * Constructs a LocaleConfigSubscriber.
   *
   * @param \Drupal\locale\StringStorageInterface $string_storage
   *   The string storage.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\locale\LocaleConfigManager $locale_config_manager
   *   The typed configuration manager.
   */
  public function __construct(StringStorageInterface $string_storage, ConfigFactoryInterface $config_factory, LocaleConfigManager $locale_config_manager) {
    $this->stringStorage = $string_storage;
    $this->configFactory = $config_factory;
    $this->localeConfigManager = $locale_config_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[LanguageConfigOverrideEvents::SAVE_OVERRIDE] = 'onSave';
    $events[LanguageConfigOverrideEvents::DELETE_OVERRIDE] = 'onDelete';
    return $events;
  }


  /**
   * Updates the translation strings when shipped configuration is saved.
   *
   * @param \Drupal\language\Config\LanguageConfigOverrideCrudEvent $event
   *   The language configuration event.
   */
  public function onSave(LanguageConfigOverrideCrudEvent $event) {
    // Do not mark strings as customized when community translations are being
    // imported.
    if ($this->localeConfigManager->isUpdatingConfigTranslations()) {
      $callable = [$this, 'saveTranslation'];
    }
    else {
      $callable = [$this, 'saveCustomizedTranslation'];
    }

    $this->updateTranslationStrings($event, $callable);
  }

  /**
   * Updates the translation strings when shipped configuration is deleted.
   *
   * @param \Drupal\language\Config\LanguageConfigOverrideCrudEvent $event
   *   The language configuration event.
   */
  public function onDelete(LanguageConfigOverrideCrudEvent $event) {
    if ($this->localeConfigManager->isUpdatingConfigTranslations()) {
      $callable = [$this, 'deleteTranslation'];
    }
    else {
      // Do not delete the string, but save a customized translation with the
      // source value so that the deletion will not be reverted by importing
      // community translations.
      // @see \Drupal\locale\LocaleConfigSubscriber::saveCustomizedTranslation()
      $callable = [$this, 'saveCustomizedTranslation'];
    }

    $this->updateTranslationStrings($event, $callable);
  }

  /**
   * Updates the translation strings of shipped configuration.
   *
   * @param \Drupal\language\Config\LanguageConfigOverrideCrudEvent $event
   *   The language configuration event.
   * @param $callable
   *   A callable to apply to each translatable string of the configuration.
   */
  protected function updateTranslationStrings(LanguageConfigOverrideCrudEvent $event, $callable) {
    $translation_config = $event->getLanguageConfigOverride();
    $name = $translation_config->getName();

    // Only do anything if the configuration was shipped.
    if ($this->stringStorage->getLocations(['type' => 'configuration', 'name' => $name])) {
      $source_config = $this->configFactory->getEditable($name);
      $schema = $this->localeConfigManager->get($name)->getTypedConfig();
      $this->traverseSchema($schema, $source_config, $translation_config, $callable);
    }
  }

  /**
   * Traverses configuration schema and applies a callback to each leaf element.
   *
   * It skips leaf elements that are not translatable.
   *
   * @param \Drupal\Core\TypedData\TraversableTypedDataInterface $schema
   *   The respective configuration schema.
   * @param callable $callable
   *   The callable to apply to each leaf element. The callable will be called
   *   with the leaf element and the element key as arguments.
   * @param string|null $base_key
   *   (optional) The base key that the schema belongs to. This should be NULL
   *   for the top-level schema and be populated consecutively when recursing
   *   into the schema structure.
   */
  protected function traverseSchema(TraversableTypedDataInterface $schema, Config $source_config, LanguageConfigOverride $translation_config, $callable, $base_key = NULL) {
    foreach ($schema as $key => $element) {
      $element_key = implode('.', array_filter([$base_key, $key]));

      // We only care for strings here, so traverse the schema further in the
      // case of traversable elements.
      if ($element instanceof TraversableTypedDataInterface) {
        $this->traverseSchema($element, $source_config, $translation_config, $callable, $element_key);
      }
      // Skip elements which are not translatable.
      elseif (!empty($element->getDataDefinition()['translatable'])) {
        $callable(
          $source_config->get($element_key),
          $translation_config->getLangcode(),
          $translation_config->get($element_key)
        );
      }
    }
  }

  /**
   * Saves a translation string.
   *
   * @param string $source_value
   *   The source string value.
   * @param string $langcode
   *   The language code of the translation.
   * @param string|null $translation_value
   *   (optional) The translation string value. If omitted, no translation will
   *   be saved.
   */
  protected function saveTranslation($source_value, $langcode, $translation_value = NULL) {
    if ($translation_value && ($translation = $this->getTranslation($source_value, $langcode, TRUE))) {
      if ($translation->isNew() || $translation->getString() != $translation_value) {
        $translation
          ->setString($translation_value)
          ->save();
      }
    }
  }

  /**
   * Saves a translation string and marks it as customized.
   *
   * @param string $source_value
   *   The source string value.
   * @param string $langcode
   *   The language code of the translation.
   * @param string|null $translation_value
   *   (optional) The translation string value. If omitted, a customized string
   *   with the source value will be saved.
   *
   * @see \Drupal\locale\LocaleConfigSubscriber::onDelete()
   */
  protected function saveCustomizedTranslation($source_value, $langcode, $translation_value = NULL) {
    if ($translation = $this->getTranslation($source_value, $langcode, TRUE)) {
      if (!isset($translation_value)) {
        $translation_value = $source_value;
      }
      if ($translation->isNew() || $translation->getString() != $translation_value) {
        $translation
          ->setString($translation_value)
          ->setCustomized(TRUE)
          ->save();
      }
    }
  }

  /**
   * Deletes a translation string, if it exists.
   *
   * @param string $source_value
   *   The source string value.
   * @param string $langcode
   *   The language code of the translation.
   *
   * @see \Drupal\locale\LocaleConfigSubscriber::onDelete()
   */
  protected function deleteTranslation($source_value, $langcode) {
    if ($translation = $this->getTranslation($source_value, $langcode, FALSE)) {
      $translation->delete();
    }
  }

  /**
   * Gets a translation string.
   *
   * @param string $source_value
   *   The source string value.
   * @param string $langcode
   *   The language code of the translation.
   * @param bool $create_fallback
   *   (optional) By default if a source string could be found and no
   *   translation in the given language exists yet, a translation object is
   *   created. This can be circumvented by passing FALSE.
   *
   * @return \Drupal\locale\TranslationString|null
   *   The translation string if one was found or created.
   */
  protected function getTranslation($source_value, $langcode, $create_fallback = TRUE) {
    // There is no point in creating a translation without a source.
    if ($source_string = $this->stringStorage->findString(['source' => $source_value])) {
      // Get the translation for this original source string from locale.
      $conditions = [
        'lid' => $source_string->lid,
        'language' => $langcode,
      ];
      $translations = $this->stringStorage->getTranslations($conditions + ['translated' => TRUE]);
      if ($translations) {
        return reset($translations);
      }
      elseif ($create_fallback) {
        return $this->stringStorage->createTranslation($conditions);
      }
    }
  }

}
