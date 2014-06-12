<?php

/**
 * @file
 * Contains \Drupal\block\BlockManager.
 */

namespace Drupal\block;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\Context\ContextAwarePluginManagerTrait;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Manages discovery and instantiation of block plugins.
 *
 * @todo Add documentation to this class.
 *
 * @see \Drupal\block\BlockPluginInterface
 */
class BlockManager extends DefaultPluginManager implements BlockManagerInterface {

  use StringTranslationTrait;
  use ContextAwarePluginManagerTrait;

  /**
   * An array of all available modules and their data.
   *
   * @var array
   */
  protected $moduleData;

  /**
   * Constructs a new \Drupal\block\BlockManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation manager.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, TranslationInterface $string_translation) {
    parent::__construct('Plugin/Block', $namespaces, $module_handler, 'Drupal\block\Annotation\Block');

    $this->alterInfo('block');
    $this->setCacheBackend($cache_backend, 'block_plugins');
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    // Ensure that every block has a category.
    if (empty($definition['category'])) {
      $definition['category'] = $this->getModuleName($definition['provider']);
    }
  }

  /**
   * Gets the name of the module.
   *
   * @param string $module
   *   The machine name of a module.
   *
   * @return string
   *   The human-readable module name if it exists, otherwise the
   *   machine-readable module name.
   */
  protected function getModuleName($module) {
    // Gather module data.
    if (!isset($this->moduleData)) {
      $this->moduleData = system_get_info('module');
    }
    // If the module exists, return its human-readable name.
    if (isset($this->moduleData[$module])) {
      return $this->t($this->moduleData[$module]['name']);
    }
    // Otherwise, return the machine name.
    return $module;
  }

  /**
   * {@inheritdoc}
   */
  public function getCategories() {
    $categories = array_unique(array_values(array_map(function ($definition) {
      return $definition['category'];
    }, $this->getDefinitions())));
    natcasesort($categories);
    return $categories;
  }

  /**
   * {@inheritdoc}
   */
  public function getSortedDefinitions() {
    // Sort the plugins first by category, then by label.
    $definitions = $this->getDefinitionsForContexts();
    uasort($definitions, function ($a, $b) {
      if ($a['category'] != $b['category']) {
        return strnatcasecmp($a['category'], $b['category']);
      }
      return strnatcasecmp($a['admin_label'], $b['admin_label']);
    });
    return $definitions;
  }

}
