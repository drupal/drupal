<?php

declare(strict_types=1);

namespace Drupal\Core\Config\Checkpoint;

/**
 * Thrown when trying to add a checkpoint with an ID that already exists.
 *
 * @internal
 *   This API is experimental.
 */
final class CheckpointExistsException extends \RuntimeException {
}
