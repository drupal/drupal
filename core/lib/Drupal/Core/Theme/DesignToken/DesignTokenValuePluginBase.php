<?php

declare(strict_types=1);

namespace Drupal\Core\Theme\DesignToken;

use Drupal\Core\Plugin\ConfigurablePluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Base class for design token value plugins.
 */
abstract class DesignTokenValuePluginBase extends ConfigurablePluginBase implements DesignTokenValueInterface, ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function prepareConfiguration(mixed $configuration): array {
    if (!is_array($configuration) || array_is_list($configuration)) {
      $configuration = ['value' => $configuration];
    }
    return $configuration;
  }

}
