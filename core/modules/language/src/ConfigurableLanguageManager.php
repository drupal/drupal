<?php

/**
 * @file
 * Contains \Drupal\language\ConfigurableLanguageManager.
 */

namespace Drupal\language;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageDefault;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\StringTranslation\TranslatableString;
use Drupal\Core\Url;
use Drupal\language\Config\LanguageConfigFactoryOverrideInterface;
use Drupal\language\Entity\ConfigurableLanguage;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Overrides default LanguageManager to provide configured languages.
 */
class ConfigurableLanguageManager extends LanguageManager implements ConfigurableLanguageManagerInterface {

  /**
   * The configuration storage service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The language configuration override service.
   *
   * @var \Drupal\language\Config\LanguageConfigFactoryOverrideInterface
   */
  protected $configFactoryOverride;

  /**
   * The request object.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The language negotiator.
   *
   * @var \Drupal\language\LanguageNegotiatorInterface
   */
  protected $negotiator;

  /**
   * Local cache for language type configuration data.
   *
   * @var array
   */
  protected $languageTypes;

  /**
   * Local cache for language type information.
   *
   * @var array
   */
  protected $languageTypesInfo;

  /**
   * An array of language objects keyed by language type.
   *
   * @var \Drupal\Core\Language\LanguageInterface[]
   */
  protected $negotiatedLanguages;

  /**
   * An array of language negotiation method IDs keyed by language type.
   *
   * @var array
   */
  protected $negotiatedMethods;

  /**
   * Whether or not the language manager has been initialized.
   *
   * @var bool
   */
  protected $initialized = FALSE;

  /**
   * Whether already in the process of language initialization.
   *
   * @var bool
   */
  protected $initializing = FALSE;

  /**
   * {@inheritdoc}
   */
  public static function rebuildServices() {
    \Drupal::service('kernel')->invalidateContainer();
  }

