<?php

/**
 * @file
 * Definition of Drupal\Core\Updater\UpdaterFileTransferException.
 */

namespace Drupal\Core\Updater;

/**
 * Defines a child class of Drupal\Core\Updater\UpdaterException that indicates
 * a Drupal\Core\FileTransfer\FileTransferInterface exception.
 *
 * We have to catch Drupal\Core\FileTransfer\FileTransferInterface exceptions
 * and wrap those in t(), since Drupal\Core\FileTransfer\FileTransferInterface
 * is so low-level that it doesn't use any Drupal APIs and none of the strings
 * are translated.
 */
class UpdaterFileTransferException extends UpdaterException {
}
