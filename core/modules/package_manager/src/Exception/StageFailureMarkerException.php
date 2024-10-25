<?php

declare(strict_types=1);

namespace Drupal\package_manager\Exception;

/**
 * Exception thrown if a stage can't be created due to an earlier failed commit.
 *
 * If this exception is thrown it indicates that an earlier commit operation had
 * failed. If this happens the site code is in an indeterminate state. Package
 * Manager does not provide a method for recovering from this state. The site
 * code should be restored from a backup.
 *
 * We are extending RuntimeException rather than StageException which makes it
 * clear that it's unrelated to the stage life cycle.
 *
 * This exception is different from ApplyFailedException as it focuses on
 * the failure marker being detected outside the stage lifecycle.
 */
final class StageFailureMarkerException extends \RuntimeException {
}
