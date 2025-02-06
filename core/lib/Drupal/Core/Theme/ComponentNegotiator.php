<?php

namespace Drupal\Core\Theme;

use Drupal\Core\DependencyInjection\DeprecatedServicePropertyTrait;
use Drupal\Core\Extension\Extension;

/**
 * Determines which component should be used.
 */
class ComponentNegotiator {

  use DeprecatedServicePropertyTrait;

  /**
   * {@inheritdoc}
   */
  protected $deprecatedProperties = ['moduleExtensionList' => 'extension.list.module'];

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
   */
  public function __construct(
    protected ThemeManagerInterface $themeManager,
  ) {
  }

  /**
   * Negotiates the active component for the current request.
   *
   * @param string $component_id
   *   The requested component id. Ex: 'my-button', 'my-button--primary',
   *   'component_example:my-button--primary', ...
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
   *   'component_example:my-button--primary', ...
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
    $theme_name = $this->themeManager->getActiveTheme()->getName();
    // Let's do theme based negotiation.
    $base_theme_names = array_map(
      static fn(Extension $extension) => $extension->getName(),
      $this->themeManager->getActiveTheme()->getBaseThemeExtensions()
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
    foreach ($candidates as $candidate) {
      if ($candidate['extension_type'] === ExtensionType::Module) {
        return $candidate['id'];
      }
    }
    return NULL;
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
      'component-negotiation::%s::%s',
      $component_id,
      $this->themeManager->getActiveTheme()->getName()
    );
  }

  /**
   * Clears the negotiation cache.
   */
  public function clearCache(): void {
    $this->cache = [];
  }

}
