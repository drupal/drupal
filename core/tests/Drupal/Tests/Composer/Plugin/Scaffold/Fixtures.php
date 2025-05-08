<?php

declare(strict_types=1);

namespace Drupal\Tests\Composer\Plugin\Scaffold;

use Drupal\Tests\Composer\Plugin\FixturesBase;
use Drupal\Composer\Plugin\Scaffold\Handler;
use Drupal\Composer\Plugin\Scaffold\Interpolator;
use Drupal\Composer\Plugin\Scaffold\Operations\AppendOp;
use Drupal\Composer\Plugin\Scaffold\Operations\ReplaceOp;
use Drupal\Composer\Plugin\Scaffold\ScaffoldFilePath;

/**
 * Convenience class for creating fixtures.
 */
class Fixtures extends FixturesBase {

  /**
   * {@inheritdoc}
   */
  public function projectRoot(): string {
    return realpath(__DIR__) . '/../../../../../../../composer/Plugin/Scaffold';
  }

  /**
   * {@inheritdoc}
   */
  public function allFixturesDir(): string {
    return realpath(__DIR__ . '/fixtures');
  }

  /**
   * Gets a path to a source scaffold fixture.
   *
   * Use in place of ScaffoldFilePath::sourcePath().
   *
   * @param string $project_name
   *   The name of the project to fetch; $package_name is
   *   "fixtures/$project_name".
   * @param string $source
   *   The name of the asset; path is "assets/$source".
   *
   * @return \Drupal\Composer\Plugin\Scaffold\ScaffoldFilePath
   *   The full and relative path to the desired asset
   *
   * @see \Drupal\Composer\Plugin\Scaffold\ScaffoldFilePath::sourcePath()
   */
  public function sourcePath($project_name, $source) {
    $package_name = "fixtures/{$project_name}";
    $source_rel_path = "assets/{$source}";
    $package_path = $this->projectFixtureDir($project_name);
    return ScaffoldFilePath::sourcePath($package_name, $package_path, 'unknown', $source_rel_path);
  }

  /**
   * Gets an Interpolator with 'web-root' and 'package-name' set.
   *
   * Use in place of ManageOptions::getLocationReplacements().
   *
   * @return \Drupal\Composer\Plugin\Scaffold\Interpolator
   *   An interpolator with location replacements, including 'web-root'.
   *
   * @see \Drupal\Composer\Plugin\Scaffold\ManageOptions::getLocationReplacements()
   */
  public function getLocationReplacements() {
    $destinationTmpDir = $this->mkTmpDir('location-replacements');
    $interpolator = new Interpolator();
    $interpolator->setData(['web-root' => $destinationTmpDir, 'package-name' => 'fixtures/tmp-destination']);
    return $interpolator;
  }

  /**
   * Creates a ReplaceOp fixture.
   *
   * @param string $project_name
   *   The name of the project to fetch; $package_name is
   *   "fixtures/$project_name".
   * @param string $source
   *   The name of the asset; path is "assets/$source".
   *
   * @return \Drupal\Composer\Plugin\Scaffold\Operations\ReplaceOp
   *   A replace operation object.
   */
  public function replaceOp($project_name, $source) {
    $source_path = $this->sourcePath($project_name, $source);
    return new ReplaceOp($source_path, TRUE);
  }

  /**
   * Creates an AppendOp fixture.
   *
   * @param string $project_name
   *   The name of the project to fetch; $package_name is
   *   "fixtures/$project_name".
   * @param string $source
   *   The name of the asset; path is "assets/$source".
   *
   * @return \Drupal\Composer\Plugin\Scaffold\Operations\AppendOp
   *   An append operation object.
   */
  public function appendOp($project_name, $source) {
    $source_path = $this->sourcePath($project_name, $source);
    return new AppendOp(NULL, $source_path);
  }

  /**
   * Gets a destination path in a tmp dir.
   *
   * Use in place of ScaffoldFilePath::destinationPath().
   *
   * @param string $destination
   *   Destination path; should be in the form '[web-root]/robots.txt', where
   *   '[web-root]' is always literally '[web-root]', with any arbitrarily
   *   desired filename following.
   * @param \Drupal\Composer\Plugin\Scaffold\Interpolator $interpolator
   *   Location replacements. Obtain via Fixtures::getLocationReplacements()
   *   when creating multiple scaffold destinations.
   * @param string $package_name
   *   (optional) The name of the fixture package that this path came from.
   *   Taken from interpolator if not provided.
   *
   * @return \Drupal\Composer\Plugin\Scaffold\ScaffoldFilePath
   *   A destination scaffold file backed by temporary storage.
   *
   * @see \Drupal\Composer\Plugin\Scaffold\ScaffoldFilePath::destinationPath()
   */
  public function destinationPath($destination, ?Interpolator $interpolator = NULL, $package_name = NULL) {
    $interpolator = $interpolator ?: $this->getLocationReplacements();
    $package_name = $package_name ?: $interpolator->interpolate('[package-name]');
    return ScaffoldFilePath::destinationPath($package_name, $destination, $interpolator);
  }

  /**
   * Runs the scaffold operation.
   *
   * This is equivalent to running `composer composer-scaffold`, but we do the
   * equivalent operation by instantiating a Handler object in order to continue
   * running in the same process, so that coverage may be calculated for the
   * code executed by these tests.
   *
   * @param string $cwd
   *   The working directory to run the scaffold command in.
   *
   * @return string
   *   Output captured from tests that write to Fixtures::io().
   */
  public function runScaffold($cwd) {
    chdir($cwd);
    $handler = new Handler($this->getComposer(), $this->io());
    $handler->scaffold();
    return $this->getOutput();
  }

}
