<?php

namespace Drupal\language\HttpKernel;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\language\ConfigurableLanguageManagerInterface;
use Drupal\language\EventSubscriber\ConfigSubscriber;
use Drupal\language\LanguageNegotiatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Session\AccountInterface;

/**
 * Processes the inbound path using path alias lookups.
 */
class PathProcessorLanguage implements InboundPathProcessorInterface, OutboundPathProcessorInterface {

  /**
   * A config factory for retrieving required config settings.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Language manager for retrieving the url language type.
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
   * Local cache for language path processors.
   *
   * @var array
   */
  protected $processors;

  /**
   * Flag indicating whether the site is multilingual.
   *
   * @var bool
   */
  protected $multilingual;

  /**
   * The language configuration event subscriber.
   *
   * @var \Drupal\language\EventSubscriber\ConfigSubscriber
   */
  protected $configSubscriber;


  /**
   * Constructs a PathProcessorLanguage object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   A config factory object for retrieving configuration settings.
   * @param \Drupal\language\ConfigurableLanguageManagerInterface $language_manager
   *   The configurable language manager.
   * @param \Drupal\language\LanguageNegotiatorInterface $negotiator
   *   The language negotiator.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current active user.
   * @param \Drupal\language\EventSubscriber\ConfigSubscriber $config_subscriber
   *   The language configuration event subscriber.
   */
  public function __construct(ConfigFactoryInterface $config, ConfigurableLanguageManagerInterface $language_manager, LanguageNegotiatorInterface $negotiator, AccountInterface $current_user, ConfigSubscriber $config_subscriber) {
    $this->config = $config;
    $this->languageManager = $language_manager;
    $this->negotiator = $negotiator;
    $this->negotiator->setCurrentUser($current_user);
    $this->configSubscriber = $config_subscriber;
  }

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    if (!empty($path)) {
      $scope = 'inbound';
      if (!isset($this->processors[$scope])) {
        $this->initProcessors($scope);
      }
      foreach ($this->processors[$scope] as $instance) {
        $path = $instance->processInbound($path, $request);
      }
    }
    return $path;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = [], Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    if (!isset($this->multilingual)) {
      $this->multilingual = $this->languageManager->isMultilingual();
    }
    if ($this->multilingual) {
      $this->negotiator->reset();
      $scope = 'outbound';
      if (!isset($this->processors[$scope])) {
        $this->initProcessors($scope);
      }
      foreach ($this->processors[$scope] as $instance) {
        $path = $instance->processOutbound($path, $options, $request, $bubbleable_metadata);
      }
      // No language dependent path allowed in this mode.
      if (empty($this->processors[$scope])) {
        unset($options['language']);
      }
    }
    return $path;
  }

  /**
   * Initializes the local cache for language path processors.
   *
   * @param string $scope
   *   The scope of the processors: "inbound" or "outbound".
   */
  protected function initProcessors($scope) {
    $interface = '\Drupal\Core\PathProcessor\\' . Unicode::ucfirst($scope) . 'PathProcessorInterface';
    $this->processors[$scope] = [];
    $weights = [];
    foreach ($this->languageManager->getLanguageTypes() as $type) {
      foreach ($this->negotiator->getNegotiationMethods($type) as $method_id => $method) {
        if (!isset($this->processors[$scope][$method_id])) {
          $reflector = new \ReflectionClass($method['class']);
          if ($reflector->implementsInterface($interface)) {
            $this->processors[$scope][$method_id] = $this->negotiator->getNegotiationMethodInstance($method_id);
            $weights[$method_id] = $method['weight'];
          }
        }
      }
    }

    // Sort the processors list, so that their functions are called in the
    // order specified by the weight of the methods.
    uksort($this->processors[$scope], function ($method_id_a, $method_id_b) use ($weights) {
      $a_weight = $weights[$method_id_a];
      $b_weight = $weights[$method_id_b];

      if ($a_weight == $b_weight) {
        return 0;
      }

      return ($a_weight < $b_weight) ? -1 : 1;
    });
  }

  /**
   * Initializes the injected event subscriber with the language path processor.
   *
   * The language path processor service is registered only on multilingual
   * site configuration, thus we inject it in the event subscriber only when
   * it is initialized.
   */
  public function initConfigSubscriber() {
    $this->configSubscriber->setPathProcessorLanguage($this);
  }

  /**
   * Resets the collected processors instances.
   */
  public function reset() {
    $this->processors = [];
  }

}
