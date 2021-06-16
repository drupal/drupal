<?php

namespace Drupal\Core\File\Exception;

/**
 * Exception thrown when a file's destination directory is not ready.
 *
 * A directory can be considered not ready when it either does not exist, or
 * is not writable.
 */
class DirectoryNotReadyException extends FileException {
}
