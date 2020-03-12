<?php

namespace Drupal\Core\Update;

/**
 * An exception thrown for removed post-update functions.
 *
 * Occurs when a module defines hook_post_update_NAME() implementations
 * that are listed as removed in hook_removed_post_updates().
 */
class RemovedPostUpdateNameException extends \LogicException {
}
