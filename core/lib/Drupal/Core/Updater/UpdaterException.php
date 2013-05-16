<?php

/**
 * @file
 * Definition of Drupal\Core\Updater\UpdaterException.
 */

namespace Drupal\Core\Updater;

/**
 * Defines a Exception class for the Drupal\Core\Updater\Updater class
 * hierarchy.
 *
 * This is identical to the base Exception class, we just give it a more
 * specific name so that call sites that want to tell the difference can
 * specifically catch these exceptions and treat them differently.
 */
class UpdaterException extends \Exception {}
