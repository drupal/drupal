<?php

/**
 * @file
 * Contains \Drupal\config_translation\ConfigMapperManager.
 */

namespace Drupal\config_translation;

use Drupal\Component\Utility\String;
use Drupal\config_translation\Exception\InvalidMapperDefinitionException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\Schema\ArrayElement;
use Drupal\Core\Config\TypedConfigManager;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManager;
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
   * {@inheritdoc}
   */
  protected $defaults = array(
    'title' => '',
    'names' => array(),
    'weight' => 20,
    'class' => '\Drupal\config_translation\ConfigNamesMapper',
    'list_controller' => 'Drupal\config_translation\Controller\ConfigTranslationEntityListController',
  );

  /**
   * Constructs a ConfigMapperManager.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   * @param \Drupal\Core\Language\LanguageManager $language_manager
   *   The language manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Config\TypedConfigManager $typed_config_manager
   *   The typed config manager.
   */
  public function __construct(CacheBackendInterface $cache_backend, LanguageManager $language_manager, ModuleHandlerInterface $module_handler, TypedConfigManager $typed_config_manager) {
    $this->typedConfigManager = $typed_config_manager;

    // Look at all themes and modules.
    $directories = array();
    foreach ($module_handler->getModuleList() as $module => $filename) {
      $directories[$module] = dirname($filename);
    }
    foreach ($this->getThemeList() as $theme) {
      $directories[$theme->name] = drupal_get_path('theme', $theme->name);
    }

    // Check for files named MODULE.config_translation.yml and
    // THEME.config_translation.yml in module/theme roots.
    $this->discovery = new YamlDiscovery('config_translation', $directories);
    $this->discovery = new InfoHookDecorator($this->discovery, 'config_translation_info');
    $this->discovery = new ContainerDerivativeDiscoveryDecorator($this->discovery);

    $this->factory = new ContainerFactory($this);

    // Let others alter definitions with hook_config_translation_info_alter().
    $this->alterInfo($module_handler, 'config_translation_info');
    $this->setCacheBackend($cache_backend, $language_manager, 'config_translation_info_plugins');
  }

  /**
   * Returns the list of themes on the site.
   *
   * @param bool $refresh
   *   Whether to refresh the cached theme list.
   *
   * @return array
   *   An associative array of the currently available themes. The keys are the
   *   themes' machine names and the values are objects. See list_themes() for
   *   documentation on those objects.
   *
   * @todo Remove this once https://drupal.org/node/2109287 is fixed in core.
   */
  protected function getThemeList($refresh = FALSE) {
    return list_themes($refresh);
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
      throw new InvalidMapperDefinitionException($plugin_id, String::format("The plugin definition of the mapper '%plugin_id' does not contain a base_route_name.", array('%plugin_id' => $plugin_id)));
    }

    if (!is_subclass_of($definition['list_controller'], 'Drupal\config_translation\Controller\ConfigTranslationEntityListControllerInterface')) {
      throw new InvalidMapperDefinitionException($plugin_id, String::format("The list_controller '%list_controller' for plugin '%plugin_id' does not implement the expected interface Drupal\config_translation\Controller\ConfigTranslationEntityListControllerInterface.", array('%list_controller' => $definition['list_controller'], '%plugin_id' => $plugin_id)));
    }
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
      $definition = $element->getDefinition();
      return isset($definition['translatable']) && $definition['translatable'];
    }
  }

}
