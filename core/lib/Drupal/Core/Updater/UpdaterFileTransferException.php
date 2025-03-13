<?php

namespace Drupal\Core\Updater;

/**
 * Defines a child class of Drupal\Core\Updater\UpdaterException.
 *
 * Indicates a Drupal\Core\FileTransfer\FileTransfer exception.
 *
 * We have to catch Drupal\Core\FileTransfer\FileTransfer exceptions
 * and wrap those in t(), since Drupal\Core\FileTransfer\FileTransfer
 * is so low-level that it doesn't use any Drupal APIs and none of the strings
 * are translated.
 *
 * @deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. There is no
 *   replacement. Use composer to manage the code for your site.
 *
 * @see https://www.drupal.org/node/3512364
 */
class UpdaterFileTransferException extends UpdaterException {
}
