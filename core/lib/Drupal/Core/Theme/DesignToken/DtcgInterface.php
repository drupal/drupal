<?php

declare(strict_types=1);

namespace Drupal\Core\Theme\DesignToken;

/**
 * Interface for DTCG tokens and groups.
 *
 * DTCG: Design Tokens Community Group.
 *
 * @see https://www.designtokens.org/
 */
interface DtcgInterface {

  /**
   * The type of the token or group.
   *
   * @return string|null
   *   The type of the token or group. NULL if undefined.
   */
  public function getType(): ?string;

  /**
   * The path of the token or group.
   *
   * @return string
   *   The path of the token or group.
   */
  public function getPath(): string;

  /**
   * Exports to the DTCG representation.
   *
   * @return array
   *   The DTCG representation.
   */
  public function toDtcg(): array;

}
