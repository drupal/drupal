<?php

namespace Drupal\Core\Plugin;

use Drupal\Core\Extension\Exception\UnknownExtensionException;
use Drupal\Core\Extension\ModuleExtensionList;
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
    try {
      return $this->getModuleExtensionList()->getName($provider);
    }
    catch (UnknownExtensionException) {
      // Otherwise, return the machine name.
      return $provider;
    }
  }

  /**
   * Returns the module handler used.
   *
   * @return \Drupal\Core\Extension\ModuleHandlerInterface
   *   The module handler.
   *
   * @deprecated in drupal:10.3.0 and is removed from drupal:12.0.0. There is no
   *   replacement.
   *
   * @see https://www.drupal.org/node/3310017
   */
  public function getModuleHandler() {
    @trigger_error(__METHOD__ . '() is deprecated in drupal:10.3.0 and is removed from drupal:12.0.0. There is no replacement. See https://www.drupal.org/node/3310017', E_USER_DEPRECATED);
    // If the class has an injected module handler, use it. Otherwise fall back
    // to fetch it from the service container.
    if (isset($this->moduleHandler)) {
      return $this->moduleHandler;
    }
    return \Drupal::moduleHandler();
  }

  /**
   * Returns the module extension list used.
   *
   * @return \Drupal\Core\Extension\ModuleExtensionList
   *   The module extension list.
   */
  protected function getModuleExtensionList(): ModuleExtensionList {
    // If the class has an injected module extension list, use it. Otherwise
    // fall back to fetch it from the service container.
    if (isset($this->moduleExtensionList)) {
      return $this->moduleExtensionList;
    }
    return \Drupal::service('extension.list.module');
  }

  /**
   * {@inheritdoc}
   */
  public function getCategories() {
    // Fetch all categories from definitions and remove duplicates.
    $categories = array_unique(array_values(array_map(function ($definition) {
      return $definition['category'];
    }, $this->getDefinitions())));
    natcasesort($categories);
    return $categories;
  }

  /**
   * {@inheritdoc}
   */
  public function getSortedDefinitions(?array $definitions = NULL, $label_key = 'label') {
    // Sort the plugins first by category, then by label.
    $definitions = $definitions ?? $this->getDefinitions();
    uasort($definitions, function ($a, $b) use ($label_key) {
      if ((string) $a['category'] != (string) $b['category']) {
        return strnatcasecmp($a['category'], $b['category']);
      }
      return strnatcasecmp($a[$label_key], $b[$label_key]);
    });
    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupedDefinitions(?array $definitions = NULL, $label_key = 'label') {
    /** @var \Drupal\Core\Plugin\CategorizingPluginManagerTrait|\Drupal\Component\Plugin\PluginManagerInterface $this */
    $definitions = $this->getSortedDefinitions($definitions ?? $this->getDefinitions(), $label_key);
    $grouped_definitions = [];
    foreach ($definitions as $id => $definition) {
      $grouped_definitions[(string) $definition['category']][$id] = $definition;
    }
    return $grouped_definitions;
  }

}
