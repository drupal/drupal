<?php

declare(strict_types=1);

namespace Drupal\Core\Theme\DesignToken;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Theme\DesignToken\Attribute\DesignTokenValue;

/**
 * Defines a design token value plugin manager.
 *
 * @internal
 *   The design tokens API is experimental and is not meant for production use.
 *   See https://www.drupal.org/core/experimental for more information.
 */
class DesignTokenValuePluginManager extends DefaultPluginManager implements DesignTokenValuePluginManagerInterface {

  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/DesignTokenValue',
      $namespaces,
      $module_handler,
      DesignTokenValueInterface::class,
      DesignTokenValue::class
    );
    $this->alterInfo('design_token_value_info');
    $this->setCacheBackend($cache_backend, 'design_token_value_plugins');
  }

}
