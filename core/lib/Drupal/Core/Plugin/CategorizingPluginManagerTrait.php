<?php

/**
 * @file
 * Contains \Drupal\Core\Plugin\CategorizingPluginManagerTrait.
 */

namespace Drupal\Core\Plugin;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides a trait for the CategorizingPluginManagerInterface.
 *
 * The trait provides methods for categorizing plugin definitions based on a
 * 'category' key. The plugin manager should make sure there is a default
 * category. For that the trait's processDefinitionCategory() method can be
 * invoked from the processDefinition() method.
 *
 * @see \Drupal\Component\Plugin\CategorizingPluginManagerInterface
 */
trait CategorizingPluginManagerTrait {

  use StringTranslationTrait;

  /**
   * Processes a plugin definition to ensure there is a category.
   *
   * If the definition lacks a category, it defaults to the providing module.
   *
   * @param array $definition
   *   The plugin definition.
   */
  protected function processDefinitionCategory(&$definition) {
    // Ensure that every plugin has a category.
    if (empty($definition['category'])) {
      // Default to the human readable module name if the provider is a module;
      // otherwise the provider machine name is used.
      $definition['category'] = $this->getProviderName($definition['provider']);
    }
  }

  /**
   * Gets the name of a provider.
   *
   * @param string $provider
   *   The machine name of a plugin provider.
   *
   * @return string
   *   The human-readable module name if it exists, otherwise the
   *   machine-readable name passed.
   */
  protected function getProviderName($provider) {
    $list = $this->getModuleHandler()->getModuleList();
    // If the module exists, return its human-readable name.
    if (isset($list[$provider])) {
      return $this->getModuleHandler()->getName($provider);
    }
    // Otherwise, return the machine name.
    return $provider;
  }

  /**
   * Returns the module handler used.
   *
   * @return \Drupal\Core\Extension\ModuleHandlerInterface
   *   The module handler.
   */
  public function getModuleHandler() {
    // If the class has an injected module handler, use it. Otherwise fall back
    // to fetch it from the service container.
    if (isset($this->moduleHandler)) {
      return $this->moduleHandler;
    }
    return \Drupal::moduleHandler();
  }

  /**
   * Implements \Drupal\Component\Plugin\CategorizingPluginManagerInterface::getCategories().
   */
  public function getCategories() {
    /** @var \Drupal\Core\Plugin\CategorizingPluginManagerTrait|\Drupal\Component\Plugin\PluginManagerInterface $this */
    // Fetch all categories from definitions and remove duplicates.
    $categories = array_unique(array_values(array_map(function ($definition) {
      return $definition['category'];
    }, $this->getDefinitions())));
    natcasesort($categories);
    return $categories;
  }

  /**
   * Implements \Drupal\Component\Plugin\CategorizingPluginManagerInterface::getSortedDefinitions().
   */
  public function getSortedDefinitions(array $definitions = NULL, $label_key = 'label') {
    // Sort the plugins first by category, then by label.
    /** @var \Drupal\Core\Plugin\CategorizingPluginManagerTrait|\Drupal\Component\Plugin\PluginManagerInterface $this */
    $definitions = isset($definitions) ? $definitions : $this->getDefinitions();
    uasort($definitions, function ($a, $b) use ($label_key) {
      if ($a['category'] != $b['category']) {
        return strnatcasecmp($a['category'], $b['category']);
      }
      return strnatcasecmp($a[$label_key], $b[$label_key]);
    });
    return $definitions;
  }

  /**
   * Implements \Drupal\Component\Plugin\CategorizingPluginManagerInterface::getGroupedDefinitions().
   */
  public function getGroupedDefinitions(array $definitions = NULL, $label_key = 'label') {
    /** @var \Drupal\Core\Plugin\CategorizingPluginManagerTrait|\Drupal\Component\Plugin\PluginManagerInterface $this */
    $definitions = $this->getSortedDefinitions(isset($definitions) ? $definitions : $this->getDefinitions(), $label_key);
    $grouped_definitions = array();
    foreach ($definitions as $id => $definition) {
      $grouped_definitions[(string) $definition['category']][$id] = $definition;
    }
    return $grouped_definitions;
  }

}
