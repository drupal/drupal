<?php

/**
 * @file
 * Contains Drupal\language\HttpKernel\PathProcessorLanguage.
 */

namespace Drupal\language\HttpKernel;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\language\ConfigurableLanguageManagerInterface;
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
   * Constructs a PathProcessorLanguage object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   A config factory object for retrieving configuration settings.
   * @param \Drupal\language\ConfigurableLanguageManagerInterface $language_manager
   *   The configurable language manager.
   * @param \Drupal\language\LanguageNegotiatorInterface
   *   The language negotiator.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current active user.
   */
  public function __construct(ConfigFactoryInterface $config, ConfigurableLanguageManagerInterface $language_manager, LanguageNegotiatorInterface $negotiator, AccountInterface $current_user) {
    $this->config = $config;
    $this->languageManager = $language_manager;
    $this->negotiator = $negotiator;
    $this->negotiator->setCurrentUser($current_user);
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
  public function processOutbound($path, &$options = array(), Request $request = NULL) {
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
        $path = $instance->processOutbound($path, $options, $request);
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
    $this->processors[$scope] = array();
    foreach ($this->languageManager->getLanguageTypes() as $type) {
      foreach ($this->negotiator->getNegotiationMethods($type) as $method_id => $method) {
        if (!isset($this->processors[$scope][$method_id])) {
          $reflector = new \ReflectionClass($method['class']);
          if ($reflector->implementsInterface($interface)) {
            $this->processors[$scope][$method_id] = $this->negotiator->getNegotiationMethodInstance($method_id);
          }
        }
      }
    }
  }

}
