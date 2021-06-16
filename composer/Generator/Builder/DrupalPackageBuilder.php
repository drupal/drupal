<?php

namespace Drupal\Composer\Generator\Builder;

use Drupal\Composer\Generator\BuilderInterface;
use Drupal\Composer\Generator\Util\DrupalCoreComposer;

/**
 * Base class that includes helpful utility routine for Drupal builder classes.
 */
abstract class DrupalPackageBuilder implements BuilderInterface {

  /**
   * Information about composer.json, composer.lock etc. in current release.
   *
   * @var \Drupal\Composer\Generator\Util\DrupalCoreComposer
   */
  protected $drupalCoreInfo;

  /**
   * DrupalPackageBuilder constructor.
   *
   * @param \Drupal\Composer\Generator\Util\DrupalCoreComposer $drupalCoreInfo
   *   Information about composer.json and composer.lock from current release.
   */
  public function __construct(DrupalCoreComposer $drupalCoreInfo) {
    $this->drupalCoreInfo = $drupalCoreInfo;
  }

}
