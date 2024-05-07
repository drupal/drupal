<?php

declare(strict_types=1);

namespace Drupal\Core\Config\Checkpoint;

/**
 * Thrown when trying to access a checkpoint that does not exist.
 *
 * @internal
 *   This API is experimental.
 */
final class UnknownCheckpointException extends \RuntimeException {
}
