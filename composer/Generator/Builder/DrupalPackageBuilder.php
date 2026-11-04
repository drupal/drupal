<?php

namespace Drupal\Composer\Generator\Builder;

use Drupal\Composer\Generator\BuilderInterface;
use Drupal\Composer\Generator\Util\DrupalCoreComposer;

/**
 * Base class that includes helpful utility routine for Drupal builder classes.
 */
abstract class DrupalPackageBuilder implements BuilderInterface {

  /**
   * DrupalPackageBuilder constructor.
   *
   * @param \Drupal\Composer\Generator\Util\DrupalCoreComposer $drupalCoreInfo
   *   Information about composer.json and composer.lock from current release.
   */
  public function __construct(
    protected DrupalCoreComposer $drupalCoreInfo,
  ) {
  }

}
