<?php

namespace Drupal\Composer;

use Composer\Composer as ComposerApp;
use Composer\Script\Event;
use Composer\Semver\Comparator;
use Drupal\Composer\Generator\PackageGenerator;

/**
 * Provides static functions for composer script events. See also
 * core/lib/Drupal/Composer/Composer.php, which contains similar
 * scripts needed by projects that include drupal/core. Scripts that
 * are only needed by drupal/drupal go here.
 *
 * @see https://getcomposer.org/doc/articles/scripts.md
 */
class Composer {

  /**
   * Update metapackages whenever composer.lock is updated.
   *
   * @param \Composer\Script\Event $event
   */
  public static function generateMetapackages(Event $event) {
    $generator = new PackageGenerator();
    $generator->generate($event->getIO(), getcwd());
  }

  /**
   * Ensure that the minimum required version of Composer is running.
   * Throw an exception if Composer is too old.
   */
  public static function ensureComposerVersion() {
    $composerVersion = method_exists(ComposerApp::class, 'getVersion') ?
      ComposerApp::getVersion() : ComposerApp::VERSION;
    if (Comparator::lessThan($composerVersion, '1.9.0')) {
      throw new \RuntimeException("Drupal core development requires Composer 1.9.0, but Composer $composerVersion is installed. Please run 'composer self-update'.");
    }
  }

  /**
   * Return the branch name the current Drupal version is associated with.
   *
   * @return string
   *   A branch name, e.g. 8.9.x or 9.0.x.
   */
  public static function drupalVersionBranch() {
    return preg_replace('#\.[0-9]+-dev#', '.x-dev', \Drupal::VERSION);
  }

}
