<?php

declare(strict_types=1);

namespace Drupal\Core\Theme\Icon;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\PluginFormFactoryInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Plugin\PluginWithFormsInterface;
use Drupal\Core\Theme\Icon\Attribute\IconExtractor;

/**
 * IconExtractor plugin manager.
 *
 * @internal
 *   Icon is currently experimental and should only be leveraged by experimental
 *   modules and development releases of contributed modules.
 *   See https://www.drupal.org/core/experimental for more information.
 */
class IconExtractorPluginManager extends DefaultPluginManager {

  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler,
    protected readonly PluginFormFactoryInterface $pluginFormFactory,
  ) {
    parent::__construct(
      'Plugin/IconExtractor',
      $namespaces,
      $module_handler,
      IconExtractorInterface::class,
      IconExtractor::class
    );
    $this->alterInfo('icon_extractor_info');
    $this->setCacheBackend($cache_backend, 'icon_extractor_plugins');
  }

  /**
   * Get multiple extractor settings form.
   *
   * @param array $icon_pack_configurations
   *   All the icon pack configurations containing the extractor.
   *
   * @return \Drupal\Core\Plugin\PluginFormInterface[]
   *   The extractor form indexed by extractor id.
   */
  public function getExtractorForms(array $icon_pack_configurations): array {
    $extractor_forms = [];
    foreach ($icon_pack_configurations as $icon_pack_configuration) {
      $pack_id = $icon_pack_configuration['id'];
      $form = $this->getExtractorForm($icon_pack_configuration);
      if (NULL === $form) {
        continue;
      }
      $extractor_forms[$pack_id] = $form;
    }

    return $extractor_forms;
  }

  /**
   * Get an extractor settings form.
   *
   * @param array $icon_pack_configuration
   *   The extractor configuration.
   *
   * @return \Drupal\Core\Plugin\PluginFormInterface|null
   *   The extractor form or null.
   */
  public function getExtractorForm(array $icon_pack_configuration): ?PluginFormInterface {
    if (!isset($icon_pack_configuration['settings'])) {
      return NULL;
    }
    /** @var \Drupal\Core\Theme\Icon\IconExtractorInterface $plugin */
    $plugin = $this->createInstance($icon_pack_configuration['extractor'], $icon_pack_configuration);
    return $this->getPluginForm($plugin);
  }

  /**
   * Retrieves the plugin form for a given icon extractor.
   *
   * @param \Drupal\Core\Theme\Icon\IconExtractorInterface $icon_extractor
   *   The ui icons extractor plugin.
   *
   * @return \Drupal\Core\Plugin\PluginFormInterface
   *   The plugin form for this plugin.
   */
  protected function getPluginForm(IconExtractorInterface $icon_extractor): PluginFormInterface {
    if ($icon_extractor instanceof PluginWithFormsInterface) {
      return $this->pluginFormFactory->createInstance($icon_extractor, 'settings');
    }
    return $icon_extractor;
  }

}
