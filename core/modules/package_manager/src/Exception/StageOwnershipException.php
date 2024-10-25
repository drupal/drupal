<?php

declare(strict_types=1);

namespace Drupal\package_manager\Exception;

/**
 * Exception thrown if a stage encounters an ownership or locking error.
 *
 * Should not be thrown by external code.
 */
final class StageOwnershipException extends StageException {
}
