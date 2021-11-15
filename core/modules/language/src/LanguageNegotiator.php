<?php

namespace Drupal\language;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Site\Settings;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUI;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class responsible for performing language negotiation.
 */
class LanguageNegotiator implements LanguageNegotiatorInterface {

  /**
   * The language negotiation method plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $negotiatorManager;

  /**
   * The language manager.
   *
   * @var \Drupal\language\ConfigurableLanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The settings instance.
   *
   * @var \Drupal\Core\Site\Settings
   */
  protected $settings;

  /**
   * The request stack object.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The current active user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Local cache for language negotiation method instances.
   *
   * @var array
   */
  protected $methods;

  /**
   * An array of language objects keyed by method id.
   *
   * @var \Drupal\Core\Language\LanguageInterface[]
   */
  protected $negotiatedLanguages = [];

  /**
   * Constructs a new LanguageNegotiator object.
   *
   * @param \Drupal\language\ConfigurableLanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $negotiator_manager
   *   The language negotiation methods plugin manager
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Site\Settings $settings
   *   The settings instance.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack service.
   */
  public function __construct(ConfigurableLanguageManagerInterface $language_manager, PluginManagerInterface $negotiator_manager, ConfigFactoryInterface $config_factory, Settings $settings, RequestStack $requestStack) {
    $this->languageManager = $language_manager;
    $this->negotiatorManager = $negotiator_manager;
    $this->configFactory = $config_factory;
    $this->settings = $settings;
    $this->requestStack = $requestStack;
  }

  /**
   * Initializes the injected language manager with the negotiator.
   *
   * This should be called right after instantiating the negotiator to make it
   * available to the language manager without introducing a circular
   * dependency.
   */
  public function initLanguageManager() {
    $this->languageManager->setNegotiator($this);
  }

  /**
   * {@inheritdoc}
   */
  public function reset() {
    $this->negotiatedLanguages = [];
    $this->methods = [];
  }

  /**
   * {@inheritdoc}
   */
  public function setCurrentUser(AccountInterface $current_user) {
    $this->currentUser = $current_user;
    $this->reset();
  }

  /**
   * {@inheritdoc}
   */
  public function initializeType($type) {
    $language = NULL;

    if ($this->currentUser) {
      // Execute the language negotiation methods in the order they were set up
      // and return the first valid language found.
      foreach ($this->getEnabledNegotiators($type) as $method_id => $info) {
        if (!isset($this->negotiatedLanguages[$method_id])) {
          $this->negotiatedLanguages[$method_id] = $this->negotiateLanguage($type, $method_id);
        }

        // Since objects are references, we need to return a clone to prevent
        // the language negotiation method cache from being unintentionally
        // altered. The same methods might be used with different language types
        // based on configuration.
        $language = !empty($this->negotiatedLanguages[$method_id]) ? clone($this->negotiatedLanguages[$method_id]) : NULL;

        if ($language) {
          $this->getNegotiationMethodInstance($method_id)->persist($language);
          break;
        }
      }
    }

    if (!$language) {
      // If no other language was found use the default one.
      $language = $this->languageManager->getDefaultLanguage();
      $method_id = static::METHOD_ID;
    }

    return [$method_id => $language];
  }

  /**
   * Gets enabled detection methods for the provided language type.
   *
   * @param string $type
   *   The language type.
   *
   * @return array
   *   An array of enabled detection methods for the provided language type.
   */
  protected function getEnabledNegotiators($type) {
    return $this->configFactory->get('language.types')->get('negotiation.' . $type . '.enabled') ?: [];
  }

