<?php

declare(strict_types=1);

namespace Drupal\Core\Config\Checkpoint;

/**
 * A value object to store information about a checkpoint.
 *
 * @internal
 *   This API is experimental.
 */
final class Checkpoint {

  /**
   * Constructs a checkpoint object.
   *
   * @param string $id
   *   The checkpoint's ID.
   * @param \Stringable|string $label
   *   The human-readable label.
   * @param int $timestamp
   *   The timestamp when the checkpoint was created.
   * @param string|null $parent
   *   The ID of the checkpoint's parent.
   */
  public function __construct(
    public readonly string $id,
    public readonly \Stringable|string $label,
    public readonly int $timestamp,
    public readonly ?string $parent,
  ) {
  }

}
