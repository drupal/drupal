<?php

declare(strict_types=1);

namespace Drupal\Core\Theme\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Theme\DesignToken\DesignTokenValuePluginCollection;

/**
 * Interface for design token config entity.
 *
 * @internal
 *   This API is experimental.
 */
interface DesignTokenInterface extends ConfigEntityInterface {

  /**
   * The character used has placeholder for the dot character.
   *
   * Keyed lists in config cannot accept dot. So this character is used when
   * saving are reading the scopes in config.
   */
  public const string DOT_CONVERSION_CHARACTER = '%';

  /**
   * Gets CSS variable name.
   *
   * @return string
   *   The CSS variable name.
   */
  public function getCssVariableName(): string;

  /**
   * Gets scopes with design token plugin values according to the type.
   *
   * @return \Drupal\Core\Theme\DesignToken\DesignTokenValuePluginCollection
   *   The prepared scope collection.
   */
  public function getScopes(): DesignTokenValuePluginCollection;

  /**
   * Exports the given tokens into CSS variables.
   *
   * @param \Drupal\Core\Theme\Entity\DesignTokenInterface[] $tokens
   *   The token entities.
   *
   * @return string
   *   The inline CSS.
   */
  public static function toCss(array $tokens): string;

}
