<?php

namespace Drupal\Composer;

use Composer\Composer as ComposerApp;
use Composer\Script\Event;
use Composer\Semver\Comparator;
use Composer\Semver\VersionParser;
use Drupal\Composer\Generator\ComponentGenerator;
use Drupal\Composer\Generator\PackageGenerator;
use Symfony\Component\Finder\Finder;

/**
 * Provides static functions for composer script events.
 *
 * See also core/lib/Drupal/Composer/Composer.php, which contains similar
 * scripts needed by projects that include drupal/core. Scripts that are only
 * needed by drupal/drupal go here.
 *
 * @see https://getcomposer.org/doc/articles/scripts.md
 */
class Composer {

  /**
   * Update metapackages whenever composer.lock is updated.
   *
   * @param \Composer\Script\Event $event
   *   The Composer event.
   */
  public static function generateMetapackages(Event $event): void {
    $generator = new PackageGenerator();
    $generator->generate($event->getIO(), getcwd());
  }

  /**
   * Update component packages whenever composer.lock is updated.
   *
   * @param \Composer\Script\Event $event
   *   The Composer event.
   */
  public static function generateComponentPackages(Event $event): void {
    $generator = new ComponentGenerator();
    $generator->generate($event, getcwd());
  }

  /**
   * Set the version of Drupal; used in release process and by the test suite.
   *
   * @param string $root
   *   Path to root of drupal/drupal repository.
   * @param string $version
   *   Semver version to set Drupal's version to.
   *
   * @throws \UnexpectedValueException
   */
  public static function setDrupalVersion(string $root, string $version): void {
    // We use VersionParser::normalize to validate that $version is valid.
    // It will throw an exception if it is not.
    $versionParser = new VersionParser();
    $versionParser->normalize($version);

    // Rewrite Drupal.php with the provided version string.
    $drupal_static_path = "$root/core/lib/Drupal.php";
    $drupal_static_source = file_get_contents($drupal_static_path);
    $drupal_static_source = preg_replace('#const VERSION = [^;]*#', "const VERSION = '$version'", $drupal_static_source);
    file_put_contents($drupal_static_path, $drupal_static_source);

    // Update the template project stability to match the version we set.
    static::setTemplateProjectStability($root, $version);
  }

  /**
   * Set the stability of the template projects to match the Drupal version.
   *
   * @param string $root
   *   Path to root of drupal/drupal repository.
   * @param string $version
   *   Semver version that Drupal was set to.
   */
  protected static function setTemplateProjectStability(string $root, string $version): void {
    $stability = VersionParser::parseStability($version);

    $templateProjectPaths = static::composerSubprojectPaths($root, 'Template');
    foreach ($templateProjectPaths as $path) {
      $dir = dirname($path);
      exec("composer --working-dir=$dir config minimum-stability $stability", $output, $status);
      if ($status) {
        throw new \Exception('Could not set minimum-stability for template project ' . basename($dir));
      }
    }
  }

  /**
   * Ensure that the minimum required version of Composer is running.
   *
   * Throw an exception if Composer is too old.
   */
  public static function ensureComposerVersion(): void {
    $composerVersion = method_exists(ComposerApp::class, 'getVersion') ?
      ComposerApp::getVersion() : ComposerApp::VERSION;
    if (Comparator::lessThan($composerVersion, '2.3.6')) {
      throw new \RuntimeException("Drupal core development requires Composer 2.3.6, but Composer $composerVersion is installed. Run 'composer self-update'.");
    }
  }

  /**
   * Return the branch name the current Drupal version is associated with.
   *
   * @return string
   *   A branch name, e.g. 8.9.x or 9.0.x.
   */
  public static function drupalVersionBranch(): string {
    return preg_replace('#\.[0-9]+-dev#', '.x-dev', \Drupal::VERSION);
  }

  /**
   * Return the list of subprojects of a given type.
   *
   * @param string $root
   *   Path to root of drupal/drupal repository.
   * @param string $subprojectType
   *   Type of subproject - one of Metapackage, Plugin, or Template.
   *
   * @return \Symfony\Component\Finder\Finder
   *   A Finder object.
   */
  public static function composerSubprojectPaths(string $root, string $subprojectType): Finder {
    return Finder::create()
      ->files()
      ->name('composer.json')
      ->in("$root/composer/$subprojectType");
  }

}
