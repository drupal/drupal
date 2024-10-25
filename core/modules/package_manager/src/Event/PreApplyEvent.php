<?php

declare(strict_types=1);

namespace Drupal\package_manager\Event;

use Drupal\package_manager\ImmutablePathList;
use Drupal\package_manager\StageBase;
use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;

/**
 * Event fired before staged changes are synced to the active directory.
 */
final class PreApplyEvent extends PreOperationStageEvent {

  /**
   * The list of paths to ignore in the active and stage directories.
   *
   * @var \Drupal\package_manager\ImmutablePathList
   */
  public readonly ImmutablePathList $excludedPaths;

  /**
   * Constructs a PreApplyEvent object.
   *
   * @param \Drupal\package_manager\StageBase $stage
   *   The stage which fired this event.
   * @param \PhpTuf\ComposerStager\API\Path\Value\PathListInterface $excluded_paths
   *   The list of paths to exclude. These will not be copied from the stage
   *   directory to the active directory, nor be deleted from the active
   *   directory if they exist, when the stage directory is copied back into
   *   the active directory.
   */
  public function __construct(StageBase $stage, PathListInterface $excluded_paths) {
    parent::__construct($stage);
    $this->excludedPaths = new ImmutablePathList($excluded_paths);
  }

}
