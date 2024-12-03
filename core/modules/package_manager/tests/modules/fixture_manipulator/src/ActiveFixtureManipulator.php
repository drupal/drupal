<?php

declare(strict_types=1);

namespace Drupal\fixture_manipulator;

use Drupal\package_manager\PathLocator;

/**
 * A fixture manipulator for the active directory.
 */
final class ActiveFixtureManipulator extends FixtureManipulator {

  /**
   * {@inheritdoc}
   */
  public function commitChanges(?string $dir = NULL, bool $validate_composer = FALSE): self {
    if ($dir) {
      throw new \UnexpectedValueException("$dir cannot be specific for a ActiveFixtureManipulator instance");
    }
    $dir = \Drupal::service(PathLocator::class)->getProjectRoot();
    parent::doCommitChanges($dir);
    return $this;
  }

}
