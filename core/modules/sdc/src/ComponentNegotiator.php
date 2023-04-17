<?php

namespace Drupal\sdc;

use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Theme\ActiveTheme;
use Drupal\Core\Theme\ThemeManagerInterface;

/**
 * Determines which component should be used.
 *
 * @internal
 */
final class ComponentNegotiator {

  /**
   * The active theme.
   *
   * @var \Drupal\Core\Theme\ActiveTheme
   */
  protected ActiveTheme $activeTheme;

  /**
   * Holds the component IDs from previous negotiations.
   *
   * @var array
   */
  protected array $cache = [];

  /**
   * Constructs ComponentNegotiator objects.
   *
   * @param \Drupal\Core\Theme\ThemeManagerInterface $themeManager
   *   The theme manager.
   * @param \Drupal\Core\Extension\ModuleExtensionList $moduleExtensionList
   *   The module extension list.
   */
  public function __construct(
    protected ThemeManagerInterface $themeManager,
    protected ModuleExtensionList $moduleExtensionList
  ) {
    $this->activeTheme = $this->themeManager->getActiveTheme();
  }

  /**
   * Negotiates the active component for the current request.
   *
   * @param string $component_id
   *   The requested component id. Ex: 'my-button', 'my-button--primary',
   *   'sdc_example:my-button--primary', ...
   * @param array[] $all_definitions
   *   All the plugin definitions for components keyed by plugin ID.
   *
   * @return string|null
   *   The negotiated plugin ID or null if no negotiation was successful.
   */
  public function negotiate(string $component_id, array $all_definitions): ?string {
    $cache_key = $this->generateCacheKey($component_id);
    $cached_data = $this->cache[$cache_key] ?? NULL;
    if (isset($cached_data)) {
      return $cached_data;
    }
    $negotiated = $this->doNegotiate($component_id, $all_definitions);
    $this->cache[$cache_key] = $negotiated;
    return $negotiated;
  }

  /**
   * Negotiates the active component for the current request.
   *
   * @param string $component_id
   *   The requested component id. Ex: 'my-button', 'my-button--primary',
   *   'sdc_example:my-button--primary', ...
   * @param array[] $all_definitions
   *   All the plugin definitions for components keyed by plugin ID.
   *
   * @return string|null
   *   The negotiated plugin ID or null if no negotiation was successful.
   */
  private function doNegotiate(string $component_id, array $all_definitions): ?string {
    // Consider only the component definitions matching the component ID in the
    // 'replaces' key.
    $matches = array_filter(
      $all_definitions,
      static fn(array $definition) => $component_id === ($definition['replaces'] ?? NULL),
    );
    $negotiated_plugin_id = $this->maybeNegotiateByTheme($matches);
    if ($negotiated_plugin_id) {
      return $negotiated_plugin_id;
    }
    return $this->maybeNegotiateByModule($matches);
  }

  /**
   * See if there is a candidate in the theme hierarchy.
   *
   * @param array[] $candidates
   *   All the components that might be a match.
   *
   * @return string|null
   *   The plugin ID for the negotiated component, or NULL if none was found.
   */
  private function maybeNegotiateByTheme(array $candidates): ?string {
    // Prepare the error message.
    $theme_name = $this->activeTheme->getName();
    // Let's do theme based negotiation.
    $base_theme_names = array_map(
      static fn(Extension $extension) => $extension->getName(),
      $this->activeTheme->getBaseThemeExtensions()
    );
    $considered_themes = [$theme_name, ...$base_theme_names];
    // Only consider components in the theme hierarchy tree.
    $candidates = array_filter(
      $candidates,
      static fn(array $definition) => $definition['extension_type'] === ExtensionType::Theme
        && in_array($definition['provider'], $considered_themes, TRUE)
    );
    if (empty($candidates)) {
      return NULL;
    }
    $theme_weights = array_flip($considered_themes);
    $sort_by_theme_weight = static fn(array $definition_a, array $definition_b) =>
      $theme_weights[$definition_a['provider']] <=> $theme_weights[$definition_b['provider']];
    // Sort the candidates by weight and choose the one with the lowest weight.
    uasort($candidates, $sort_by_theme_weight);
    $definition = reset($candidates);
    return $definition['id'] ?? NULL;
  }

  /**
   * Negotiate the component from the list of candidates for a module.
   *
   * @param array[] $candidates
   *   The candidate definitions.
   *
   * @return string|null
   *   The negotiated plugin ID, or NULL if none found.
   */
  private function maybeNegotiateByModule(array $candidates): ?string {
    $module_list = $this->moduleExtensionList->getList();
    if (!$module_list) {
      return NULL;
    }
    $candidates = array_filter(
      $candidates,
      static fn(array $definition) => $definition['extension_type'] === ExtensionType::Module
    );
    $sort_by_module_weight_and_name = static function (array $definition_a, array $definition_b) use ($module_list) {
      $a_weight = $module_list[$definition_a['provider']]?->weight ?? 999;
      $b_weight = $module_list[$definition_b['provider']]?->weight ?? 999;
      return $a_weight !== $b_weight
        ? $a_weight <=> $b_weight
        : $definition_a['provider'] <=> $definition_b['provider'];
    };
    uasort($candidates, $sort_by_module_weight_and_name);
    $definition = reset($candidates);
    return $definition ? ($definition['id'] ?? NULL) : NULL;
  }

  /**
   * Generates the cache key for the current theme and the provided component.
   *
   * @param string $component_id
   *   The component ID.
   *
   * @return string
   *   The cache key.
   */
  private function generateCacheKey(string $component_id): string {
    return sprintf(
      'sdc-negotiation::%s::%s',
      $component_id,
      $this->activeTheme->getName()
    );
  }

  /**
   * Clears the negotiation cache.
   */
  public function clearCache(): void {
    $this->cache = [];
  }

}
