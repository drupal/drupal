<?php

declare(strict_types=1);

namespace Drupal\Core\Theme\DesignToken;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;
use Drupal\Core\Plugin\Factory\ContainerFactory;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a design tokens plugin manager.
 *
 * @method \Drupal\Core\Theme\DesignToken\DesignToken|null getDefinition($plugin_id, $exception_on_invalid = TRUE)
 * @method \Drupal\Core\Theme\DesignToken\DesignToken[] getDefinitions()
 *
 * @internal
 *   The design tokens API is experimental and is not meant for production use.
 *   See https://www.drupal.org/core/experimental for more information.
 */
class DesignTokenPluginManager extends DefaultPluginManager implements DesignTokenPluginManagerInterface {

  public function __construct(
    ModuleHandlerInterface $module_handler,
    protected readonly ThemeHandlerInterface $themeHandler,
    CacheBackendInterface $cacheBackend,
    protected readonly DesignTokenValuePluginManagerInterface $designTokenValuePluginManager,
  ) {
    $this->moduleHandler = $module_handler;
    $this->factory = new ContainerFactory($this);
    $this->alterInfo('design_token_info');
    $this->setCacheBackend($cacheBackend, 'design_token', ['design_token']);
  }

  /**
   * {@inheritdoc}
   */
  public function findDefinitions(): array {
    $discovered = parent::findDefinitions();
    return $this->extractDefinitions($discovered);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery(): DiscoveryInterface {
    if (!$this->discovery) {
      $this->discovery = new YamlDiscovery('tokens', $this->moduleHandler->getModuleDirectories() + $this->themeHandler->getThemeDirectories());
    }
    return $this->discovery;
  }

  /**
   * {@inheritdoc}
   */
  protected function providerExists(mixed $provider): bool {
    /** @var string $provider */
    return $this->moduleHandler->moduleExists($provider) || $this->themeHandler->themeExists($provider);
  }

  /**
   * Convert raw plugin definitions from the discovery into a flattened list.
   *
   * YamlDiscovery doesn't know about the nested groups structure of DTCG and
   * may create a plugin by group instead of a plugin by token.
   *
   * @param array $discovered
   *   The raw plugin definitions from the discovery.
   *
   * @return array
   *   The extracted definitions.
   */
  protected function extractDefinitions(array $discovered): array {
    $definitions = [];
    foreach ($discovered as $id => $data) {
      // Id & provider have been automatically added by YamlDiscovery.
      unset($data['id']);
      $provider = $data['provider'];
      unset($data['provider']);
      $tree = $this->parseDtcg($provider, $id, $data);
      $definitions += $this->flattenDefinitions($tree);
    }
    return $definitions;
  }

  /**
   * Parse W3C DTCG format.
   *
   * @param string $provider
   *   The Drupal extension providing the token.
   * @param string $name
   *   The token name.
   * @param array $data
   *   The raw DTCG data.
   * @param ?\Drupal\Core\Theme\DesignToken\Group $parent
   *   The parent group. NULL if root.
   *
   * @return \Drupal\Core\Theme\DesignToken\DtcgInterface|null
   *   A DTCG object.
   */
  protected function parseDtcg(string $provider, string $name, array $data, ?Group $parent = NULL): ?DtcgInterface {
    // phpcs:ignore Drupal.Semantics.FunctionT.NotLiteralString
    $description = isset($data['$description']) ? new TranslatableMarkup($data['$description']) : NULL;

    // An array with a $value property is a token.
    if (isset($data['$value'])) {
      try {
        $data['$value'] = DesignTokenValuePluginBase::prepareConfiguration($data['$value']);
        /** @var \Drupal\Core\Theme\DesignToken\DesignTokenValueInterface $value */
        $value = $this->designTokenValuePluginManager->createInstance($data['$type'] ?? $parent?->type, $data['$value']);
        return new DesignToken(
          $provider,
          $name,
          $value,
          $parent,
          $description,
          $data['$type'] ?? NULL
        );
      }
      catch (PluginException) {
        // Type does not have a design token value plugin or has an invalid
        // structure.
        return NULL;
      }
    }

    // An array without a $value property is a group.
    $group = new Group(
      $provider,
      $name,
      [],
      $parent,
      $description,
      $data['$type'] ?? NULL
    );

    $children = [];
    foreach ($data as $childName => $childData) {
      if (str_starts_with($childName, '$')) {
        continue;
      }

      $child = $this->parseDtcg($provider, $childName, $childData, $group);
      if ($child) {
        $children[$childName] = $child;
      }
    }
    $group->setChildren($children);
    return $group;
  }

  /**
   * Get Design Token plugins from a tree of processed DTCG objects.
   *
   * @param ?\Drupal\Core\Theme\DesignToken\DtcgInterface $dtcgTree
   *   A DTCG object.
   *
   * @return array
   *   The list of design token plugins.
   */
  protected function flattenDefinitions(?DtcgInterface $dtcgTree): array {
    $tokens = [];
    if ($dtcgTree instanceof DesignToken) {
      $tokens[$dtcgTree->id()] = $dtcgTree;
    }
    elseif ($dtcgTree instanceof Group) {
      foreach ($dtcgTree->getChildren() as $child) {
        $tokens += $this->flattenDefinitions($child);
      }
    }
    return $tokens;
  }

}
