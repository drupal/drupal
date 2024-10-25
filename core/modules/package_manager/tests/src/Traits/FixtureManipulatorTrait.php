<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Traits;

use Drupal\fixture_manipulator\StageFixtureManipulator;

/**
 * A trait for common fixture manipulator functions.
 */
trait FixtureManipulatorTrait {

  /**
   * Gets the stage fixture manipulator service.
   *
   * @return \Drupal\fixture_manipulator\StageFixtureManipulator|object|null
   *   The stage fixture manipulator service.
   */
  protected function getStageFixtureManipulator() {
    return $this->container->get(StageFixtureManipulator::class);
  }

}
