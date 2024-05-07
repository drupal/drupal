<?php

declare(strict_types=1);

namespace Drupal\Core\Config\Checkpoint;

/**
 * Thrown when using the checkpoint storage with no checkpoints.
 *
 * @internal
 *   This API is experimental.
 */
final class NoCheckpointsException extends \RuntimeException {

  /**
   * {@inheritdoc}
   */
  protected $message = 'This storage cannot be read because there are no checkpoints';

}
