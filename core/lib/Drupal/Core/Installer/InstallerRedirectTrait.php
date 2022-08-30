<?php

namespace Drupal\Core\Installer;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\ConnectionNotDefinedException;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\DatabaseException;
use Drupal\Core\Database\DatabaseNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides methods for checking if Drupal is already installed.
 */
trait InstallerRedirectTrait {

  /**
   * Returns whether the current PHP process runs on CLI.
   *
   * @return bool
   */
  protected function isCli() {
    return PHP_SAPI === 'cli';
  }

  /**
   * Determines if an exception handler should redirect to the installer.
   *
   * @param \Throwable $exception
   *   The exception to check.
   * @param \Drupal\Core\Database\Connection|null $connection
   *   (optional) The default database connection. If not provided, a less
   *   comprehensive check will be performed. This can be the case if the
   *   exception occurs early enough that a database connection object isn't
   *   available from the container yet.
   *
   * @return bool
   *   TRUE if the exception handler should redirect to the installer because
   *   Drupal is not installed yet, or FALSE otherwise.
   */
  protected function shouldRedirectToInstaller(\Throwable $exception, Connection $connection = NULL) {
    // Never redirect on the command line.
    if ($this->isCli()) {
      return FALSE;
    }

    // Never redirect if we're already in the installer.
    if (InstallerKernel::installationAttempted()) {
      return FALSE;
    }

    // If the database wasn't found, assume the user hasn't entered it properly
    // and redirect to the installer. This check needs to come first because a
    // DatabaseNotFoundException is also an instance of DatabaseException.
    if ($exception instanceof DatabaseNotFoundException || $exception instanceof ConnectionNotDefinedException) {
      return TRUE;
    }

    // To avoid unnecessary queries, only act if the exception is one that is
    // expected to occur when Drupal has not yet been installed. This includes
    // NotFoundHttpException because an uninstalled site won't have route
    // information available yet and therefore can return 404 errors.
    if (!($exception instanceof \PDOException || $exception instanceof DatabaseException || $exception instanceof NotFoundHttpException)) {
      return FALSE;
    }

    // Redirect if there isn't even any database connection information in
    // settings.php yet, since that means Drupal is not installed.
    if (!Database::getConnectionInfo()) {
      return TRUE;
    }

    // Redirect if the database is empty.
    if ($connection) {
      try {
        return !$connection->schema()->tableExists('sessions');
      }
      catch (\Exception $e) {
        // If we still have an exception at this point, we need to be careful
        // since we should not redirect if the exception represents an error on
        // an already-installed site (for example, if the database server went
        // down). Assume we shouldn't redirect, just in case.
        return FALSE;
      }
    }

    // When in doubt, don't redirect.
    return FALSE;
  }

}
