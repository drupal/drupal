<?php

namespace Drupal\Core\Updater;

/**
 * Defines an Exception class for Drupal\Core\Updater\Updater class hierarchy.
 *
 * This is identical to the base Exception class, we just give it a more
 * specific name so that call sites that want to tell the difference can
 * specifically catch these exceptions and treat them differently.
 *
 * @deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. There is no
 *   replacement. Use composer to manage the code for your site.
 *
 * @see https://www.drupal.org/node/3512364
 */
class UpdaterException extends \Exception {}
