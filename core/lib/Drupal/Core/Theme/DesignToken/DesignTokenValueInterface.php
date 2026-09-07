<?php

declare(strict_types=1);

namespace Drupal\Core\Theme\DesignToken;

/**
 * Interface for design tokens value plugins.
 */
interface DesignTokenValueInterface {

  /**
   * Exports as DTCG.
   *
   * @return mixed
   *   DTCG data of the value key.
   */
  public function toDtcg(): mixed;

  /**
   * Exports as CSS value.
   *
   * @return string
   *   The CSS variable.
   */
  public function toCss(): string;

  /**
   * Prepares configuration for plugin creation.
   *
   * Ensures that the configuration is an associative array.
   *
   * @param mixed $configuration
   *   The configuration to prepare.
   *
   * @return array
   *   The prepared configuration.
   */
  public static function prepareConfiguration(mixed $configuration): array;

}
