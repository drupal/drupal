<?php

/**
 * @file
 * Contains \Drupal\Core\Menu\Plugin\Derivative\StaticLocalActionDeriver.
 */

namespace Drupal\Core\Menu\Plugin\Derivative;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeInterface;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\StringTranslation\TranslationManager;
use Drupal\Component\Utility\String;
use Drupal\Component\Discovery\YamlDiscovery;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides plugin derivatives for local actions provided in YAML files.
 */
class StaticLocalActionDeriver implements ContainerDerivativeInterface {

  /**
   * List of derivative definitions.
   *
   * @var array
   */
  protected $derivatives = array();

  /**
   * The base plugin ID.
   *
   * @var string
   */
  protected $basePluginId;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The translation manager.
   *
   * @var \Drupal\Core\StringTranslation\TranslationManager
   */
  protected $translationManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('module_handler'),
      $container->get('string_translation')
    );
  }

  /**
   * Constructs a StaticLocalActionDeriver object.
   *
   * @param string $base_plugin_id
   *   The base plugin ID.
   * @param\Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\StringTranslation\TranslationManager translation_manager
   *   The translation manager.
   */
  public function __construct($base_plugin_id, ModuleHandlerInterface $module_handler, TranslationManager $translation_manager) {
    $this->basePluginId = $base_plugin_id;
    $this->moduleHandler = $module_handler;
    $this->translationManager = $translation_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinition($derivative_id, array $base_plugin_definition) {
    if (!empty($this->derivatives) && !empty($this->derivatives[$derivative_id])) {
      return $this->derivatives[$derivative_id];
    }
    $this->getDerivativeDefinitions($base_plugin_definition);
    return $this->derivatives[$derivative_id];
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions(array $base_plugin_definition) {
    $yaml_discovery = new YamlDiscovery('local_actions', $this->moduleHandler->getModuleDirectories());
    $required_keys = array('title' => 1, 'route_name' => 1, 'appears_on' => 1);

    foreach ($yaml_discovery->findAll() as $module => $local_actions) {
      if (!empty($local_actions)) {
        foreach ($local_actions as $name => $info) {
          if ($missing_keys = array_diff_key($required_keys, array_intersect_key($info, $required_keys))) {
            throw new PluginException(String::format('Static local action @name is missing @keys', array('@name' => $name, '@keys' => implode(', ', array_keys($missing_keys)))));
          }

          $info += array('provider' => $module);
          // Make sure 'appears_on' is an array.
          $info['appears_on'] = (array) $info['appears_on'];
          $info['title'] = $this->translationManager->translate($info['title']);
          $this->derivatives[$name] = $info + $base_plugin_definition;
        }
      }
    }

    return $this->derivatives;
  }

}
