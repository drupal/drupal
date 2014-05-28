<?php

/**
 * @file
 * Contains \Drupal\migrate\Annotation\MigrateDestination.
 */

namespace Drupal\migrate\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a migration destination plugin annotation object.
 *
 * @Annotation
 */
class MigrateSource extends Plugin {

  /**
   * A unique identifier for the process plugin.
   *
   * @var string
   */
  public $id;

  /**
   * Whether requirements are met.
   *
   * @var bool
   */
  public $requirements_met = TRUE;

}
