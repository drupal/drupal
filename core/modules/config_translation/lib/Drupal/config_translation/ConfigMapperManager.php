<?php

/**
 * @file
 * Contains \Drupal\config_translation\ConfigMapperManager.
 */

namespace Drupal\config_translation;

use Drupal\Component\Utility\String;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\Schema\ArrayElement;
use Drupal\Core\Config\TypedConfigManager;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\InfoHookDecorator;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\Core\Plugin\Factory\ContainerFactory;
use Drupal\Core\TypedData\TypedDataInterface;

/**
 * Manages plugins for configuration translation mappers.
 */
class ConfigMapperManager extends DefaultPluginManager implements ConfigMapperManagerInterface {

  /**
   * The typed config manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManager
   */
  protected $typedConfigManager;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * {@inheritdoc}
   */
  protected $defaults = array(
    'title' => '',
    'names' => array(),
    'weight' => 20,
    'class' => '\Drupal\config_translation\ConfigNamesMapper',
  );

  /**
   * Constructs a ConfigMapperManager.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Config\TypedConfigManager $typed_config_manager
   *   The typed config manager.
   */
  public function __construct(CacheBackendInterface $cache_backend, LanguageManagerInterface $language_manager, ModuleHandlerInterface $module_handler, TypedConfigManager $typed_config_manager, ThemeHandlerInterface $theme_handler) {
    $this->typedConfigManager = $typed_config_manager;

    // Look at all themes and modules.
    $directories = array();
    foreach ($module_handler->getModuleList() as $name => $module) {
      $directories[$name] = $module->getPath();
    }
    foreach ($theme_handler->listInfo() as $theme) {
      $directories[$theme->getName()] = $theme->getPath();
    }

    // Check for files named MODULE.config_translation.yml and
    // THEME.config_translation.yml in module/theme roots.
    $this->discovery = new YamlDiscovery('config_translation', $directories);
    $this->discovery = new InfoHookDecorator($this->discovery, 'config_translation_info');
    $this->discovery = new ContainerDerivativeDiscoveryDecorator($this->discovery);

    $this->factory = new ContainerFactory($this);

    // Let others alter definitions with hook_config_translation_info_alter().
    $this->moduleHandler = $module_handler;
    $this->themeHandler = $theme_handler;

    $this->alterInfo('config_translation_info');
    $this->setCacheBackend($cache_backend, $language_manager, 'config_translation_info_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function getMappers() {
    $mappers = array();
    foreach($this->getDefinitions() as $id => $definition) {
      $mappers[$id] = $this->createInstance($id);
    }

    return $mappers;
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    if (!isset($definition['base_route_name'])) {
      throw new InvalidPluginDefinitionException($plugin_id, String::format("The plugin definition of the mapper '%plugin_id' does not contain a base_route_name.", array('%plugin_id' => $plugin_id)));
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function findDefinitions() {
    $definitions = $this->discovery->getDefinitions();
    foreach ($definitions as $plugin_id => &$definition) {
      $this->processDefinition($definition, $plugin_id);
    }
    if ($this->alterHook) {
      $this->moduleHandler->alter($this->alterHook, $definitions);
    }

    // If this plugin was provided by a module that does not exist, remove the
    // plugin definition.
    foreach ($definitions as $plugin_id => $plugin_definition) {
      if (isset($plugin_definition['provider']) && !in_array($plugin_definition['provider'], array('Core', 'Component')) && (!$this->moduleHandler->moduleExists($plugin_definition['provider']) && !in_array($plugin_definition['provider'], array_keys($this->themeHandler->listInfo())))) {
        unset($definitions[$plugin_id]);
      }
    }
    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function hasTranslatable($name) {
    return $this->findTranslatable($this->typedConfigManager->get($name));
  }

  /**
   * Returns TRUE if at least one translatable element is found.
   *
   * @param \Drupal\Core\TypedData\TypedDataInterface $element
   *   Configuration schema element.
   *
   * @return bool
   *   A boolean indicating if there is at least one translatable element.
   */
  protected function findTranslatable(TypedDataInterface $element) {
    // In case this is a sequence or a mapping check whether any child element
    // is translatable.
    if ($element instanceof ArrayElement) {
      foreach ($element as $child_element) {
        if ($this->findTranslatable($child_element)) {
          return TRUE;
        }
      }
      // If none of the child elements are translatable, return FALSE.
      return FALSE;
    }
    else {
      $definition = $element->getDataDefinition();
      return isset($definition['translatable']) && $definition['translatable'];
    }
  }

}
