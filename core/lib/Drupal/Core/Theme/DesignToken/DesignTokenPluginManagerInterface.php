<?php

declare(strict_types=1);

namespace Drupal\Core\Theme\DesignToken;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Interface for design token manager.
 *
 * @internal
 *   This API is experimental.
 */
interface DesignTokenPluginManagerInterface extends PluginManagerInterface {

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\Core\Theme\DesignToken\DesignToken|null
   *   The design token. NULL if not found.
   */
  public function getDefinition($plugin_id, $exception_on_invalid = TRUE);

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\Core\Theme\DesignToken\DesignToken[]
   *   The design tokens.
   */
  public function getDefinitions();

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\Core\Theme\DesignToken\DesignToken[]
   *   The design tokens.
   */
  public function findDefinitions(): array;

}