  /**
   * Constructs a new ConfigurableLanguageManager object.
   *
   * @param \Drupal\Core\Language\LanguageDefault $default_language
   *   The default language service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\language\Config\LanguageConfigFactoryOverrideInterface $config_override
   *   The language configuration override service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack object.
   */
  public function __construct(LanguageDefault $default_language, ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, LanguageConfigFactoryOverrideInterface $config_override, RequestStack $request_stack) {
    $this->defaultLanguage = $default_language;
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
    $this->configFactoryOverride = $config_override;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public function init() {
    if (!$this->initialized) {
      foreach ($this->getDefinedLanguageTypes() as $type) {
        $this->getCurrentLanguage($type);
      }
      $this->initialized = TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isMultilingual() {
    return count($this->getLanguages(LanguageInterface::STATE_CONFIGURABLE)) > 1;
  }

  /**
   * {@inheritdoc}
   */
  public function getLanguageTypes() {
    $this->loadLanguageTypesConfiguration();
    return $this->languageTypes['configurable'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinedLanguageTypes() {
    $this->loadLanguageTypesConfiguration();
    return $this->languageTypes['all'];
  }

  /**
   * Retrieves language types from the configuration storage.
   *
   * @return array
   *   An array of language type names.
   */
  protected function loadLanguageTypesConfiguration() {
    if (!$this->languageTypes) {
      $this->languageTypes = $this->configFactory->get('language.types')->get() ?: array('configurable' => array(), 'all' => parent::getLanguageTypes());
    }
    return $this->languageTypes;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinedLanguageTypesInfo() {
    if (!isset($this->languageTypesInfo)) {
      $defaults = parent::getDefinedLanguageTypesInfo();

      $info = $this->moduleHandler->invokeAll('language_types_info');
      $language_info = $info + $defaults;

      // Let other modules alter the list of language types.
      $this->moduleHandler->alter('language_types_info', $language_info);
      $this->languageTypesInfo = $language_info;
    }
    return $this->languageTypesInfo;
  }

  /**
   * {@inheritdoc}
   */
  public function saveLanguageTypesConfiguration(array $values) {
    $config = $this->configFactory->getEditable('language.types');
    if (isset($values['configurable'])) {
      $config->set('configurable', $values['configurable']);
    }
    if (isset($values['all'])) {
      $config->set('all', $values['all']);
    }
    $config->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentLanguage($type = LanguageInterface::TYPE_INTERFACE) {
    if (!isset($this->negotiatedLanguages[$type])) {
      // Ensure we have a valid value for this language type.
      $this->negotiatedLanguages[$type] = $this->getDefaultLanguage();

      if ($this->negotiator && $this->isMultilingual()) {
        if (!$this->initializing) {
          $this->initializing = TRUE;
          $negotiation = $this->negotiator->initializeType($type);
          $this->negotiatedLanguages[$type] = reset($negotiation);
          $this->negotiatedMethods[$type] = key($negotiation);
          $this->initializing = FALSE;
        }
        // If the current interface language needs to be retrieved during
        // initialization we return the system language. This way string
        // translation calls happening during initialization will return the
        // original strings which can be translated by calling them again
        // afterwards. This can happen for instance while parsing negotiation
        // method definitions.
        elseif ($type == LanguageInterface::TYPE_INTERFACE) {
          return new Language(array('id' => LanguageInterface::LANGCODE_SYSTEM));
        }
      }
    }

    return $this->negotiatedLanguages[$type];
  }

  /**
   * {@inheritdoc}
   */
  public function reset($type = NULL) {
    if (!isset($type)) {
      $this->initialized = FALSE;
      $this->negotiatedLanguages = array();
      $this->negotiatedMethods = array();
      $this->languageTypes = NULL;
      $this->languageTypesInfo = NULL;
      $this->languages = array();
      if ($this->negotiator) {
        $this->negotiator->reset();
      }
    }
    elseif (isset($this->negotiatedLanguages[$type])) {
      unset($this->negotiatedLanguages[$type]);
      unset($this->negotiatedMethods[$type]);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getNegotiator() {
    return $this->negotiator;
  }

  /**
   * {@inheritdoc}
   */
  public function setNegotiator(LanguageNegotiatorInterface $negotiator) {
    $this->negotiator = $negotiator;
    $this->initialized = FALSE;
    $this->negotiatedLanguages = array();
  }

  /**
   * {@inheritdoc}
   */
  public function getLanguages($flags = LanguageInterface::STATE_CONFIGURABLE) {
    // If a config override is set, cache using that language's ID.
    if ($override_language = $this->getConfigOverrideLanguage()) {
      $static_cache_id = $override_language->getId();
    }
    else {
      $static_cache_id = $this->getCurrentLanguage()->getId();
    }

    if (!isset($this->languages[$static_cache_id][$flags])) {
      // Initialize the language list with the default language and default
      // locked languages. These cannot be removed. This serves as a fallback
      // list if this method is invoked while the language module is installed
      // and the configuration entities for languages are not yet fully
      // imported.
      $default = $this->getDefaultLanguage();
      $languages = array($default->getId() => $default);
      $languages += $this->getDefaultLockedLanguages($default->getWeight());

      // Load configurable languages on top of the defaults. Ideally this could
      // use the entity API to load and instantiate ConfigurableLanguage
      // objects. However the entity API depends on the language system, so that
      // would result in infinite loops. We use the configuration system
      // directly and instantiate runtime Language objects. When language
      // entities are imported those cover the default and locked languages, so
      // site-specific configuration will prevail over the fallback values.
      // Having them in the array already ensures if this is invoked in the
      // middle of importing language configuration entities, the defaults are
      // always present.
      $config_ids = $this->configFactory->listAll('language.entity.');
      foreach ($this->configFactory->loadMultiple($config_ids) as $config) {
        $data = $config->get();
        $data['name'] = $data['label'];
        $languages[$data['id']] = new Language($data);
      }
      Language::sort($languages);

      // Filter the full list of languages based on the value of $flags.
      $this->languages[$static_cache_id][$flags] = $this->filterLanguages($languages, $flags);
    }

    return $this->languages[$static_cache_id][$flags];
  }

  /**
   * {@inheritdoc}
   */
  public function getNativeLanguages() {
    $languages = $this->getLanguages(LanguageInterface::STATE_CONFIGURABLE);
    $natives = array();

    $original_language = $this->getConfigOverrideLanguage();

    foreach ($languages as $langcode => $language) {
      $this->setConfigOverrideLanguage($language);
      $natives[$langcode] = ConfigurableLanguage::load($langcode);
    }
    $this->setConfigOverrideLanguage($original_language);
    Language::sort($natives);
    return $natives;
  }

  /**
   * {@inheritdoc}
   */
  public function updateLockedLanguageWeights() {
    // Get the weight of the last configurable language.
    $configurable_languages = $this->getLanguages(LanguageInterface::STATE_CONFIGURABLE);
    $max_weight = end($configurable_languages)->getWeight();

    $locked_languages = $this->getLanguages(LanguageInterface::STATE_LOCKED);
    // Update locked language weights to maintain the existing order, if
    // necessary.
    if (reset($locked_languages)->getWeight() <= $max_weight) {
      foreach ($locked_languages as $language) {
        // Update system languages weight.
        $max_weight++;
        ConfigurableLanguage::load($language->getId())
          ->setWeight($max_weight)
          ->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackCandidates(array $context = array()) {
    if ($this->isMultilingual()) {
      $candidates = array();
      if (empty($context['operation']) || $context['operation'] != 'locale_lookup') {
        // If the fallback context is not locale_lookup, initialize the
        // candidates with languages ordered by weight and add
        // LanguageInterface::LANGCODE_NOT_SPECIFIED at the end. Interface
        // translation fallback should only be based on explicit configuration
        // gathered via the alter hooks below.
        $candidates = array_keys($this->getLanguages());
        $candidates[] = LanguageInterface::LANGCODE_NOT_SPECIFIED;
        $candidates = array_combine($candidates, $candidates);

        // The first candidate should always be the desired language if
        // specified.
        if (!empty($context['langcode'])) {
          $candidates = array($context['langcode'] => $context['langcode']) + $candidates;
        }
      }

      // Let other modules hook in and add/change candidates.
      $type = 'language_fallback_candidates';
      $types = array();
      if (!empty($context['operation'])) {
        $types[] = $type . '_' .  $context['operation'];
      }
      $types[] = $type;
      $this->moduleHandler->alter($types, $candidates, $context);
    }
    else {
      $candidates = parent::getFallbackCandidates($context);
    }

    return $candidates;
  }

  /**
   * {@inheritdoc}
   */
  public function getLanguageSwitchLinks($type, Url $url) {
    $links = FALSE;

    if ($this->negotiator) {
      foreach ($this->negotiator->getNegotiationMethods($type) as $method_id => $method) {
        $reflector = new \ReflectionClass($method['class']);

        if ($reflector->implementsInterface('\Drupal\language\LanguageSwitcherInterface')) {
          $result = $this->negotiator->getNegotiationMethodInstance($method_id)->getLanguageSwitchLinks($this->requestStack->getCurrentRequest(), $type, $url);

          if (!empty($result)) {
            // Allow modules to provide translations for specific links.
            $this->moduleHandler->alter('language_switch_links', $result, $type, $path);
            $links = (object) array('links' => $result, 'method_id' => $method_id);
            break;
          }
        }
      }
    }

    return $links;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfigOverrideLanguage(LanguageInterface $language = NULL) {
    $this->configFactoryOverride->setLanguage($language);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigOverrideLanguage() {
    return $this->configFactoryOverride->getLanguage();
  }

  /**
   * {@inheritdoc}
   */
  public function getLanguageConfigOverride($langcode, $name) {
    return $this->configFactoryOverride->getOverride($langcode, $name);
  }

  /**
   * {@inheritdoc}
   */
  public function getLanguageConfigOverrideStorage($langcode) {
    return $this->configFactoryOverride->getStorage($langcode);
  }

  /**
   * {@inheritdoc}
   */
  public function getStandardLanguageListWithoutConfigured() {
    $languages = $this->getLanguages();
    $predefined = $this->getStandardLanguageList();
    foreach ($predefined as $key => $value) {
      if (isset($languages[$key])) {
        unset($predefined[$key]);
        continue;
      }
      $predefined[$key] = new TranslatableString($value[0]);
    }
    asort($predefined);
    return $predefined;
  }

  /**
   * {@inheritdoc}
   */
  public function getNegotiatedLanguageMethod($type = LanguageInterface::TYPE_INTERFACE) {
    if (isset($this->negotiatedLanguages[$type]) && isset($this->negotiatedMethods[$type])) {
      return $this->negotiatedMethods[$type];
    }
  }

}
