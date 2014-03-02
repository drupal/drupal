<?php

/**
 * @file
 * Contains \Drupal\block\Plugin\Type\BlockManager.
 */

namespace Drupal\block\Plugin\Type;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Manages discovery and instantiation of block plugins.
 *
 * @todo Add documentation to this class.
 *
 * @see \Drupal\block\BlockPluginInterface
 */
class BlockManager extends DefaultPluginManager {

  /**
   * An array of all available modules and their data.
   *
   * @var array
   */
  protected $moduleData;

  /**
   * The translation manager.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $translationManager;

  /**
   * Constructs a new \Drupal\block\Plugin\Type\BlockManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Language\LanguageManager $language_manager
   *   The language manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation_manager
   *   The translation manager.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, LanguageManager $language_manager, ModuleHandlerInterface $module_handler, TranslationInterface $translation_manager) {
    parent::__construct('Plugin/Block', $namespaces, $module_handler, 'Drupal\block\Annotation\Block');

    $this->alterInfo('block');
    $this->setCacheBackend($cache_backend, $language_manager, 'block_plugins');
    $this->translationManager = $translation_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    // Ensure that every block has a category.
    if (!isset($definition['category'])) {
      $definition['category'] = $this->getModuleName($definition['provider']);
    }
    // @todo Remove any usage of 'module' from block code.
    if (!isset($definition['module'])) {
      $definition['module'] = $definition['provider'];
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
   * Translates a string to the current language or to a given language.
   *
   * See the t() documentation for details.
   */
  protected function t($string, array $args = array(), array $options = array()) {
    return $this->translationManager->translate($string, $args, $options);
  }

  /**
   * Gets the names of all block categories.
   *
   * @return array
   *   An array of translated categories, sorted alphabetically.
   */
  public function getCategories() {
    $categories = array_unique(array_values(array_map(function ($definition) {
      return $definition['category'];
    }, $this->getDefinitions())));
    natcasesort($categories);
    return $categories;
  }

}
