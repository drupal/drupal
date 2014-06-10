<?php

/**
 * @file
 * Contains \Drupal\language\LanguageNegotiator.
 */

namespace Drupal\language;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\HttpFoundation\Request;

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
   * @return \Drupal\Core\Site\Settings
   */
  protected $settings;

  /**
   * The request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The current active user.
   *
   * @return \Drupal\Core\Session\AccountInterface
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
   * @var array
   */
  protected $negotiatedLanguages;

  /**
   * Constructs a new LanguageNegotiator object.
   *
   * @param \Drupal\language\ConfigurableLanguageManagerInterface $language_manager
   *    The language manager.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $negotiator_manager
   *   The language negotiation methods plugin manager
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Site\Settings $settings
   *   The settings instance.
   */
  public function __construct(ConfigurableLanguageManagerInterface $language_manager, PluginManagerInterface $negotiator_manager, ConfigFactoryInterface $config_factory, Settings $settings) {
    $this->languageManager = $language_manager;
    $this->negotiatorManager = $negotiator_manager;
    $this->configFactory = $config_factory;
    $this->settings = $settings;
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
    $this->negotiatedLanguages = array();
    $this->methods = array();
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
  public function setRequest(Request $request) {
    $this->request = $request;
    $this->reset();
  }

  /**
   * {@inheritdoc}
   */
  public function initializeType($type) {
    $language = NULL;

    if ($this->currentUser && $this->request) {
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
      $language->method_id = LanguageNegotiatorInterface::METHOD_ID;
    }

    return $language;
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
    return $this->configFactory->get('language.types')->get('negotiation.' . $type . '.enabled') ?: array();
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

      // Check for a cache mode force from settings.php.
      if ($this->settings->get('page_cache_without_database')) {
        $cache_enabled = TRUE;
      }
      else {
        $cache_enabled = $this->configFactory->get('system.performance')->get('cache.page.use_internal');
      }

      // If the language negotiation method has no cache preference or this is
      // satisfied we can execute the callback.
      if ($cache = !isset($method['cache']) || $this->currentUser->isAuthenticated() || $method['cache'] == $cache_enabled) {
        $langcode = $this->getNegotiationMethodInstance($method_id)->getLangcode($this->request);
      }
    }

    $languages = $this->languageManager->getLanguages();
    return isset($languages[$langcode]) ? $languages[$langcode] : NULL;
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
      $instance = $this->negotiatorManager->createInstance($method_id, array());
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
    $language_types = !empty($type) ? array($type) : $this->languageManager->getLanguageTypes();

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
  function saveConfiguration($type, $enabled_methods) {
    $definitions = $this->getNegotiationMethods();
    $default_types = $this->languageManager->getLanguageTypes();

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
    $this->configFactory->get('language.types')->set('negotiation.' . $type . '.enabled', $enabled_methods)->save();
  }

  /**
   * {@inheritdoc}
   */
  function purgeConfiguration() {
    // Ensure that we are getting the defined language negotiation information.
    // An invocation of \Drupal\Core\Extension\ModuleHandler::install() or
    // \Drupal\Core\Extension\ModuleHandler::uninstall() could invalidate the
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
  function updateConfiguration(array $types) {
    // Ensure that we are getting the defined language negotiation information.
    // An invocation of \Drupal\Core\Extension\ModuleHandler::install() or
    // \Drupal\Core\Extension\ModuleHandler::uninstall() could invalidate the
    // cached information.
    $this->negotiatorManager->clearCachedDefinitions();
    $this->languageManager->reset();

    $language_types = array();
    $language_types_info = $this->languageManager->getDefinedLanguageTypesInfo();
    $method_definitions = $this->getNegotiationMethods();

    foreach ($language_types_info as $type => $info) {
      $configurable = in_array($type, $types);

      // Check whether the language type is unlocked. Only the status of
      // unlocked language types can be toggled between configurable and
      // non-configurable. The default language negotiation settings, if
      // available, are stored in $info['fixed'].
      if (empty($info['locked'])) {
        // If we have a non-locked non-configurable language type without
        // default language negotiation settings, we use the values negotiated
        // for the interface language which should always be available.
        if (!$configurable && !empty($info['fixed'])) {
          $method_weights = array(LanguageNegotiationUI::METHOD_ID);
          $method_weights = array_flip($method_weights);
          $this->saveConfiguration($type, $method_weights);
        }
      }
      else {
        // Locked language types with default settings are always considered
        // non-configurable. In turn if default settings are missing, the
        // language type is always considered configurable.
        $configurable = empty($info['fixed']);

        // If the language is non-configurable we need to store its language
        // negotiation settings.
        if (!$configurable) {
          $method_weights = array();
          foreach ($info['fixed'] as $weight => $method_id) {
            if (isset($method_definitions[$method_id])) {
              $method_weights[$method_id] = $weight;
            }
          }
          $this->saveConfiguration($type, $method_weights);
        }
      }

      $language_types[$type] = $configurable;
    }

    // Store the language type configuration.
    $config = array(
      'configurable' => array_keys(array_filter($language_types)),
      'all' => array_keys($language_types),
    );
    $this->languageManager->saveLanguageTypesConfiguration($config);
  }

}
