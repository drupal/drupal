<?php

namespace Drupal\Composer\Generator;

use Drupal\Composer\Generator\Util\DrupalCoreComposer;

/**
 * Produce the output for a metapackage.
 *
 * BuilderInterface provides an interface for builder classes which are
 * called by the PackageGenerator in order to produce a derived metapackage from
 * the provided source package.
 *
 * See the README.txt file in composer/Metapackage for a description of what
 * a metapackage is, and an explanation of the metapackages produced by the
 * generator.
 */
interface BuilderInterface {

  /**
   * BuilderInterface constructor.
   *
   * @param \Drupal\Composer\Generator\Util\DrupalCoreComposer $drupalCoreInfo
   *   Information about the composer.json, composer.lock, and repository path.
   */
  public function __construct(DrupalCoreComposer $drupalCoreInfo);

  /**
   * Return the path to where the metapackage should be written.
   *
   * @return string
   *   Path to the metapackage.
   */
  public function getPath();

  /**
   * Generate the Composer.json data for the current tag or branch.
   *
   * @return array
   *   Composer json data.
   */
  public function getPackage();

}