  /**
   * Performs language negotiation using the specified negotiation method.
   *
   * @param string $type
   *   The language type to be initialized.
   * @param string $method_id
   *   The string identifier of the language negotiation method to use to detect
   *   language.
   *
   * @return \Drupal\Core\Language\LanguageInterface|null
   *   Negotiated language object for given type and method, FALSE otherwise.
   */
  protected function negotiateLanguage($type, $method_id) {
    $langcode = NULL;
    $method = $this->negotiatorManager->getDefinition($method_id);

    if (!isset($method['types']) || in_array($type, $method['types'])) {
      $langcode = $this->getNegotiationMethodInstance($method_id)->getLangcode($this->requestStack->getCurrentRequest());
    }

    $languages = $this->languageManager->getLanguages();
    return $languages[$langcode] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getNegotiationMethods($type = NULL) {
    $definitions = $this->negotiatorManager->getDefinitions();
    if (isset($type)) {
      $enabled_methods = $this->getEnabledNegotiators($type);
      $definitions = array_intersect_key($definitions, $enabled_methods);
    }
    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getNegotiationMethodInstance($method_id) {
    if (!isset($this->methods[$method_id])) {
      $instance = $this->negotiatorManager->createInstance($method_id, []);
      $instance->setLanguageManager($this->languageManager);
      $instance->setConfig($this->configFactory);
      $instance->setCurrentUser($this->currentUser);
      $this->methods[$method_id] = $instance;
    }
    return $this->methods[$method_id];
  }

  /**
   * {@inheritdoc}
   */
  public function getPrimaryNegotiationMethod($type) {
    $enabled_methods = $this->getEnabledNegotiators($type);
    return empty($enabled_methods) ? LanguageNegotiatorInterface::METHOD_ID : key($enabled_methods);
  }

  /**
   * {@inheritdoc}
   */
  public function isNegotiationMethodEnabled($method_id, $type = NULL) {
    $enabled = FALSE;
    $language_types = !empty($type) ? [$type] : $this->languageManager->getLanguageTypes();

    foreach ($language_types as $type) {
      $enabled_methods = $this->getEnabledNegotiators($type);
      if (isset($enabled_methods[$method_id])) {
        $enabled = TRUE;
        break;
      }
    }

    return $enabled;
  }

  /**
   * {@inheritdoc}
   */
  public function saveConfiguration($type, $enabled_methods) {
    // As configurable language types might have changed, we reset the cache.
    $this->languageManager->reset();
    $definitions = $this->getNegotiationMethods();
    $default_types = $this->languageManager->getLanguageTypes();

    // Ensure that the weights are integers.
    $enabled_methods = array_map('intval', $enabled_methods);

    // Order the language negotiation method list by weight.
    asort($enabled_methods);
    foreach ($enabled_methods as $method_id => $weight) {
      if (isset($definitions[$method_id])) {
        $method = $definitions[$method_id];
        // If the language negotiation method does not express any preference
        // about types, make it available for any configurable type.
        $types = array_flip(!empty($method['types']) ? $method['types'] : $default_types);
        // Check whether the method is defined and has the right type.
        if (!isset($types[$type])) {
          unset($enabled_methods[$method_id]);
        }
      }
      else {
        unset($enabled_methods[$method_id]);
      }
    }
    $this->configFactory->getEditable('language.types')->set('negotiation.' . $type . '.enabled', $enabled_methods)->save(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function purgeConfiguration() {
    // Ensure that we are getting the defined language negotiation information.
    // An invocation of \Drupal\Core\Extension\ModuleInstaller::install() or
    // \Drupal\Core\Extension\ModuleInstaller::uninstall() could invalidate the
    // cached information.
    $this->negotiatorManager->clearCachedDefinitions();
    $this->languageManager->reset();
    foreach ($this->languageManager->getDefinedLanguageTypesInfo() as $type => $info) {
      $this->saveConfiguration($type, $this->getEnabledNegotiators($type));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function updateConfiguration(array $types) {
    // Ensure that we are getting the defined language negotiation information.
    // An invocation of \Drupal\Core\Extension\ModuleInstaller::install() or
    // \Drupal\Core\Extension\ModuleInstaller::uninstall() could invalidate the
    // cached information.
    $this->negotiatorManager->clearCachedDefinitions();
    $this->languageManager->reset();

    $language_types = [];
    $language_types_info = $this->languageManager->getDefinedLanguageTypesInfo();
    $method_definitions = $this->getNegotiationMethods();

    foreach ($language_types_info as $type => $info) {
      $configurable = in_array($type, $types);

      // The default language negotiation settings, if available, are stored in
      // $info['fixed'].
      $has_default_settings = !empty($info['fixed']);
      // Check whether the language type is unlocked. Only the status of
      // unlocked language types can be toggled between configurable and
      // non-configurable.
      if (empty($info['locked'])) {
        if (!$configurable && !$has_default_settings) {
          // If we have an unlocked non-configurable language type without
          // default language negotiation settings, we use the values
          // negotiated for the interface language which, should always be
          // available.
          $method_weights = [LanguageNegotiationUI::METHOD_ID];
          $method_weights = array_flip($method_weights);
          $this->saveConfiguration($type, $method_weights);
        }
      }
      else {
        // The language type is locked. Locked language types with default
        // settings are always considered non-configurable. In turn if default
        // settings are missing, the language type is always considered
        // configurable.

        // If the language type is locked we can just store its default language
        // negotiation settings if it has some, since it is not configurable.
        if ($has_default_settings) {
          $method_weights = [];
          // Default settings are in $info['fixed'].

          foreach ($info['fixed'] as $weight => $method_id) {
            if (isset($method_definitions[$method_id])) {
              $method_weights[$method_id] = $weight;
            }
          }
          $this->saveConfiguration($type, $method_weights);
        }
        else {
          // It was missing default settings, so force it to be configurable.
          $configurable = TRUE;
        }
      }

      // Accumulate information for each language type so it can be saved later.
      $language_types[$type] = $configurable;
    }

    // Store the language type configuration.
    $config = [
      'configurable' => array_keys(array_filter($language_types)),
      'all' => array_keys($language_types),
    ];
    $this->languageManager->saveLanguageTypesConfiguration($config);
  }

}
