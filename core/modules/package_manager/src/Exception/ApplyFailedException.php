<?php

declare(strict_types=1);

namespace Drupal\package_manager\Exception;

/**
 * Exception thrown if a stage encounters an error applying an update.
 *
 * If this exception is thrown it indicates that an update of the active
 * codebase was attempted but failed. If this happens the site code is in an
 * indeterminate state. Package Manager does not provide a method for recovering
 * from this state. The site code should be restored from a backup.
 *
 * This exception is different from StageFailureMarkerException in that it is
 * thrown if an error happens *during* the apply operation, rather than before
 * or after it.
 *
 * Should not be thrown by external code.
 */
final class ApplyFailedException extends StageException {
}
